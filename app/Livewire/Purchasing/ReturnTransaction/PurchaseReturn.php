<?php

namespace App\Livewire\Purchasing\ReturnTransaction;

use App\Models\GoodsReceive;
use App\Models\PurchaseReturn as PurchaseReturnModel;
use App\Models\PurchaseReturnItem;
use App\Models\StockBalance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseReturn extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public bool $showModal = false;

    public bool $showDetail = false;

    public bool $showConfirmModal = false;

    public ?int $confirmTargetId = null;

    public ?PurchaseReturnModel $selectedReturn = null;

    public string $returnDate = '';

    public ?int $goodsReceiveId = null;

    public string $notes = '';

    public array $items = [];

    protected function rules(): array
    {
        return [
            'returnDate' => ['required', 'date', 'before_or_equal:today'],
            'goodsReceiveId' => ['required', 'integer', 'exists:goods_receives,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.qty' => ['nullable', 'integer', 'min:0'],
            'items.*.reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function mount(): void
    {
        $this->returnDate = now()->toDateString();
        if ($id = request()->integer('return')) {
            $this->openDetail($id);
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorizeModule();
        $this->resetForm();
        $this->returnDate = now()->toDateString();
        $this->showModal = true;
    }

    public function updatedGoodsReceiveId(): void
    {
        $this->items = [];
        if (! $this->goodsReceiveId) {
            return;
        }

        $goodsReceive = GoodsReceive::with(['items.product.category', 'items.warehouse', 'items.unit', 'items.purchaseOrderItem'])
            ->where('status', GoodsReceive::STATUS_RECEIVED)
            ->find($this->goodsReceiveId);

        if (! $goodsReceive) {
            $this->goodsReceiveId = null;

            return;
        }

        $returned = PurchaseReturnItem::query()
            ->selectRaw('goods_receive_item_id, SUM(qty) as total_returned')
            ->whereHas('purchaseReturn', fn ($query) => $query->whereIn('status', [PurchaseReturnModel::STATUS_DRAFT, PurchaseReturnModel::STATUS_CONFIRMED]))
            ->whereIn('goods_receive_item_id', $goodsReceive->items->pluck('id'))
            ->groupBy('goods_receive_item_id')
            ->pluck('total_returned', 'goods_receive_item_id');

        $this->items = $goodsReceive->items->map(function ($item) use ($returned) {
            $received = (int) $item->qty_received;
            $remaining = max(0, $received - (int) ($returned[$item->id] ?? 0));
            $poItem = $item->purchaseOrderItem;
            $orderedQty = max(1, (int) ($poItem?->qty ?? 1));
            $unitPrice = max(0, (int) round((((int) ($poItem?->price ?? 0) * $orderedQty) - (int) ($poItem?->disc ?? 0)) / $orderedQty));

            return [
                'goods_receive_item_id' => $item->id,
                'product_name' => $item->product?->name ?? '-',
                'product_sku' => $item->product?->sku ?? '-',
                'warehouse_name' => $item->warehouse?->name ?? '-',
                'unit_name' => $item->unit?->name ?? '-',
                'received_qty' => $received,
                'remaining_qty' => $remaining,
                'unit_price' => $unitPrice,
                'qty' => 0,
                'reason' => '',
            ];
        })->filter(fn ($row) => $row['remaining_qty'] > 0)->values()->all();
    }

    public function save(): void
    {
        $this->authorizeModule();
        $this->validate();

        if (! collect($this->items)->contains(fn ($row) => (int) ($row['qty'] ?? 0) > 0)) {
            throw ValidationException::withMessages(['items' => 'Isi minimal satu Qty Retur.']);
        }

        $purchaseReturn = DB::transaction(function () {
            $goodsReceive = GoodsReceive::with(['items.purchaseOrderItem'])->lockForUpdate()
                ->where('status', GoodsReceive::STATUS_RECEIVED)->findOrFail($this->goodsReceiveId);
            $inputRows = collect($this->items)->keyBy('goods_receive_item_id');
            $rows = [];

            foreach ($goodsReceive->items as $item) {
                $input = $inputRows->get($item->id);
                $qty = (int) ($input['qty'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $alreadyReturned = (int) PurchaseReturnItem::query()
                    ->where('goods_receive_item_id', $item->id)
                    ->whereHas('purchaseReturn', fn ($query) => $query->whereIn('status', [PurchaseReturnModel::STATUS_DRAFT, PurchaseReturnModel::STATUS_CONFIRMED]))
                    ->sum('qty');
                $remaining = max(0, (int) $item->qty_received - $alreadyReturned);
                if ($qty > $remaining) {
                    throw ValidationException::withMessages(['items' => "Qty retur {$item->product?->name} melebihi sisa yang dapat diretur ({$remaining})."]);
                }

                $poItem = $item->purchaseOrderItem;
                $orderedQty = max(1, (int) ($poItem?->qty ?? 1));
                $unitPrice = max(0, (int) round((((int) ($poItem?->price ?? 0) * $orderedQty) - (int) ($poItem?->disc ?? 0)) / $orderedQty));
                $rows[] = [
                    'goods_receive_item_id' => $item->id,
                    'purchase_order_item_id' => $item->purchase_order_item_id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->warehouse_id,
                    'unit_id' => $item->unit_id,
                    'conversion' => $item->conversion,
                    'qty' => $qty,
                    'qty_base' => $qty * (int) $item->conversion,
                    'unit_price' => $unitPrice,
                    'subtotal' => $qty * $unitPrice,
                    'reason' => trim((string) ($input['reason'] ?? '')) ?: null,
                ];
            }

            if ($rows === []) {
                throw ValidationException::withMessages(['items' => 'Tidak ada item retur yang valid.']);
            }

            $return = PurchaseReturnModel::create([
                'return_no' => $this->generateCode(),
                'return_date' => $this->returnDate,
                'supplier_id' => $goodsReceive->supplier_id,
                'goods_receive_id' => $goodsReceive->id,
                'purchase_order_id' => $goodsReceive->purchase_order_id,
                'status' => PurchaseReturnModel::STATUS_DRAFT,
                'notes' => trim($this->notes) ?: null,
                'created_by' => Auth::id(),
            ]);
            $return->items()->createMany($rows);

            return $return;
        });

        $this->resetForm();
        $this->dispatch('toast', message: "Retur Pembelian {$purchaseReturn->return_no} berhasil disimpan sebagai Draf.", type: 'success');
    }

    public function confirmReturn(int $id): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.return.purchase-return.confirm'), 403);
        $return = PurchaseReturnModel::findOrFail($id);
        if ($return->status !== PurchaseReturnModel::STATUS_DRAFT) {
            return;
        }
        $this->confirmTargetId = $id;
        $this->showConfirmModal = true;
    }

    public function confirm(): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.return.purchase-return.confirm'), 403);
        DB::transaction(function () {
            $return = PurchaseReturnModel::with(['items.product', 'items.warehouse'])->lockForUpdate()->findOrFail($this->confirmTargetId);
            if ($return->status !== PurchaseReturnModel::STATUS_DRAFT) {
                throw ValidationException::withMessages(['return' => 'Hanya retur Draf yang dapat dikonfirmasi.']);
            }

            foreach ($return->items as $item) {
                $stock = StockBalance::where('warehouse_id', $item->warehouse_id)->where('product_id', $item->product_id)->lockForUpdate()->first();
                $available = (int) ($stock?->quantity ?? 0);
                if (! $stock || $available < $item->qty_base) {
                    throw ValidationException::withMessages(['return' => "Stok {$item->product?->name} di {$item->warehouse?->name} tidak cukup. Tersedia {$available}, dibutuhkan {$item->qty_base}."]);
                }
                $stock->decrement('quantity', $item->qty_base);
            }

            $return->update(['status' => PurchaseReturnModel::STATUS_CONFIRMED, 'confirmed_at' => now(), 'confirmed_by' => Auth::id()]);
        });

        $this->showConfirmModal = false;
        $this->confirmTargetId = null;
        $this->dispatch('toast', message: 'Retur Pembelian dikonfirmasi dan stok telah dikurangi.', type: 'success');
    }

    public function cancel(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        DB::transaction(function () use ($id) {
            $return = PurchaseReturnModel::with('items')->lockForUpdate()->findOrFail($id);
            if ($return->status !== PurchaseReturnModel::STATUS_CONFIRMED || $return->returnInvoice()->exists()) {
                throw ValidationException::withMessages(['return' => 'Retur tidak dapat dibatalkan karena statusnya tidak valid atau sudah memiliki Faktur Retur.']);
            }
            foreach ($return->items as $item) {
                $stock = StockBalance::firstOrCreate(['warehouse_id' => $item->warehouse_id, 'product_id' => $item->product_id], ['quantity' => 0]);
                StockBalance::whereKey($stock->id)->lockForUpdate()->firstOrFail()->increment('quantity', $item->qty_base);
            }
            $return->update(['status' => PurchaseReturnModel::STATUS_CANCELLED]);
        });
        $this->dispatch('toast', message: 'Retur dibatalkan dan stok dikembalikan.', type: 'success');
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $return = PurchaseReturnModel::findOrFail($id);
        abort_unless($return->status === PurchaseReturnModel::STATUS_DRAFT, 422);
        $return->delete();
        $this->dispatch('toast', message: 'Retur Pembelian Draf dihapus.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $this->selectedReturn = PurchaseReturnModel::withTrashed()->with(['supplier', 'goodsReceive', 'purchaseOrder', 'items.product', 'items.warehouse', 'items.unit', 'returnInvoice'])->findOrFail($id);
        $this->showDetail = true;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function generateCode(): string
    {
        $prefix = 'PR/'.now()->format('ym').'/';
        $last = PurchaseReturnModel::withTrashed()->where('return_no', 'like', $prefix.'%')->orderByDesc('id')->value('return_no');

        return $prefix.str_pad($last ? (int) str($last)->afterLast('/') + 1 : 1, 4, '0', STR_PAD_LEFT);
    }

    private function authorizeModule(): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.return.purchase-return'), 403);
    }

    private function resetForm(): void
    {
        $this->reset(['showModal', 'goodsReceiveId', 'notes', 'items']);
        $this->returnDate = now()->toDateString();
        $this->resetErrorBag();
    }

    public function render()
    {
        $returns = PurchaseReturnModel::query()->with(['supplier', 'goodsReceive', 'purchaseOrder', 'returnInvoice'])->withCount('items')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('return_no', 'like', '%'.$this->search.'%')->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%'.$this->search.'%'))->orWhereHas('goodsReceive', fn ($gr) => $gr->where('code', 'like', '%'.$this->search.'%'))))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('return_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('return_date', '<=', $this->dateTo))
            ->latest('return_date')->latest('id')->paginate($this->perPage);

        return view('livewire.purchasing.return-transaction.purchase-return', [
            'returns' => $returns,
            'goodsReceives' => GoodsReceive::with('supplier')->where('status', GoodsReceive::STATUS_RECEIVED)->whereHas('items', fn ($query) => $query->whereRaw('qty_received > 0'))->latest('date')->get(),
        ]);
    }
}
