<?php

namespace App\Livewire\Sales\Transaction;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\SalesOrder as SalesOrderModel;
use App\Models\Warehouse;
use App\Services\Inventory\AvailableForSalesService;
use App\Services\Inventory\StockQuantityFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class SalesOrder extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $statusFilter = '';

    public int $perPage = 10;

    public bool $showTrashed = false;

    public bool $showModal = false;

    public bool $showProductModal = false;

    public bool $showDetailModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deleteTargetId = null;

    public ?int $confirmTargetId = null;

    public bool $showConfirmModal = false;

    public ?SalesOrderModel $selectedOrder = null;

    public string $orderNo = '';

    public string $date = '';

    public ?int $customerId = null;

    public ?int $customerAddressId = null;

    public bool $tax = false;

    public string $notes = '';

    public array $items = [];

    public string $productSearch = '';

    public ?int $categoryFilter = null;

    public array $selectedProductIds = [];

    protected function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'customerId' => ['required', 'integer', 'exists:customers,id'],
            'customerAddressId' => ['nullable', 'integer', Rule::exists('customer_addresses', 'id')->where(fn ($query) => $query->where('customer_id', $this->customerId)->whereNull('deleted_at'))],
            'tax' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'items.*.unit_id' => ['required', 'integer', 'exists:product_units,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.conversion' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'items.*.discount_amount' => ['required', 'integer', 'min:0'],
        ];
    }

    protected $messages = [
        'customerId.required' => 'Customer wajib dipilih.',
        'items.required' => 'Tambahkan minimal satu produk.',
        'items.min' => 'Tambahkan minimal satu produk.',
        'items.*.warehouse_id.required' => 'Gudang wajib dipilih.',
        'items.*.qty.min' => 'Qty minimal 1.',
    ];

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');

        if ($orderId = request()->integer('order')) {
            $this->openDetail($orderId);
        }
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

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedShowTrashed(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo', 'showTrashed']);
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->orderNo = $this->generateCode();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $order = SalesOrderModel::with(['items.product.prices.unit', 'items.product.baseUnit'])->findOrFail($id);
        $this->authorizeOrder($order);

        if ($order->status !== 'draft') {
            $this->dispatch('toast', message: 'Sales Order yang sudah diproses tidak dapat diubah.', type: 'error');

            return;
        }

        $this->resetForm();
        $this->editingId = $order->id;
        $this->orderNo = $order->order_no;
        $this->date = $order->date->format('Y-m-d');
        $this->customerId = $order->customer_id;
        $this->customerAddressId = $order->customer_address_id;
        $this->tax = $order->is_taxed;
        $this->notes = $order->notes ?? '';
        $this->items = $order->items->map(fn ($item) => $this->makeItem(
            $item->product,
            $item->warehouse_id,
            $item->unit_id,
            $item->qty,
            $item->unit_price,
            $item->discount_amount,
        ))->toArray();
        $this->showModal = true;
    }

    public function updatedCustomerId(): void
    {
        $this->customerAddressId = null;
    }

    public function openProductPicker(): void
    {
        $this->selectedProductIds = [];
        $this->productSearch = '';
        $this->categoryFilter = null;
        $this->showProductModal = true;
    }

    public function addSelectedProducts(): void
    {
        foreach (array_unique(array_map('intval', $this->selectedProductIds)) as $productId) {
            $this->addProduct($productId);
        }

        $this->showProductModal = false;
        $this->selectedProductIds = [];
    }

    public function addProduct(int $productId): void
    {
        if (collect($this->items)->contains('product_id', $productId)) {
            return;
        }

        $product = Product::with(['prices.unit', 'baseUnit'])->findOrFail($productId);
        $this->items[] = $this->makeItem($product);
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems(mixed $value, ?string $key = null): void
    {
        if ($key === null) {
            return;
        }

        [$index, $field] = array_pad(explode('.', $key), 2, null);

        if (! isset($this->items[$index])) {
            return;
        }

        if ($field === 'unit_id') {
            $price = ProductPrice::where('product_id', $this->items[$index]['product_id'])->where('unit_id', $value)->first();
            $this->items[$index]['conversion'] = (int) ($price?->conversion ?? 1);
            $this->items[$index]['unit_price'] = (int) ($price?->price ?? 0);
        }

        if ($field === 'warehouse_id') {
            $this->refreshItemStock((int) $index);
        }
    }

    public function save(): void
    {
        $wasEditing = (bool) $this->editingId;

        $this->validate();

        foreach ($this->items as $index => $item) {
            if ((int) $item['discount_amount'] > ((int) $item['qty'] * (int) $item['unit_price'])) {
                throw ValidationException::withMessages(["items.$index.discount_amount" => 'Diskon tidak boleh melebihi nilai produk.']);
            }
        }

        DB::transaction(function () {
            $totals = $this->totals();
            $order = $this->editingId
                ? SalesOrderModel::findOrFail($this->editingId)
                : new SalesOrderModel;

            if ($order->exists) {
                $this->authorizeOrder($order);
            }

            $order->fill([
                'order_no' => $order->exists ? $order->order_no : $this->generateCode(),
                'date' => $this->date,
                'customer_id' => $this->customerId,
                'customer_address_id' => $this->customerAddressId,
                'is_taxed' => $this->tax,
                'tax_rate' => 11,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'grand_total' => $totals['grand_total'],
                'dp_amount' => (int) ($order->dp_amount ?? 0),
                'amount_due' => max(0, $totals['grand_total'] - (int) ($order->dp_amount ?? 0)),
                'notes' => trim($this->notes) ?: null,
                'status' => 'draft',
                'created_by' => $order->exists ? $order->created_by : Auth::id(),
            ]);
            $order->save();
            $order->items()->delete();

            foreach ($this->items as $item) {
                $gross = (int) $item['qty'] * (int) $item['unit_price'];
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $item['warehouse_id'],
                    'unit_id' => $item['unit_id'],
                    'qty' => (int) $item['qty'],
                    'conversion' => (int) $item['conversion'],
                    'unit_price' => (int) $item['unit_price'],
                    'discount_amount' => (int) $item['discount_amount'],
                    'line_total' => $gross - (int) $item['discount_amount'],
                ]);
            }
        });

        $this->resetForm();
        $this->dispatch('toast', message: $wasEditing ? 'Sales Order berhasil diperbarui.' : 'Sales Order berhasil dibuat.', type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $order = SalesOrderModel::findOrFail($id);
        $this->authorizeOrder($order);
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

        SalesOrderModel::findOrFail($this->deleteTargetId)->delete();
        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Sales Order berhasil dihapus.', type: 'success');
    }

    public function restore(int $id): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat memulihkan data.', type: 'error');

            return;
        }

        SalesOrderModel::onlyTrashed()->findOrFail($id)->restore();
        $this->dispatch('toast', message: 'Sales Order berhasil dipulihkan.', type: 'success');
    }

    public function totals(): array
    {
        $subtotal = collect($this->items)->sum(fn (array $item) => (int) ($item['qty'] ?? 0) * (int) ($item['unit_price'] ?? 0));
        $discount = collect($this->items)->sum(fn (array $item) => (int) ($item['discount_amount'] ?? 0));
        $taxable = max(0, $subtotal - $discount);
        $tax = $this->tax ? (int) round($taxable * 0.11) : 0;

        return compact('subtotal', 'discount', 'tax') + ['grand_total' => $taxable + $tax];
    }

    public function openDetail(int $id): void
    {
        $order = SalesOrderModel::with(['salesCanvas', 'preOrder', 'customer', 'customerAddress', 'items.product', 'items.warehouse', 'items.unit'])->findOrFail($id);
        $this->authorizeOrder($order);
        $this->selectedOrder = $order;
        $this->showDetailModal = true;
    }

    public function openConfirmOrder(int $id): void
    {
        if (! auth()->user()?->canPerform('sales.transaction.salesOrder', 'verify')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk mengonfirmasi Pesanan Penjualan.', type: 'error');

            return;
        }
        $order = SalesOrderModel::findOrFail($id);
        if ($order->status !== 'draft') {
            $this->dispatch('toast', message: 'Hanya Pesanan Penjualan berstatus Draf yang dapat dikonfirmasi.', type: 'error');

            return;
        }
        $this->confirmTargetId = $id;
        $this->showConfirmModal = true;
    }

    public function confirmOrder(): void
    {
        if (! auth()->user()?->canPerform('sales.transaction.salesOrder', 'verify')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk mengonfirmasi Pesanan Penjualan.', type: 'error');

            return;
        }
        if (! $this->confirmTargetId) {
            return;
        }
        DB::transaction(function () {
            $order = SalesOrderModel::lockForUpdate()->findOrFail($this->confirmTargetId);
            if ($order->status !== 'draft') {
                throw ValidationException::withMessages(['status' => 'Pesanan Penjualan sudah diproses.']);
            }
            $order->forceFill(['status' => 'verified', 'verified_at' => now(), 'verified_by' => Auth::id()])->save();
        });
        $this->showConfirmModal = false;
        $this->confirmTargetId = null;
        $this->dispatch('toast', message: 'Pesanan Penjualan berhasil dikonfirmasi.', type: 'success');
    }

    private function authorizeOrder(SalesOrderModel $order): void
    {
        if (auth()->user()?->isSuperAdmin() || auth()->user()?->canPerform('sales.transaction.salesOrder', 'verify')) {
            return;
        }

        $salesmanId = auth()->user()?->salesman()->where('is_active', true)->value('id');
        $ownsConvertedOrder = $order->salesCanvas && $order->salesCanvas->salesman_id === $salesmanId;

        abort_unless($ownsConvertedOrder || $order->created_by === Auth::id(), 403);
    }

    private function makeItem(
        Product $product,
        ?int $warehouseId = null,
        ?int $unitId = null,
        int $qty = 1,
        ?int $unitPrice = null,
        int $discountAmount = 0,
    ): array {
        $product->loadMissing(['prices.unit', 'baseUnit']);
        $price = $product->prices->firstWhere('unit_id', $unitId) ?? $product->prices->first();
        $warehouseId ??= Warehouse::query()->orderBy('id')->value('id');
        $stock = $warehouseId
            ? app(AvailableForSalesService::class)->available($product->id, $warehouseId, $this->editingId)
            : 0;

        return [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'warehouse_id' => $warehouseId,
            'unit_id' => $price?->unit_id,
            'conversion' => (int) ($price?->conversion ?? 1),
            'qty' => $qty,
            'unit_price' => $unitPrice ?? (int) ($price?->price ?? 0),
            'discount_amount' => $discountAmount,
            'stock_available' => $stock,
            'stock_available_display' => app(StockQuantityFormatter::class)->format($product, $stock),
            'base_unit_name' => $product->baseUnit?->name ?? '-',
            'unit_options' => $product->prices->map(fn ($item) => [
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name ?? '-',
                'unit_code' => $item->unit?->code,
                'conversion' => (int) $item->conversion,
            ])->values()->all(),
        ];
    }

    private function refreshItemStock(int $index): void
    {
        $item = $this->items[$index];
        $stock = $item['warehouse_id']
            ? app(AvailableForSalesService::class)->available(
                (int) $item['product_id'],
                (int) $item['warehouse_id'],
                $this->editingId,
            )
            : 0;
        $this->items[$index]['stock_available'] = $stock;
        $this->items[$index]['stock_available_display'] = app(StockQuantityFormatter::class)->formatUnits(
            $stock,
            collect($item['unit_options'])->map(fn (array $unit) => [
                'conversion' => $unit['conversion'],
                'code' => $unit['unit_code'] ?? null,
                'name' => $unit['unit_name'],
            ]),
        );
    }

    private function resetForm(): void
    {
        $this->reset(['showModal', 'showProductModal', 'showDeleteModal', 'editingId', 'deleteTargetId', 'orderNo', 'customerId', 'customerAddressId', 'tax', 'notes', 'items', 'productSearch', 'categoryFilter', 'selectedProductIds']);
        $this->date = now()->format('Y-m-d');
        $this->resetErrorBag();
    }

    private function generateCode(): string
    {
        $prefix = 'SO-M-'.now()->format('ymd').'-';
        $sequence = SalesOrderModel::withTrashed()->where('order_no', 'like', $prefix.'%')->count() + 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $currentSalesmanId = auth()->user()?->salesman()->where('is_active', true)->value('id');
        $salesOrders = SalesOrderModel::query()
            ->with(['salesCanvas', 'preOrder', 'customer'])
            ->when(! auth()->user()->isSuperAdmin() && ! auth()->user()->canPerform('sales.transaction.salesOrder', 'verify'), fn (Builder $query) => $query->where(function (Builder $query) use ($currentSalesmanId) {
                $query->where('created_by', Auth::id())
                    ->orWhereHas('salesCanvas', fn (Builder $canvas) => $canvas->where('salesman_id', $currentSalesmanId ?? 0));
            }))
            ->when($this->showTrashed, fn (Builder $query) => $query->withTrashed())
            ->when($this->statusFilter !== '', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('date', '<=', $this->dateTo))
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('order_no', 'like', '%'.$this->search.'%')
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->latest('date')
            ->latest('id')
            ->paginate($this->perPage);

        return view('livewire.sales.transaction.sales-order', [
            'salesOrders' => $salesOrders,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'customerAddresses' => CustomerAddress::where('customer_id', $this->customerId)->orderByDesc('is_primary')->orderBy('label')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'products' => Product::with('category')
                ->whereHas('prices')
                ->when($this->productSearch, fn (Builder $query) => $query->where(fn (Builder $product) => $product->where('name', 'like', '%'.$this->productSearch.'%')->orWhere('sku', 'like', '%'.$this->productSearch.'%')))
                ->when($this->categoryFilter, fn (Builder $query) => $query->where('category_id', $this->categoryFilter))
                ->orderBy('name')->limit(50)->get(),
            'categories' => ProductCategory::orderBy('name')->get(),
            'totals' => $this->totals(),
        ]);
    }
}
