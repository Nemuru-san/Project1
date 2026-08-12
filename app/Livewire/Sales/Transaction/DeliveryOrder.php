<?php

namespace App\Livewire\Sales\Transaction;

use App\Models\CustomerAddress;
use App\Models\DeliveryOrder as DeliveryOrderModel;
use App\Models\DeliveryOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\StockBalance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class DeliveryOrder extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public bool $showModal = false;

    public bool $showDetailModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    public bool $showShipModal = false;

    public ?int $shipTargetId = null;

    public bool $showCancelShipmentModal = false;

    public ?int $cancelShipmentTargetId = null;

    public ?DeliveryOrderModel $selectedDeliveryOrder = null;

    public string $deliveryNo = '';

    public string $deliveryDate = '';

    public ?int $salesOrderId = null;

    public ?int $customerId = null;

    public ?int $customerAddressId = null;

    public string $notes = '';

    public array $items = [];

    protected function rules(): array
    {
        return [
            'deliveryDate' => ['required', 'date'],
            'salesOrderId' => ['required', 'integer', 'exists:sales_orders,id'],
            'customerAddressId' => [
                'nullable',
                'integer',
                Rule::exists('customer_addresses', 'id')->where(
                    fn ($query) => $query->where('customer_id', $this->customerId)->whereNull('deleted_at')
                ),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sales_order_item_id' => ['required', 'integer', 'exists:sales_order_items,id'],
            'items.*.qty_delivered' => ['nullable', 'integer', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected $messages = [
        'salesOrderId.required' => 'Pesanan Penjualan wajib dipilih.',
        'deliveryDate.required' => 'Tanggal pengiriman wajib diisi.',
        'customerAddressId.exists' => 'Alamat pengiriman harus milik pelanggan pada Pesanan Penjualan.',
        'items.*.qty_delivered.integer' => 'Qty dikirim harus berupa bilangan bulat.',
        'items.*.qty_delivered.min' => 'Qty dikirim tidak boleh negatif.',
    ];

    public function mount(): void
    {
        $this->deliveryDate = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorizeModule();
        $this->resetForm();
        $this->deliveryNo = $this->generateCode();
        $this->showModal = true;
    }

    public function updatedSalesOrderId(mixed $value): void
    {
        if (! $value) {
            $this->customerId = null;
            $this->customerAddressId = null;
            $this->items = [];

            return;
        }

        $salesOrder = SalesOrder::with([
            'customer', 'items.product.category', 'items.warehouse', 'items.unit',
        ])->findOrFail((int) $value);
        $this->authorizeSalesOrder($salesOrder);

        if (! in_array($salesOrder->status, ['verified', 'processing'], true)) {
            $this->addError('salesOrderId', 'Pesanan Penjualan harus dikonfirmasi sebelum dibuatkan Surat Jalan.');
            $this->items = [];

            return;
        }

        $this->customerId = $salesOrder->customer_id;
        $this->customerAddressId = $salesOrder->customer_address_id;
        $this->items = $salesOrder->items->map(function (SalesOrderItem $item) {
            $alreadyAllocated = $this->allocatedQuantity($item->id);

            return [
                'sales_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'sku' => $item->product?->sku ?? '-',
                'product_name' => $item->product?->name ?? '-',
                'category_name' => $item->product?->category?->name ?? '-',
                'warehouse_id' => $item->warehouse_id,
                'warehouse_name' => $item->warehouse?->name ?? '-',
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name ?? '-',
                'conversion' => (int) $item->conversion,
                'qty_order' => (int) $item->qty,
                'qty_already_allocated' => $alreadyAllocated,
                'qty_outstanding' => max(0, (int) $item->qty - $alreadyAllocated),
                'qty_delivered' => 0,
                'note' => '',
            ];
        })->filter(fn (array $item) => $item['qty_outstanding'] > 0)->values()->all();
    }

    public function save(): void
    {
        $this->authorizeModule();
        $this->validate();

        if (! collect($this->items)->contains(fn (array $item) => (int) ($item['qty_delivered'] ?? 0) > 0)) {
            throw ValidationException::withMessages(['items' => 'Isi minimal satu Qty Dikirim.']);
        }

        $deliveryOrder = DB::transaction(function () {
            $salesOrder = SalesOrder::with('items')->lockForUpdate()->findOrFail($this->salesOrderId);
            $this->authorizeSalesOrder($salesOrder);

            if (! in_array($salesOrder->status, ['verified', 'processing'], true)) {
                throw ValidationException::withMessages(['salesOrderId' => 'Pesanan Penjualan harus dikonfirmasi sebelum dibuatkan Surat Jalan.']);
            }

            $orderItems = $salesOrder->items->keyBy('id');
            $rows = [];

            foreach ($this->items as $index => $input) {
                $qtyDelivered = (int) ($input['qty_delivered'] ?? 0);
                if ($qtyDelivered <= 0) {
                    continue;
                }

                $orderItem = $orderItems->get((int) $input['sales_order_item_id']);
                if (! $orderItem) {
                    throw ValidationException::withMessages(["items.$index.qty_delivered" => 'Produk tidak berasal dari Pesanan Penjualan terpilih.']);
                }

                $alreadyAllocated = $this->allocatedQuantity($orderItem->id);
                $outstanding = max(0, (int) $orderItem->qty - $alreadyAllocated);
                if ($qtyDelivered > $outstanding) {
                    throw ValidationException::withMessages(["items.$index.qty_delivered" => "Qty dikirim melebihi sisa pesanan ($outstanding)."]);
                }

                $rows[] = [$orderItem, $qtyDelivered, $outstanding];
            }

            $deliveryOrder = DeliveryOrderModel::create([
                'delivery_no' => $this->generateCode(),
                'delivery_date' => $this->deliveryDate,
                'sales_order_id' => $salesOrder->id,
                'customer_id' => $salesOrder->customer_id,
                'customer_address_id' => $this->customerAddressId,
                'notes' => trim($this->notes) ?: null,
                'status' => DeliveryOrderModel::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            foreach ($rows as [$orderItem, $qtyDelivered, $outstanding]) {
                $input = collect($this->items)->firstWhere('sales_order_item_id', $orderItem->id);
                $deliveryOrder->items()->create([
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'warehouse_id' => $orderItem->warehouse_id,
                    'unit_id' => $orderItem->unit_id,
                    'conversion' => $orderItem->conversion,
                    'qty_order' => $orderItem->qty,
                    'qty_delivered' => $qtyDelivered,
                    'qty_outstanding' => $outstanding - $qtyDelivered,
                    'qty_base' => $qtyDelivered * (int) $orderItem->conversion,
                    'note' => trim((string) ($input['note'] ?? '')) ?: null,
                ]);
            }

            return $deliveryOrder;
        });

        $this->resetForm();
        $this->dispatch('toast', message: "Surat Jalan {$deliveryOrder->delivery_no} berhasil dibuat sebagai draf.", type: 'success');
    }

    public function openConfirmShipment(int $id): void
    {
        $deliveryOrder = DeliveryOrderModel::findOrFail($id);
        $this->authorizeDeliveryOrder($deliveryOrder);
        abort_unless($deliveryOrder->status === DeliveryOrderModel::STATUS_DRAFT, 422);

        $this->shipTargetId = $id;
        $this->showShipModal = true;
    }

    public function confirmShipment(): void
    {
        $this->authorizeModule();
        if (! $this->shipTargetId) {
            return;
        }

        DB::transaction(function () {
            $deliveryOrder = DeliveryOrderModel::with(['items.product', 'items.warehouse'])
                ->lockForUpdate()->findOrFail($this->shipTargetId);
            $this->authorizeDeliveryOrder($deliveryOrder);

            if ($deliveryOrder->status !== DeliveryOrderModel::STATUS_DRAFT) {
                throw ValidationException::withMessages(['shipment' => 'Surat Jalan ini sudah diproses.']);
            }

            foreach ($deliveryOrder->items as $item) {
                $stock = StockBalance::query()
                    ->where('warehouse_id', $item->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();
                $available = (int) ($stock?->quantity ?? 0);

                if (! $stock || $available < $item->qty_base) {
                    throw ValidationException::withMessages([
                        'shipment' => "Stok {$item->product?->name} di {$item->warehouse?->name} tidak cukup. Tersedia {$available}, dibutuhkan {$item->qty_base}.",
                    ]);
                }

                $stock->decrement('quantity', $item->qty_base);
            }

            $deliveryOrder->update(['status' => DeliveryOrderModel::STATUS_SHIPPED]);
            $salesOrder = SalesOrder::with('items')->lockForUpdate()->findOrFail($deliveryOrder->sales_order_id);
            $this->refreshSalesOrderStatus($salesOrder);
        });

        $this->showShipModal = false;
        $this->shipTargetId = null;
        $this->dispatch('toast', message: 'Pengiriman berhasil dikonfirmasi dan stok telah dikurangi.', type: 'success');
    }

    public function openCancelShipment(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $deliveryOrder = DeliveryOrderModel::findOrFail($id);
        abort_unless($deliveryOrder->status === DeliveryOrderModel::STATUS_SHIPPED, 422);
        $this->cancelShipmentTargetId = $id;
        $this->showCancelShipmentModal = true;
    }

    public function cancelShipment(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        if (! $this->cancelShipmentTargetId) {
            return;
        }

        DB::transaction(function () {
            $deliveryOrder = DeliveryOrderModel::with('items')->lockForUpdate()->findOrFail($this->cancelShipmentTargetId);
            if ($deliveryOrder->status !== DeliveryOrderModel::STATUS_SHIPPED) {
                throw ValidationException::withMessages(['shipment' => 'Hanya pengiriman berstatus Dikirim yang dapat dibatalkan.']);
            }

            if ($deliveryOrder->salesReturns()->whereIn('status', [SalesReturn::STATUS_DRAFT, SalesReturn::STATUS_CONFIRMED])->exists()) {
                throw ValidationException::withMessages(['shipment' => 'Pengiriman tidak dapat dibatalkan karena sudah memiliki Retur Penjualan aktif.']);
            }

            foreach ($deliveryOrder->items as $item) {
                $stock = StockBalance::query()->firstOrCreate(
                    ['warehouse_id' => $item->warehouse_id, 'product_id' => $item->product_id],
                    ['quantity' => 0],
                );
                StockBalance::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail()
                    ->increment('quantity', $item->qty_base);
            }

            $deliveryOrder->update(['status' => DeliveryOrderModel::STATUS_CANCELLED]);
            $salesOrder = SalesOrder::with('items')->lockForUpdate()->findOrFail($deliveryOrder->sales_order_id);
            $this->refreshSalesOrderStatus($salesOrder);
        });

        $this->showCancelShipmentModal = false;
        $this->cancelShipmentTargetId = null;
        $this->dispatch('toast', message: 'Pengiriman dibatalkan dan stok telah dikembalikan.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $deliveryOrder = DeliveryOrderModel::with([
            'salesOrder.preOrder', 'salesOrder.salesCanvas', 'customer', 'customerAddress',
            'creator', 'items.product', 'items.warehouse', 'items.unit',
        ])->findOrFail($id);
        $this->authorizeDeliveryOrder($deliveryOrder);
        $this->selectedDeliveryOrder = $deliveryOrder;
        $this->showDetailModal = true;
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        abort_unless(DeliveryOrderModel::findOrFail($id)->status === DeliveryOrderModel::STATUS_DRAFT, 422);
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        if (! $this->deleteTargetId) {
            return;
        }

        DB::transaction(function () {
            $deliveryOrder = DeliveryOrderModel::findOrFail($this->deleteTargetId);
            abort_unless($deliveryOrder->status === DeliveryOrderModel::STATUS_DRAFT, 422);
            $salesOrder = SalesOrder::with('items')->lockForUpdate()->findOrFail($deliveryOrder->sales_order_id);
            $deliveryOrder->delete();
            $this->refreshSalesOrderStatus($salesOrder);
        });

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Surat Jalan berhasil dihapus.', type: 'success');
    }

    private function allocatedQuantity(int $salesOrderItemId): int
    {
        return (int) DeliveryOrderItem::query()
            ->where('sales_order_item_id', $salesOrderItemId)
            ->whereHas('deliveryOrder', fn (Builder $query) => $query->whereIn('status', [
                DeliveryOrderModel::STATUS_DRAFT,
                DeliveryOrderModel::STATUS_SHIPPED,
            ]))
            ->sum('qty_delivered');
    }

    private function refreshSalesOrderStatus(SalesOrder $salesOrder): void
    {
        $salesOrder->loadMissing('items');
        $ordered = (int) $salesOrder->items->sum('qty');
        $delivered = (int) DeliveryOrderItem::query()
            ->whereIn('sales_order_item_id', $salesOrder->items->pluck('id'))
            ->whereHas('deliveryOrder', fn (Builder $query) => $query->where('status', DeliveryOrderModel::STATUS_SHIPPED))
            ->sum('qty_delivered');

        $salesOrder->update([
            'status' => match (true) {
                $ordered > 0 && $delivered >= $ordered => 'completed',
                $delivered > 0 => 'processing',
                default => 'verified',
            },
        ]);
    }

    private function authorizeModule(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.transaction.delivery-order'), 403);
    }

    private function authorizeDeliveryOrder(DeliveryOrderModel $deliveryOrder): void
    {
        $deliveryOrder->loadMissing('salesOrder.salesCanvas');
        $this->authorizeSalesOrder($deliveryOrder->salesOrder);
    }

    private function authorizeSalesOrder(SalesOrder $salesOrder): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            return;
        }

        $salesmanId = auth()->user()?->salesman()->where('is_active', true)->value('id');
        $ownsConvertedOrder = $salesOrder->salesCanvas && $salesOrder->salesCanvas->salesman_id === $salesmanId;
        abort_unless($ownsConvertedOrder || $salesOrder->created_by === Auth::id(), 403);
    }

    private function resetForm(): void
    {
        $this->reset([
            'showModal', 'showDetailModal', 'showDeleteModal', 'deleteTargetId',
            'showShipModal', 'shipTargetId', 'showCancelShipmentModal', 'cancelShipmentTargetId',
            'selectedDeliveryOrder', 'deliveryNo', 'salesOrderId', 'customerId',
            'customerAddressId', 'notes', 'items',
        ]);
        $this->deliveryDate = now()->toDateString();
        $this->resetErrorBag();
    }

    private function generateCode(): string
    {
        $prefix = 'SJ-'.now()->format('ymd').'-';
        $last = DeliveryOrderModel::withTrashed()
            ->where('delivery_no', 'like', $prefix.'%')
            ->orderByDesc('delivery_no')
            ->value('delivery_no');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    private function accessibleSalesOrders(): Builder
    {
        $currentSalesmanId = auth()->user()?->salesman()->where('is_active', true)->value('id');

        return SalesOrder::query()
            ->with(['customer', 'preOrder', 'salesCanvas'])
            ->whereIn('status', ['verified', 'processing'])
            ->whereHas('items')
            ->when(! auth()->user()?->isSuperAdmin(), fn (Builder $query) => $query->where(function (Builder $query) use ($currentSalesmanId) {
                $query->where('created_by', Auth::id())
                    ->orWhereHas('salesCanvas', fn (Builder $canvas) => $canvas->where('salesman_id', $currentSalesmanId ?? 0));
            }));
    }

    public function render()
    {
        $currentSalesmanId = auth()->user()?->salesman()->where('is_active', true)->value('id');
        $deliveryOrders = DeliveryOrderModel::query()
            ->with(['salesOrder.preOrder', 'salesOrder.salesCanvas', 'customer'])
            ->when(! auth()->user()?->isSuperAdmin(), fn (Builder $query) => $query->where(function (Builder $query) use ($currentSalesmanId) {
                $query->where('created_by', Auth::id())
                    ->orWhereHas('salesOrder.salesCanvas', fn (Builder $canvas) => $canvas->where('salesman_id', $currentSalesmanId ?? 0));
            }))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('delivery_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('delivery_date', '<=', $this->dateTo))
            ->when($this->search !== '', fn (Builder $query) => $query->where(function (Builder $query) {
                $query->where('delivery_no', 'like', '%'.$this->search.'%')
                    ->orWhereHas('salesOrder', fn (Builder $order) => $order->where('order_no', 'like', '%'.$this->search.'%'))
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%'.$this->search.'%'));
            }))
            ->latest('delivery_date')
            ->latest('id')
            ->paginate($this->perPage);

        return view('livewire.sales.transaction.delivery-order', [
            'deliveryOrders' => $deliveryOrders,
            'salesOrders' => $this->accessibleSalesOrders()->latest('date')->latest('id')->get(),
            'customerAddresses' => CustomerAddress::query()
                ->where('customer_id', $this->customerId)
                ->orderByDesc('is_primary')->orderBy('label')->get(),
        ]);
    }
}
