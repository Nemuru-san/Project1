<?php

namespace App\Livewire\Sales\ReturnTransaction;

use App\Models\DeliveryOrder;
use App\Models\SalesReturn as SalesReturnModel;
use App\Models\SalesReturnItem;
use App\Models\StockBalance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class SalesReturn extends Component
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

    public ?SalesReturnModel $selectedReturn = null;

    public string $returnDate = '';

    public ?int $deliveryOrderId = null;

    public string $notes = '';

    public array $items = [];

    protected function rules(): array
    {
        return ['returnDate' => ['required', 'date', 'before_or_equal:today'], 'deliveryOrderId' => ['required', 'integer', 'exists:delivery_orders,id'], 'notes' => ['nullable', 'string', 'max:1000'], 'items' => ['required', 'array', 'min:1'], 'items.*.qty' => ['nullable', 'integer', 'min:0'], 'items.*.reason' => ['nullable', 'string', 'max:500']];
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
        $this->showModal = true;
    }

    public function updatedDeliveryOrderId(): void
    {
        $this->items = [];
        $delivery = DeliveryOrder::with(['items.product', 'items.warehouse', 'items.unit', 'items.salesOrderItem'])->where('status', DeliveryOrder::STATUS_SHIPPED)->find($this->deliveryOrderId);
        if (! $delivery) {
            $this->deliveryOrderId = null;

            return;
        }

        $returned = SalesReturnItem::selectRaw('delivery_order_item_id, SUM(qty) total_returned')
            ->whereHas('salesReturn', fn ($query) => $query->whereIn('status', [SalesReturnModel::STATUS_DRAFT, SalesReturnModel::STATUS_CONFIRMED]))
            ->whereIn('delivery_order_item_id', $delivery->items->pluck('id'))->groupBy('delivery_order_item_id')->pluck('total_returned', 'delivery_order_item_id');

        $this->items = $delivery->items->map(function ($item) use ($returned) {
            $remaining = max(0, (int) $item->qty_delivered - (int) ($returned[$item->id] ?? 0));
            $orderItem = $item->salesOrderItem;
            $orderQty = max(1, (int) ($orderItem?->qty ?? 1));
            $unitPrice = max(0, (int) round(((int) ($orderItem?->line_total ?? 0)) / $orderQty));

            return ['delivery_order_item_id' => $item->id, 'product_name' => $item->product?->name ?? '-', 'product_sku' => $item->product?->sku ?? '-', 'warehouse_name' => $item->warehouse?->name ?? '-', 'unit_name' => $item->unit?->name ?? '-', 'delivered_qty' => (int) $item->qty_delivered, 'remaining_qty' => $remaining, 'unit_price' => $unitPrice, 'qty' => 0, 'reason' => ''];
        })->filter(fn ($row) => $row['remaining_qty'] > 0)->values()->all();
    }

    public function save(): void
    {
        $this->authorizeModule();
        $this->validate();
        if (! collect($this->items)->contains(fn ($row) => (int) ($row['qty'] ?? 0) > 0)) {
            throw ValidationException::withMessages(['items' => 'Isi minimal satu Qty Retur.']);
        }

        $return = DB::transaction(function () {
            $delivery = DeliveryOrder::with('items.salesOrderItem')->lockForUpdate()->where('status', DeliveryOrder::STATUS_SHIPPED)->findOrFail($this->deliveryOrderId);
            $inputs = collect($this->items)->keyBy('delivery_order_item_id');
            $rows = [];
            foreach ($delivery->items as $item) {
                $input = $inputs->get($item->id);
                $qty = (int) ($input['qty'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $used = (int) SalesReturnItem::where('delivery_order_item_id', $item->id)->whereHas('salesReturn', fn ($query) => $query->whereIn('status', [SalesReturnModel::STATUS_DRAFT, SalesReturnModel::STATUS_CONFIRMED]))->sum('qty');
                $remaining = max(0, (int) $item->qty_delivered - $used);
                if ($qty > $remaining) {
                    throw ValidationException::withMessages(['items' => "Qty retur {$item->product?->name} melebihi sisa ({$remaining})."]);
                }
                $orderItem = $item->salesOrderItem;
                $unitPrice = max(0, (int) round((int) ($orderItem?->line_total ?? 0) / max(1, (int) ($orderItem?->qty ?? 1))));
                $rows[] = ['delivery_order_item_id' => $item->id, 'sales_order_item_id' => $item->sales_order_item_id, 'product_id' => $item->product_id, 'warehouse_id' => $item->warehouse_id, 'unit_id' => $item->unit_id, 'conversion' => $item->conversion, 'qty' => $qty, 'qty_base' => $qty * (int) $item->conversion, 'unit_price' => $unitPrice, 'subtotal' => $qty * $unitPrice, 'reason' => trim((string) ($input['reason'] ?? '')) ?: null];
            }
            if ($rows === []) {
                throw ValidationException::withMessages(['items' => 'Tidak ada item retur yang valid.']);
            }
            $return = SalesReturnModel::create(['return_no' => $this->generateCode(), 'return_date' => $this->returnDate, 'customer_id' => $delivery->customer_id, 'delivery_order_id' => $delivery->id, 'sales_order_id' => $delivery->sales_order_id, 'status' => SalesReturnModel::STATUS_DRAFT, 'notes' => trim($this->notes) ?: null, 'created_by' => Auth::id()]);
            $return->items()->createMany($rows);

            return $return;
        });

        $this->resetForm();
        $this->dispatch('toast', message: "Retur Penjualan {$return->return_no} disimpan sebagai Draf.", type: 'success');
    }

    public function confirmReturn(int $id): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.return.sales-return.confirm'), 403);
        if (SalesReturnModel::whereKey($id)->where('status', SalesReturnModel::STATUS_DRAFT)->exists()) {
            $this->confirmTargetId = $id;
            $this->showConfirmModal = true;
        }
    }

    public function confirm(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.return.sales-return.confirm'), 403);
        DB::transaction(function () {
            $return = SalesReturnModel::with('items')->lockForUpdate()->findOrFail($this->confirmTargetId);
            if ($return->status !== SalesReturnModel::STATUS_DRAFT) {
                throw ValidationException::withMessages(['return' => 'Hanya retur Draf yang dapat dikonfirmasi.']);
            }
            foreach ($return->items as $item) {
                $stock = StockBalance::firstOrCreate(['warehouse_id' => $item->warehouse_id, 'product_id' => $item->product_id], ['quantity' => 0]);
                StockBalance::whereKey($stock->id)->lockForUpdate()->firstOrFail()->increment('quantity', $item->qty_base);
            }
            $return->update(['status' => SalesReturnModel::STATUS_CONFIRMED, 'confirmed_at' => now(), 'confirmed_by' => Auth::id()]);
        });
        $this->showConfirmModal = false;
        $this->confirmTargetId = null;
        $this->dispatch('toast', message: 'Retur Penjualan dikonfirmasi dan stok dikembalikan.', type: 'success');
    }

    public function cancel(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        DB::transaction(function () use ($id) {
            $return = SalesReturnModel::with('items')->lockForUpdate()->findOrFail($id);
            if ($return->status !== SalesReturnModel::STATUS_CONFIRMED || $return->returnInvoice()->exists()) {
                throw ValidationException::withMessages(['return' => 'Retur tidak dapat dibatalkan karena sudah memiliki Faktur Retur atau status tidak valid.']);
            }
            foreach ($return->items as $item) {
                $stock = StockBalance::where('warehouse_id', $item->warehouse_id)->where('product_id', $item->product_id)->lockForUpdate()->first();
                if (! $stock || $stock->quantity < $item->qty_base) {
                    throw ValidationException::withMessages(['return' => 'Stok tidak cukup untuk membatalkan retur.']);
                }
                $stock->decrement('quantity', $item->qty_base);
            }
            $return->update(['status' => SalesReturnModel::STATUS_CANCELLED]);
        });
        $this->dispatch('toast', message: 'Retur dibatalkan dan stok dikurangi kembali.', type: 'success');
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $return = SalesReturnModel::findOrFail($id);
        abort_unless($return->status === SalesReturnModel::STATUS_DRAFT, 422);
        $return->delete();
        $this->dispatch('toast', message: 'Retur Penjualan Draf dihapus.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $this->selectedReturn = SalesReturnModel::withTrashed()->with(['customer', 'deliveryOrder', 'salesOrder', 'items.product', 'items.warehouse', 'items.unit', 'returnInvoice'])->findOrFail($id);
        $this->showDetail = true;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function authorizeModule(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.return.sales-return'), 403);
    }

    private function generateCode(): string
    {
        $prefix = 'SR/'.now()->format('ym').'/';
        $last = SalesReturnModel::withTrashed()->where('return_no', 'like', $prefix.'%')->orderByDesc('id')->value('return_no');

        return $prefix.str_pad($last ? (int) str($last)->afterLast('/') + 1 : 1, 4, '0', STR_PAD_LEFT);
    }

    private function resetForm(): void
    {
        $this->reset(['showModal', 'deliveryOrderId', 'notes', 'items']);
        $this->returnDate = now()->toDateString();
        $this->resetErrorBag();
    }

    public function render()
    {
        $returns = SalesReturnModel::with(['customer', 'deliveryOrder', 'salesOrder', 'returnInvoice'])->withCount('items')
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('return_no', 'like', '%'.$this->search.'%')->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$this->search.'%'))->orWhereHas('deliveryOrder', fn ($delivery) => $delivery->where('delivery_no', 'like', '%'.$this->search.'%'))))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))->when($this->dateFrom, fn ($query) => $query->whereDate('return_date', '>=', $this->dateFrom))->when($this->dateTo, fn ($query) => $query->whereDate('return_date', '<=', $this->dateTo))->latest('return_date')->latest('id')->paginate($this->perPage);

        return view('livewire.sales.return-transaction.sales-return', ['returns' => $returns, 'deliveryOrders' => DeliveryOrder::with('customer')->where('status', DeliveryOrder::STATUS_SHIPPED)->latest('delivery_date')->get()]);
    }
}
