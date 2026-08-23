<?php

namespace App\Livewire\Sales\Transaction;

use App\Models\ArDpPayment;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\PreOrder as PreOrderModel;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\Warehouse;
use App\Services\ConvertPreOrderToSalesOrder;
use App\Services\Inventory\AvailableForSalesService;
use App\Services\Inventory\StockQuantityFormatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class PreOrder extends Component
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

    public bool $showConvertModal = false;

    public bool $showConfirmModal = false;

    public ?int $editingId = null;

    public ?int $deleteTargetId = null;

    public ?int $convertTargetId = null;

    public ?int $confirmTargetId = null;

    public ?PreOrderModel $selectedPreOrder = null;

    public string $preOrderNo = '';

    public string $date = '';

    public ?int $customerId = null;

    public ?int $customerAddressId = null;

    public bool $tax = false;

    public int $dpAmount = 0;

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
            'dpAmount' => ['required', 'integer', 'min:1'],
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
        'dpAmount.required' => 'Nominal DP wajib diisi.',
        'dpAmount.min' => 'Nominal DP harus lebih dari 0.',
        'items.required' => 'Tambahkan minimal satu produk.',
        'items.min' => 'Tambahkan minimal satu produk.',
        'items.*.warehouse_id.required' => 'Gudang wajib dipilih.',
        'items.*.qty.min' => 'Qty minimal 1.',
    ];

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
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
        $this->preOrderNo = $this->generateCode();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $preOrder = PreOrderModel::with(['items.product.prices.unit', 'items.product.baseUnit'])->findOrFail($id);
        $this->authorizePreOrder($preOrder);

        if ($preOrder->status !== PreOrderModel::STATUS_DRAFT || $preOrder->dpAllocations()->whereHas('payment', fn ($query) => $query->where('status', ArDpPayment::STATUS_POSTED))->exists()) {
            $this->dispatch('toast', message: 'Pesanan Awal yang sudah memiliki DP posted atau sudah dikonversi tidak dapat diubah.', type: 'error');

            return;
        }

        $this->resetForm();
        $this->editingId = $preOrder->id;
        $this->preOrderNo = $preOrder->pre_order_no;
        $this->date = $preOrder->date->format('Y-m-d');
        $this->customerId = $preOrder->customer_id;
        $this->customerAddressId = $preOrder->customer_address_id;
        $this->tax = $preOrder->is_taxed;
        $this->dpAmount = (int) $preOrder->dp_amount;
        $this->notes = $preOrder->notes ?? '';
        $this->items = $preOrder->items->map(fn ($item) => $this->makeItem($item->product, $item->warehouse_id, $item->unit_id, $item->qty, $item->unit_price, $item->discount_amount))->toArray();
        $this->showModal = true;
    }

    public function updatedCustomerId(): void
    {
        $this->customerAddressId = null;
    }

    public function openProductPicker(): void
    {
        $this->reset(['selectedProductIds', 'productSearch', 'categoryFilter']);
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
        $this->items[] = $this->makeItem(Product::with(['prices.unit', 'baseUnit'])->findOrFail($productId));
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

        $totals = $this->totals();
        if ($this->dpAmount > $totals['grand_total']) {
            throw ValidationException::withMessages(['dpAmount' => 'Nominal DP tidak boleh melebihi Neto.']);
        }

        foreach ($this->items as $index => $item) {
            if ((int) $item['discount_amount'] > ((int) $item['qty'] * (int) $item['unit_price'])) {
                throw ValidationException::withMessages(["items.$index.discount_amount" => 'Diskon tidak boleh melebihi nilai produk.']);
            }
        }

        DB::transaction(function () {
            $totals = $this->totals();
            $preOrder = $this->editingId ? PreOrderModel::lockForUpdate()->findOrFail($this->editingId) : new PreOrderModel;
            if ($preOrder->exists) {
                $this->authorizePreOrder($preOrder);
                if ($preOrder->status !== PreOrderModel::STATUS_DRAFT || $preOrder->dpAllocations()->whereHas('payment', fn ($query) => $query->where('status', ArDpPayment::STATUS_POSTED))->exists()) {
                    throw ValidationException::withMessages(['date' => 'Pesanan Awal sudah diproses dan tidak dapat diubah.']);
                }
            }

            $preOrder->fill([
                'pre_order_no' => $preOrder->exists ? $preOrder->pre_order_no : $this->generateCode(),
                'date' => $this->date,
                'customer_id' => $this->customerId,
                'customer_address_id' => $this->customerAddressId,
                'is_taxed' => $this->tax,
                'tax_rate' => 11,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'grand_total' => $totals['grand_total'],
                'dp_amount' => $this->dpAmount,
                'dp_payment_status' => PreOrderModel::DP_STATUS_UNPAID,
                'notes' => trim($this->notes) ?: null,
                'status' => PreOrderModel::STATUS_DRAFT,
                'created_by' => $preOrder->exists ? $preOrder->created_by : Auth::id(),
            ])->save();

            $preOrder->items()->delete();
            foreach ($this->items as $item) {
                $preOrder->items()->create([
                    'product_id' => $item['product_id'], 'warehouse_id' => $item['warehouse_id'], 'unit_id' => $item['unit_id'],
                    'qty' => (int) $item['qty'], 'conversion' => (int) $item['conversion'], 'unit_price' => (int) $item['unit_price'],
                    'discount_amount' => (int) $item['discount_amount'],
                    'line_total' => ((int) $item['qty'] * (int) $item['unit_price']) - (int) $item['discount_amount'],
                ]);
            }
        });

        $this->resetForm();
        $this->dispatch('toast', message: $wasEditing ? 'Pesanan Awal berhasil diperbarui.' : 'Pesanan Awal berhasil dibuat.', type: 'success');
    }

    public function openConfirmPreOrder(int $id): void
    {
        if (! auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'confirm')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk mengonfirmasi Pesanan Awal.', type: 'error');

            return;
        }

        $preOrder = PreOrderModel::findOrFail($id);
        if ($preOrder->status !== PreOrderModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya Pesanan Awal berstatus Draf yang dapat dikonfirmasi.', type: 'error');

            return;
        }

        $this->confirmTargetId = $id;
        $this->showConfirmModal = true;
    }

    public function confirmPreOrder(int $id): void
    {
        if (! auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'confirm')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk mengonfirmasi Pesanan Awal.', type: 'error');

            return;
        }

        $preOrder = PreOrderModel::findOrFail($id);

        if ($preOrder->status !== PreOrderModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya Pesanan Awal berstatus Draf yang dapat dikonfirmasi.', type: 'error');

            return;
        }

        if ((int) $preOrder->dp_amount <= 0 || (int) $preOrder->dp_amount > (int) $preOrder->grand_total) {
            $this->dispatch('toast', message: 'Nominal DP harus diisi dan tidak boleh melebihi Neto sebelum dikonfirmasi.', type: 'error');

            return;
        }

        $preOrder->update(['status' => PreOrderModel::STATUS_CONFIRMED]);
        $this->showConfirmModal = false;
        $this->confirmTargetId = null;
        $this->dispatch('toast', message: 'Pesanan Awal berhasil dikonfirmasi.', type: 'success');
    }

    public function confirmConvert(int $id): void
    {
        if (! auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'convert')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk mengonversi Pesanan Awal.', type: 'error');

            return;
        }

        $preOrder = PreOrderModel::findOrFail($id);
        if ($preOrder->status !== PreOrderModel::STATUS_CONFIRMED) {
            $this->dispatch('toast', message: 'Pesanan Awal harus dikonfirmasi sebelum dijadikan Sales Order.', type: 'error');

            return;
        }
        if ($preOrder->dp_payment_status !== PreOrderModel::DP_STATUS_PAID) {
            $this->dispatch('toast', message: 'Target DP harus dibayar lunas sebelum Pesanan Awal dijadikan Sales Order.', type: 'error');

            return;
        }
        $this->convertTargetId = $id;
        $this->showConvertModal = true;
    }

    public function convertToSalesOrder(ConvertPreOrderToSalesOrder $converter): void
    {
        if (! auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'convert')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk mengonversi Pesanan Awal.', type: 'error');

            return;
        }

        if (! $this->convertTargetId) {
            return;
        }
        try {
            $order = $converter->handle($this->convertTargetId);
            $this->showConvertModal = false;
            $this->convertTargetId = null;
            $this->dispatch('toast', message: "Berhasil dikonversi ke {$order->order_no}. DP posted sudah mengurangi sisa tagihan SO.", type: 'success');
        } catch (\Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');
        }
    }

    public function confirmDelete(int $id): void
    {
        if (! auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'delete')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menghapus Pesanan Awal.', type: 'error');

            return;
        }

        $preOrder = PreOrderModel::findOrFail($id);
        $this->authorizePreOrder($preOrder);
        if ($preOrder->status !== PreOrderModel::STATUS_DRAFT || $preOrder->dpAllocations()->whereHas('payment', fn ($query) => $query->where('status', ArDpPayment::STATUS_POSTED))->exists()) {
            $this->dispatch('toast', message: 'Pesanan Awal yang sudah diproses tidak dapat dihapus.', type: 'error');

            return;
        }
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'delete')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menghapus Pesanan Awal.', type: 'error');

            return;
        }
        if (! $this->deleteTargetId) {
            return;
        }
        $preOrder = PreOrderModel::findOrFail($this->deleteTargetId);
        if ($preOrder->status !== PreOrderModel::STATUS_DRAFT || $preOrder->dpAllocations()->whereHas('payment', fn ($query) => $query->where('status', ArDpPayment::STATUS_POSTED))->exists()) {
            $this->dispatch('toast', message: 'Pesanan Awal yang sudah diproses tidak dapat dihapus.', type: 'error');

            return;
        }

        $preOrder->delete();
        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Pesanan Awal berhasil dihapus.', type: 'success');
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'delete'), 403);
        PreOrderModel::onlyTrashed()->findOrFail($id)->restore();
        $this->dispatch('toast', message: 'Pesanan Awal berhasil dipulihkan.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $preOrder = PreOrderModel::withTrashed()->with([
            'customer', 'customerAddress', 'creator', 'items.product', 'items.warehouse', 'items.unit',
            'dpAllocations' => fn ($query) => $query->whereHas('payment', fn ($payment) => $payment->where('status', ArDpPayment::STATUS_POSTED))->with('payment')->orderBy('id'),
            'salesOrder',
        ])->findOrFail($id);
        $this->authorizePreOrder($preOrder);
        $this->selectedPreOrder = $preOrder;
        $this->showDetailModal = true;
    }

    public function totals(): array
    {
        $subtotal = collect($this->items)->sum(fn (array $item) => (int) ($item['qty'] ?? 0) * (int) ($item['unit_price'] ?? 0));
        $discount = collect($this->items)->sum(fn (array $item) => (int) ($item['discount_amount'] ?? 0));
        $taxable = max(0, $subtotal - $discount);
        $tax = $this->tax ? (int) round($taxable * 0.11) : 0;

        return compact('subtotal', 'discount', 'tax') + ['grand_total' => $taxable + $tax];
    }

    private function authorizePreOrder(PreOrderModel $preOrder): void
    {
        abort_unless(auth()->user()?->isSuperAdmin() || $this->canManagePreOrders() || $preOrder->created_by === Auth::id(), 403);
    }

    private function canManagePreOrders(): bool
    {
        $user = auth()->user();

        return $user?->canPerform('sales.transaction.salesPreOrder', 'confirm')
            || $user?->canPerform('sales.transaction.salesPreOrder', 'convert')
            || $user?->canPerform('sales.transaction.salesPreOrder', 'delete');
    }

    private function makeItem(Product $product, ?int $warehouseId = null, ?int $unitId = null, int $qty = 1, ?int $unitPrice = null, int $discountAmount = 0): array
    {
        $product->loadMissing(['prices.unit', 'baseUnit']);
        $price = $product->prices->firstWhere('unit_id', $unitId) ?? $product->prices->first();
        $warehouseId ??= Warehouse::query()->orderBy('id')->value('id');
        $stock = $warehouseId ? app(AvailableForSalesService::class)->available($product->id, $warehouseId) : 0;

        return [
            'product_id' => $product->id, 'sku' => $product->name, 'name' => $product->sku,
            'warehouse_id' => $warehouseId, 'unit_id' => $price?->unit_id, 'conversion' => (int) ($price?->conversion ?? 1),
            'qty' => $qty, 'unit_price' => $unitPrice ?? (int) ($price?->price ?? 0), 'discount_amount' => $discountAmount,
            'stock_available' => $stock, 'stock_available_display' => app(StockQuantityFormatter::class)->format($product, $stock), 'base_unit_name' => $product->baseUnit?->name ?? '-',
            'unit_options' => $product->prices->map(fn ($item) => ['unit_id' => $item->unit_id, 'unit_name' => $item->unit?->name ?? '-', 'unit_code' => $item->unit?->code, 'conversion' => (int) $item->conversion])->values()->all(),
        ];
    }

    private function refreshItemStock(int $index): void
    {
        $item = $this->items[$index];
        $stock = $item['warehouse_id'] ? app(AvailableForSalesService::class)->available((int) $item['product_id'], (int) $item['warehouse_id']) : 0;
        $this->items[$index]['stock_available'] = $stock;
        $this->items[$index]['stock_available_display'] = app(StockQuantityFormatter::class)->formatUnits(
            $stock,
            collect($item['unit_options'])->map(fn (array $unit) => [
                'conversion' => $unit['conversion'], 'code' => $unit['unit_code'] ?? null, 'name' => $unit['unit_name'],
            ]),
        );
    }

    private function resetForm(): void
    {
        $this->reset(['showModal', 'showProductModal', 'showDeleteModal', 'showConvertModal', 'showConfirmModal', 'editingId', 'deleteTargetId', 'convertTargetId', 'confirmTargetId', 'preOrderNo', 'customerId', 'customerAddressId', 'tax', 'dpAmount', 'notes', 'items', 'productSearch', 'categoryFilter', 'selectedProductIds']);
        $this->date = now()->format('Y-m-d');
        $this->resetErrorBag();
    }

    private function generateCode(): string
    {
        $prefix = 'PRE-'.now()->format('ymd').'-';
        $last = PreOrderModel::withTrashed()->where('pre_order_no', 'like', $prefix.'%')->orderByDesc('pre_order_no')->value('pre_order_no');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $preOrders = PreOrderModel::query()
            ->with(['customer', 'salesOrder'])
            ->withSum(['dpAllocations as posted_dp_amount' => fn ($query) => $query->whereHas('payment', fn ($payment) => $payment->where('status', ArDpPayment::STATUS_POSTED))], 'amount')
            ->when(! auth()->user()->isSuperAdmin() && ! $this->canManagePreOrders(), fn (Builder $query) => $query->where('created_by', Auth::id()))
            ->when($this->showTrashed, fn (Builder $query) => $query->withTrashed())
            ->when($this->statusFilter !== '', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('date', '<=', $this->dateTo))
            ->when($this->search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('pre_order_no', 'like', '%'.$this->search.'%')->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%'.$this->search.'%'))))
            ->latest('date')->latest('id')->paginate($this->perPage);

        return view('livewire.sales.transaction.pre-order', [
            'preOrders' => $preOrders,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'customerAddresses' => CustomerAddress::where('customer_id', $this->customerId)->orderByDesc('is_primary')->orderBy('label')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'products' => Product::with('category')->whereHas('prices')
                ->when($this->productSearch, fn (Builder $query) => $query->where(fn (Builder $product) => $product->where('name', 'like', '%'.$this->productSearch.'%')->orWhere('sku', 'like', '%'.$this->productSearch.'%')))
                ->when($this->categoryFilter, fn (Builder $query) => $query->where('category_id', $this->categoryFilter))
                ->orderBy('name')->limit(50)->get(),
            'categories' => ProductCategory::orderBy('name')->get(),
            'totals' => $this->totals(),
        ]);
    }
}
