<?php

namespace App\Livewire\Purchasing\Transaction;

use App\Models\GoodsReceive as GoodsReceiveModel;
use App\Models\GoodsReceiveItem;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class GoodsReceive extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';
    public string $statusFilter = '';
    public int $perPage = 10;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Modal state
    public bool $showModal = false;
    public bool $showDetailModal = false;
    public bool $showDeleteModal = false;
    public ?int $deleteTargetId = null;
    public bool $showTrashed = false;
    // public ?array $detail = null;
    public ?GoodsReceiveModel $selectedGR = null;
    public string $selectedStatus = 'draft';

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
        'items.*.warehouse_id.required' => 'Warehouse wajib dipilih.',
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

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
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

        if ($goodsReceive->status !== 'draft') {
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
                    $query->where('status', 'received');
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
                    message: 'Produk ' . ($poItem->product?->name ?? '-') . ' belum punya satuan / harga.',
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
            ->contains(fn(array $item): bool => (int) $item['qty_received'] > 0);

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

                if ($goodsReceive->status !== 'draft') {
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
                    'status' => 'draft',
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
                    return $grItem->goodsReceive?->status === 'received';
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
        $this->selectedGR = GoodsReceiveModel::query()
            ->with([
                'supplier',
                'purchaseOrder',
                'items.product.category',
                'items.unit',
                'items.warehouse',
            ])
            ->withTrashed()
            ->findOrFail($id);

        $this->selectedStatus = $this->selectedGR->status;
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedGR = null;
        $this->selectedStatus = 'draft';

        $this->resetValidation();
    }

    public function updateStatus(): void
    {
        if (! $this->selectedGR) {
            return;
        }

        if (! in_array($this->selectedStatus, ['draft', 'received'], true)) {
            throw ValidationException::withMessages([
                'selectedStatus' => 'Status tidak valid.',
            ]);
        }

        $goodsReceive = GoodsReceiveModel::query()
            ->with('items')
            ->findOrFail($this->selectedGR->id);

        if ($goodsReceive->status === 'received') {
            $this->dispatch('toast', message: 'Goods Receive sudah received dan tidak bisa diubah lagi.', type: 'error');
            return;
        }

        if ($this->selectedStatus === 'draft') {
            $this->dispatch('toast', message: 'Status masih draft.', type: 'success');
            return;
        }

        DB::transaction(function () use ($goodsReceive) {
            foreach ($goodsReceive->items as $item) {
                $stockBalance = StockBalance::firstOrCreate(
                    [
                        'warehouse_id' => $item->warehouse_id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'quantity' => 0,
                    ]
                );

                $stockBalance->increment('quantity', (int) $item->qty_base);
            }

            $goodsReceive->update([
                'status' => 'received',
            ]);

            $this->updatePurchaseOrderStatus((int) $goodsReceive->purchase_order_id);
        });

        $this->openDetail($goodsReceive->id);

        $this->dispatch('toast', message: 'Goods Receive berhasil di-received. Stok berhasil ditambahkan.', type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! $this->deleteTargetId) {
            return;
        }

        $goodsReceive = GoodsReceiveModel::findOrFail($this->deleteTargetId);

        if ($goodsReceive->status !== 'draft') {
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
        $this->selectedStatus = 'draft';

        $this->resetValidation();
    }

    private function generateGrCode(): string
    {
        $prefix = 'GR/' . now()->format('dmy') . '/';

        $last = GoodsReceiveModel::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($last) {
            $lastNumber = (int) str_replace($prefix, '', $last->code);
            $nextNumber = $lastNumber + 1;
        }

        return $prefix . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
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

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                    ->orWhere('status', 'like', '%' . $this->search . '%')
                    ->orWhereHas('supplier', function ($supplierQuery) {
                        $supplierQuery->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('purchaseOrder', function ($poQuery) {
                        $poQuery->where('code', 'like', '%' . $this->search . '%');
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
