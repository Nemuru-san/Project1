<?php

namespace App\Livewire\Purchasing\Transaction;

use App\Models\GoodsReceive as GoodsReceiveModel;
use App\Models\GoodsReceiveItem;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class GoodsReceive extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    // Modal state
    public bool $showModal = false;

    public bool $showDetailModal = false;

    public bool $showDeleteModal = false;

    public bool $showReceiveModal = false;

    public ?int $deleteTargetId = null;

    public ?int $receiveTargetId = null;

    public bool $showTrashed = false;

    // public bool $showDetail = false;
    public ?GoodsReceiveModel $selectedGR = null;

    public string $selectedStatus = '';

    // Form state
    public ?int $editingId = null;

    public string $code = '';

    public string $date = '';

    public ?int $purchase_order_id = null;

    public ?int $supplier_id = null;

    public string $supplier_name = '';

    public string $note = '';

    public array $items = [];

    protected function rules(): array
    {
        return [
            'date' => 'required|date',
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'note' => 'nullable|string|max:1000',

            'items' => 'required|array|min:1',
            'items.*.purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.warehouse_id' => 'required|exists:warehouses,id',
            'items.*.unit_id' => 'required|exists:product_units,id',
            'items.*.conversion' => 'required|integer|min:1',
            'items.*.qty_order' => 'required|integer|min:0',
            'items.*.qty_outstanding' => 'required|integer|min:0',
            'items.*.qty_received' => 'required|integer|min:0',
        ];
    }

    protected array $messages = [
        'date.required' => 'Tanggal GR wajib diisi.',
        'purchase_order_id.required' => 'PO wajib dipilih.',
        'supplier_id.required' => 'Supplier wajib dipilih.',
        'items.required' => 'Detail item tidak boleh kosong.',
        'items.*.warehouse_id.required' => 'Gudang wajib dipilih.',
        'items.*.qty_received.min' => 'Qty received tidak boleh minus.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo', 'showTrashed']);
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $allowedFields = [
            'code',
            'date',
            'status',
            'created_at',
        ];

        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $field
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';

        $this->sortField = $field;
    }

    public function openCreate(): void
    {
        $this->resetForm();

        $this->code = $this->generateGrCode();
        $this->date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $goodsReceive = GoodsReceiveModel::query()
            ->with([
                'supplier',
                'purchaseOrder',
                'items.product.category',
                'items.unit',
                'items.warehouse',
            ])
            ->findOrFail($id);

        if ($goodsReceive->status !== 'Draft') {
            $this->dispatch(
                'toast',
                message: 'Goods Receive hanya bisa diedit saat status draft.',
                type: 'error'
            );

            return;
        }

        $this->resetForm();

        $this->editingId = $goodsReceive->id;
        $this->code = $goodsReceive->code;
        $this->date = $goodsReceive->date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->purchase_order_id = $goodsReceive->purchase_order_id;
        $this->supplier_id = $goodsReceive->supplier_id;
        $this->supplier_name = $goodsReceive->supplier?->name ?? '';
        $this->note = $goodsReceive->note ?? '';

        $this->items = $goodsReceive->items->map(function ($item): array {
            return [
                'goods_receive_item_id' => $item->id,
                'purchase_order_item_id' => $item->purchase_order_item_id,
                'product_id' => $item->product_id,
                'product_sku' => $item->product?->sku ?? '-',
                'product_name' => $item->product?->name ?? '-',
                'category_name' => $item->product?->category?->name ?? '-',
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name ?? '-',
                'conversion' => (int) $item->conversion,
                'qty_order' => (int) $item->qty_order,
                'qty_outstanding' => (int) $item->qty_outstanding + (int) $item->qty_received,

                'qty_received' => (int) $item->qty_received,
                'warehouse_id' => (string) $item->warehouse_id,
                'note' => $item->note ?? '',
            ];
        })->toArray();

        $this->showModal = true;
    }

    public function updatedPurchaseOrderId(): void
    {
        if ($this->editingId) {
            return;
        }

        $this->loadPurchaseOrderItems();
    }

    public function loadPurchaseOrderItems(): void
    {
        $this->items = [];
        $this->supplier_id = null;
        $this->supplier_name = '';

        if (! $this->purchase_order_id) {
            return;
        }

        $purchaseOrder = PurchaseOrder::query()
            ->with([
                'supplier',
                'items.product.category',
                'items.product.prices.unit',
                'items.goodsReceiveItems',
            ])
            ->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])
            ->find($this->purchase_order_id);

        if (! $purchaseOrder) {
            $this->purchase_order_id = null;

            $this->dispatch(
                'toast',
                message: 'PO tidak bisa dipilih. Hanya PO status Approved atau Partially Received yang boleh dibuatkan Goods Receive.',
                type: 'error'
            );

            return;
        }

        $this->supplier_id = $purchaseOrder->supplier_id;
        $this->supplier_name = $purchaseOrder->supplier?->name ?? '';

        foreach ($purchaseOrder->items as $poItem) {
            $totalReceived = (int) $poItem->goodsReceiveItems()
                ->whereHas('goodsReceive', function ($query) {
                    $query->where('status', 'Received');
                })
                ->sum('qty_received');

            $qtyOrder = (int) $poItem->qty;
            $qtyOutstanding = max(0, $qtyOrder - $totalReceived);

            if ($qtyOutstanding <= 0) {
                continue;
            }

            $price = $poItem->product?->prices?->sortBy('conversion')->first();

            if (! $price) {
                $this->dispatch(
                    'toast',
                    message: 'Produk '.($poItem->product?->name ?? '-').' belum punya satuan / harga.',
                    type: 'error'
                );

                continue;
            }

            $this->items[] = [
                'purchase_order_item_id' => $poItem->id,
                'product_id' => $poItem->product_id,
                'product_sku' => $poItem->product?->sku ?? '-',
                'product_name' => $poItem->product?->name ?? '-',
                'category_name' => $poItem->product?->category?->name ?? '-',

                'unit_id' => $price?->unit_id,
                'unit_name' => $price?->unit?->name ?? '-',
                'conversion' => (int) ($price?->conversion ?? 1),

                'qty_order' => $qtyOrder,
                'qty_outstanding' => $qtyOutstanding,
                'qty_received' => 0,
                'warehouse_id' => '',
                'note' => '',
            ];
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        $hasReceivedQty = collect($this->items)
            ->contains(fn (array $item): bool => (int) $item['qty_received'] > 0);

        if (! $hasReceivedQty) {
            throw ValidationException::withMessages([
                'items' => 'Minimal ada 1 item dengan qty received lebih dari 0.',
            ]);
        }

        foreach ($this->items as $index => $item) {
            $qtyReceived = (int) $item['qty_received'];
            $qtyOutstanding = (int) $item['qty_outstanding'];

            if ($qtyReceived > $qtyOutstanding) {
                throw ValidationException::withMessages([
                    "items.$index.qty_received" => 'Qty received tidak boleh lebih besar dari qty outstanding.',
                ]);
            }
        }

        DB::transaction(function () use ($validated) {
            if ($this->editingId) {
                $goodsReceive = GoodsReceiveModel::with('items')->findOrFail($this->editingId);

                if ($goodsReceive->status !== 'Draft') {
                    throw ValidationException::withMessages([
                        'items' => 'Goods Receive hanya bisa diedit saat status draft.',
                    ]);
                }

                $goodsReceive->update([
                    'date' => $validated['date'],
                    'note' => $validated['note'] ?? null,
                ]);

                $goodsReceive->items()->delete();
            } else {
                $goodsReceive = GoodsReceiveModel::create([
                    'code' => $this->generateGrCode(),
                    'date' => $validated['date'],
                    'supplier_id' => $validated['supplier_id'],
                    'purchase_order_id' => $validated['purchase_order_id'],
                    'status' => GoodsReceiveModel::STATUS_DRAFT,
                    'note' => $validated['note'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            foreach ($this->items as $item) {
                $qtyReceived = (int) $item['qty_received'];

                if ($qtyReceived <= 0) {
                    continue;
                }

                $conversion = (int) $item['conversion'];
                $qtyBase = $qtyReceived * $conversion;
                $qtyOutstanding = max(0, (int) $item['qty_outstanding'] - $qtyReceived);

                GoodsReceiveItem::create([
                    'goods_receive_id' => $goodsReceive->id,
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $item['warehouse_id'],
                    'unit_id' => $item['unit_id'],
                    'conversion' => $conversion,
                    'qty_order' => $item['qty_order'],
                    'qty_received' => $qtyReceived,
                    'qty_outstanding' => $qtyOutstanding,
                    'qty_base' => $qtyBase,
                    'note' => $item['note'] ?? null,
                ]);
            }
        });

        $wasEditing = (bool) $this->editingId;

        $this->showModal = false;
        $this->resetForm();

        $this->dispatch(
            'toast',
            message: $wasEditing
                ? 'Goods Receive draft berhasil diperbarui.'
                : 'Goods Receive berhasil disimpan sebagai draft.',
            type: 'success'
        );
    }

    private function updatePurchaseOrderStatus(int $purchaseOrderId): void
    {
        $purchaseOrder = PurchaseOrder::with('items.goodsReceiveItems.goodsReceive')
            ->findOrFail($purchaseOrderId);

        $totalOrder = 0;
        $totalReceived = 0;

        foreach ($purchaseOrder->items as $item) {
            $totalOrder += (int) $item->qty;

            $receivedForItem = $item->goodsReceiveItems
                ->filter(function ($grItem) {
                    return $grItem->goodsReceive?->status === 'Received';
                })
                ->sum('qty_received');

            $totalReceived += (int) $receivedForItem;
        }

        if ($totalReceived >= $totalOrder) {
            $purchaseOrder->update([
                'status' => PurchaseOrder::STATUS_RECEIVED,
            ]);

            return;
        }

        if ($totalReceived > 0 && $totalReceived < $totalOrder) {
            $purchaseOrder->update([
                'status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ]);

            return;
        }

        $purchaseOrder->update([
            'status' => PurchaseOrder::STATUS_APPROVED,
        ]);
    }

    public function openDetail(int $id): void
    {
        $this->selectedGR = GoodsReceiveModel::with([
            'supplier',
            'purchaseOrder',
            'creator',
            'items.product.category',
            'items.unit',
            'items.warehouse',
        ])->findOrFail($id);

        $this->selectedStatus = $this->selectedGR->status;
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedGR = null;
        $this->selectedStatus = '';
    }

    public function updateStatus(): void
    {
        if (! $this->selectedGR) {
            return;
        }

        $this->validate([
            'selectedStatus' => [
                'required',
                Rule::in(GoodsReceiveModel::statusOptions()),
            ],
        ]);

        $goodsReceive = GoodsReceiveModel::with([
            'items',
            'purchaseInvoices',
            'purchaseOrder',
        ])->findOrFail($this->selectedGR->id);

        if (! in_array($goodsReceive->status, [
            GoodsReceiveModel::STATUS_DRAFT,
            GoodsReceiveModel::STATUS_RECEIVED,
        ], true)) {
            $this->addError('selectedStatus', 'Status Goods Receive ini tidak bisa diubah manual.');

            return;
        }

        if ($goodsReceive->status === $this->selectedStatus) {
            return;
        }

        if ($this->selectedStatus === GoodsReceiveModel::STATUS_CANCELLED) {
            if ($goodsReceive->purchaseInvoices()->exists()) {
                $this->addError('selectedStatus', 'Penerimaan Barang tidak dapat dibatalkan karena sudah memiliki Faktur Pembelian.');

                return;
            }
        }

        if (
            $goodsReceive->status === GoodsReceiveModel::STATUS_RECEIVED &&
            $this->selectedStatus === GoodsReceiveModel::STATUS_DRAFT
        ) {
            if ($goodsReceive->purchaseInvoices()->exists()) {
                $this->addError('selectedStatus', 'Penerimaan Barang tidak dapat dikembalikan ke Draf karena sudah memiliki Faktur Pembelian.');

                return;
            }
        }

        if (
            $goodsReceive->status === GoodsReceiveModel::STATUS_DRAFT &&
            $this->selectedStatus === GoodsReceiveModel::STATUS_RECEIVED
        ) {
            if ($goodsReceive->items()->count() <= 0) {
                $this->addError('selectedStatus', 'Goods Receive tidak bisa Received karena item masih kosong.');

                return;
            }

            $invalidItem = $goodsReceive->items->first(function ($item) {
                return (int) $item->qty_received <= 0;
            });

            if ($invalidItem) {
                $this->addError('selectedStatus', 'Goods Receive tidak bisa Received karena masih ada qty received yang 0.');

                return;
            }
        }

        try {
            DB::transaction(function () use ($goodsReceive) {
                $originalStatus = $goodsReceive->status;
                $nextStatus = $this->selectedStatus;

                if (
                    $originalStatus === GoodsReceiveModel::STATUS_RECEIVED
                    && in_array($nextStatus, [
                        GoodsReceiveModel::STATUS_DRAFT,
                        GoodsReceiveModel::STATUS_CANCELLED,
                    ], true)
                ) {
                    $this->removeGoodsReceiveFromStock($goodsReceive);
                }

                $goodsReceive->update([
                    'status' => $nextStatus,
                ]);

                if (
                    $originalStatus !== GoodsReceiveModel::STATUS_RECEIVED
                    && $nextStatus === GoodsReceiveModel::STATUS_RECEIVED
                ) {
                    $this->addGoodsReceiveToStock($goodsReceive);
                }

                $this->updatePurchaseOrderStatus($goodsReceive->purchase_order_id);
            });
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');

            return;
        }

        $this->showDetailModal = false;
        $this->selectedGR = null;
        $this->selectedStatus = '';

        $this->dispatch('toast', message: 'Status Goods Receive berhasil diubah.', type: 'success');
    }

    public function confirmReceive(int $id): void
    {
        $goodsReceive = GoodsReceiveModel::with('items')->findOrFail($id);

        if ($goodsReceive->status !== GoodsReceiveModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya Goods Receive Draft yang bisa di-receive.', type: 'error');

            return;
        }

        if ($goodsReceive->items->isEmpty()) {
            $this->dispatch('toast', message: 'Goods Receive tidak bisa di-receive karena item masih kosong.', type: 'error');

            return;
        }

        $this->receiveTargetId = $id;
        $this->showReceiveModal = true;
    }

    public function cancelReceive(): void
    {
        $this->showReceiveModal = false;
        $this->receiveTargetId = null;
    }

    private function addGoodsReceiveToStock(GoodsReceiveModel $goodsReceive): void
    {
        $goodsReceive->loadMissing('items');

        foreach ($goodsReceive->items as $item) {
            $qtyBase = (int) $item->qty_base;

            if ($qtyBase <= 0) {
                continue;
            }

            $stockBalance = StockBalance::firstOrCreate(
                [
                    'warehouse_id' => $item->warehouse_id,
                    'product_id' => $item->product_id,
                ],
                [
                    'quantity' => 0,
                ]
            );

            $stockBalance->increment('quantity', $qtyBase);
        }
    }

    private function removeGoodsReceiveFromStock(GoodsReceiveModel $goodsReceive): void
    {
        $goodsReceive->loadMissing('items.product');

        foreach ($goodsReceive->items as $item) {
            $qtyBase = (int) $item->qty_base;

            if ($qtyBase <= 0) {
                continue;
            }

            $stockBalance = StockBalance::where('warehouse_id', $item->warehouse_id)
                ->where('product_id', $item->product_id)
                ->lockForUpdate()
                ->first();

            if (! $stockBalance || (int) $stockBalance->quantity < $qtyBase) {
                throw new \Exception('Stok tidak cukup untuk cancel Goods Receive item '.($item->product?->name ?? '-'));
            }

            $stockBalance->decrement('quantity', $qtyBase);
        }
    }

    public function receive(): void
    {
        if (! $this->receiveTargetId) {
            return;
        }

        $goodsReceive = GoodsReceiveModel::with('items')->findOrFail($this->receiveTargetId);

        if ($goodsReceive->status !== GoodsReceiveModel::STATUS_DRAFT) {
            $this->showReceiveModal = false;
            $this->receiveTargetId = null;

            $this->dispatch('toast', message: 'Hanya Goods Receive Draft yang bisa di-receive.', type: 'error');

            return;
        }

        DB::transaction(function () use ($goodsReceive) {
            $goodsReceive->update([
                'status' => GoodsReceiveModel::STATUS_RECEIVED,
            ]);

            $this->addGoodsReceiveToStock($goodsReceive);

            $this->updatePurchaseOrderStatus($goodsReceive->purchase_order_id);
        });

        $this->showReceiveModal = false;
        $this->receiveTargetId = null;

        $this->dispatch('toast', message: 'Goods Receive berhasil di-receive.', type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');

            return;
        }

        if (! $this->deleteTargetId) {
            return;
        }

        $goodsReceive = GoodsReceiveModel::findOrFail($this->deleteTargetId);

        if ($goodsReceive->status !== 'Draft') {
            $this->dispatch(
                'toast',
                message: 'Hanya Goods Receive status draft yang boleh dihapus.',
                type: 'error'
            );

            $this->showDeleteModal = false;
            $this->deleteTargetId = null;

            return;
        }

        $goodsReceive->items()->delete();
        $goodsReceive->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'Goods Receive draft berhasil dihapus.', type: 'success');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->date = '';
        $this->purchase_order_id = null;
        $this->supplier_id = null;
        $this->supplier_name = '';
        $this->note = '';
        $this->items = [];

        $this->showDetailModal = false;
        $this->selectedGR = null;
        $this->selectedStatus = 'Draft';

        $this->resetValidation();
    }

    private function generateGrCode(): string
    {
        $date = now()->format('dmy');
        $prefix = "GR-{$date}-";

        $last = GoodsReceiveModel::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function print(int $id)
    {
        return redirect()->route('purchases.transaction.good-receive.print', $id);
    }

    public function render()
    {
        $query = GoodsReceiveModel::query()
            ->with(['supplier', 'purchaseOrder']);

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%')
                    ->orWhereHas('supplier', function ($supplierQuery) {
                        $supplierQuery->where('name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhereHas('purchaseOrder', function ($poQuery) {
                        $poQuery->where('code', 'like', '%'.$this->search.'%');
                    });
            });
        }

        $goodsReceives = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $purchaseOrders = PurchaseOrder::query()
            ->with('supplier')
            ->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])
            ->whereDoesntHave('goodsReceives', function ($q) {
                $q->where('status', GoodsReceiveModel::STATUS_DRAFT)
                    ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId));
            })
            ->orderByDesc('date')
            ->get();

        $warehouses = Warehouse::query()
            ->orderBy('name')
            ->get();

        return view('livewire.purchasing.transaction.goods-receive', [
            'goodsReceives' => $goodsReceives,
            'purchaseOrders' => $purchaseOrders,
            'warehouses' => $warehouses,
        ]);
    }
}
