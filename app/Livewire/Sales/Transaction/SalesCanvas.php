<?php

namespace App\Livewire\Sales\Transaction;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\SalesCanvas as SalesCanvasModel;
use App\Models\Salesman;
use App\Models\SalesOrder as SalesOrderModel;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class SalesCanvas extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $statusFilter = '';

    public int $perPage = 10;

    public string $sortField = 'date';

    public string $sortDirection = 'desc';

    public bool $showTrashed = false;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public bool $showConvertModal = false;

    public bool $showProductModal = false;

    public bool $showDetailModal = false;

    public ?int $editingId = null;

    public ?int $deleteTargetId = null;

    public ?int $convertTargetId = null;

    public ?SalesCanvasModel $selectedCanvas = null;

    public string $canvasNo = '';

    public string $date = '';

    public ?int $salesmanId = null;

    public ?int $customerId = null;

    public ?int $customerAddressId = null;

    public bool $tax = false;

    public string $notes = '';

    public string $status = 'draft';

    public array $items = [];

    public string $productSearch = '';

    public ?int $categoryFilter = null;

    public array $selectedProductIds = [];

    protected function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'salesmanId' => [
                'required',
                'integer',
                Rule::exists('salesmen', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at')),
            ],
            'customerId' => ['required', 'integer', 'exists:customers,id'],
            'customerAddressId' => [
                'nullable',
                'integer',
                Rule::exists('customer_addresses', 'id')->where(
                    fn ($query) => $query->where('customer_id', $this->customerId)->whereNull('deleted_at')
                ),
            ],
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
        'salesmanId.required' => 'Salesman wajib ditentukan.',
        'salesmanId.exists' => 'Salesman tidak aktif atau tidak ditemukan.',
        'customerId.required' => 'Customer wajib dipilih.',
        'customerAddressId.exists' => 'Alamat harus berasal dari customer yang dipilih.',
        'items.required' => 'Tambahkan minimal satu produk.',
        'items.min' => 'Tambahkan minimal satu produk.',
        'items.*.warehouse_id.required' => 'Gudang wajib dipilih.',
        'items.*.unit_id.required' => 'Satuan wajib dipilih.',
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

    public function updatingShowTrashed(): void
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

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo', 'showTrashed']);
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['canvas_no', 'date', 'grand_total', 'status'], true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->canvasNo = $this->generateCode();

        $salesman = $this->currentSalesman();

        if (auth()->user()->isSuperAdmin()) {
            if ($salesman) {
                $this->salesmanId = $salesman->id;
                $this->applySalesmanDefaults($salesman);
            }

            $this->showModal = true;

            return;
        }

        if (! $salesman) {
            $this->dispatch('toast', message: 'Hanya salesman aktif yang dapat membuat penjualan kanvas.', type: 'error');

            return;
        }

        $this->salesmanId = $salesman->id;
        $this->applySalesmanDefaults($salesman);

        $this->showModal = true;
    }

    public function updatedSalesmanId(): void
    {
        if (! auth()->user()->isSuperAdmin() || ! $this->salesmanId) {
            return;
        }

        $salesman = Salesman::where('is_active', true)->find($this->salesmanId);

        if ($salesman) {
            $this->applySalesmanDefaults($salesman);
        }
    }

    public function openEdit(int $id): void
    {
        $canvas = SalesCanvasModel::with(['items.product.prices.unit', 'items.warehouse'])->findOrFail($id);
        $this->authorizeCanvas($canvas);

        if ($canvas->status !== 'draft') {
            $this->dispatch('toast', message: 'Transaksi yang sudah diposting tidak dapat diubah.', type: 'error');

            return;
        }

        $this->editingId = $canvas->id;
        $this->canvasNo = $canvas->canvas_no;
        $this->date = $canvas->date->format('Y-m-d');
        $this->salesmanId = $canvas->salesman_id;
        $this->customerId = $canvas->customer_id;
        $this->customerAddressId = $canvas->customer_address_id;
        $this->tax = $canvas->is_taxed;
        $this->notes = $canvas->notes ?? '';
        $this->status = $canvas->status;
        $this->items = $canvas->items->map(fn ($item) => $this->makeItem(
            $item->product,
            $item->warehouse_id,
            $item->unit_id,
            $item->qty,
            $item->unit_price,
            $item->discount_amount,
        ))->toArray();
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function updatedCustomerId(): void
    {
        $this->customerAddressId = null;
    }

    public function openProductPicker(): void
    {
        $this->reset(['productSearch', 'categoryFilter', 'selectedProductIds']);
        $this->showProductModal = true;
    }

    public function addSelectedProducts(): void
    {
        if ($this->selectedProductIds === []) {
            $this->dispatch('toast', message: 'Pilih produk terlebih dahulu.', type: 'error');

            return;
        }

        foreach ($this->selectedProductIds as $productId) {
            $this->addProduct((int) $productId);
        }

        $this->showProductModal = false;
        $this->reset(['selectedProductIds', 'productSearch', 'categoryFilter']);
    }

    public function addProduct(int $productId): void
    {
        if (collect($this->items)->contains(fn (array $item) => (int) $item['product_id'] === $productId)) {
            return;
        }

        $product = Product::with('prices.unit')->findOrFail($productId);

        if ($product->prices->isEmpty()) {
            $this->dispatch('toast', message: "Produk {$product->name} belum memiliki harga jual.", type: 'error');

            return;
        }

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
            $price = ProductPrice::where('product_id', $this->items[$index]['product_id'])
                ->where('unit_id', $value)
                ->first();

            $this->items[$index]['conversion'] = (int) ($price?->conversion ?? 1);
            $this->items[$index]['unit_price'] = (int) ($price?->price ?? 0);
        }

        if (in_array($field, ['warehouse_id', 'unit_id'], true)) {
            $this->refreshItemStock((int) $index);
        }
    }

    public function save(): void
    {
        if ($this->editingId) {
            $canvas = SalesCanvasModel::findOrFail($this->editingId);
            $this->authorizeCanvas($canvas);
            $this->salesmanId = $canvas->salesman_id;
        } elseif (! auth()->user()->isSuperAdmin()) {
            $salesman = $this->currentSalesman();

            if (! $salesman) {
                $this->dispatch('toast', message: 'Hanya salesman aktif yang dapat membuat penjualan kanvas.', type: 'error');

                return;
            }

            $this->salesmanId = $salesman->id;
        }

        $this->validate();
        $this->validateItemAmounts();

        DB::transaction(function () {
            $canvas = $this->editingId
                ? SalesCanvasModel::findOrFail($this->editingId)
                : new SalesCanvasModel;

            if ($canvas->exists) {
                $this->authorizeCanvas($canvas);

                if ($canvas->status !== 'draft') {
                    throw ValidationException::withMessages(['status' => 'Transaksi posted tidak dapat diubah.']);
                }
            }

            $totals = $this->totals();
            $canvas->fill([
                'canvas_no' => $canvas->exists ? $canvas->canvas_no : $this->generateCode(),
                'date' => $this->date,
                'salesman_id' => $this->salesmanId,
                'customer_id' => $this->customerId,
                'customer_address_id' => $this->customerAddressId,
                'is_taxed' => $this->tax,
                'tax_rate' => 11,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'tax_amount' => $totals['tax'],
                'grand_total' => $totals['grand_total'],
                'notes' => trim($this->notes) ?: null,
                'status' => 'draft',
                'created_by' => $canvas->exists ? $canvas->created_by : Auth::id(),
            ]);
            $canvas->save();
            $canvas->items()->delete();

            foreach ($this->items as $item) {
                $gross = (int) $item['qty'] * (int) $item['unit_price'];
                $canvas->items()->create([
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
        $this->dispatch('toast', message: 'Penjualan kanvas berhasil disimpan sebagai draf.', type: 'success');
    }

    public function confirmConvertToSalesOrder(int $id): void
    {
        $canvas = SalesCanvasModel::findOrFail($id);
        $this->authorizeCanvas($canvas);

        if ($canvas->status !== 'draft') {
            $this->dispatch('toast', message: 'Hanya penjualan kanvas berstatus draf yang dapat dijadikan Sales Order.', type: 'error');

            return;
        }

        $this->convertTargetId = $id;
        $this->showConvertModal = true;
    }

    public function convertToSalesOrder(): void
    {
        if (! $this->convertTargetId) {
            return;
        }

        try {
            DB::transaction(function () {
                $canvas = SalesCanvasModel::with('items')->lockForUpdate()->findOrFail($this->convertTargetId);
                $this->authorizeCanvas($canvas);

                if ($canvas->status !== 'draft') {
                    throw new \RuntimeException('Penjualan kanvas sudah diproses.');
                }

                $salesOrder = SalesOrderModel::create([
                    'order_no' => 'SO-'.$canvas->date->format('ymd').'-'.str_pad((string) $canvas->id, 5, '0', STR_PAD_LEFT),
                    'date' => $canvas->date,
                    'sales_canvas_id' => $canvas->id,
                    'customer_id' => $canvas->customer_id,
                    'customer_address_id' => $canvas->customer_address_id,
                    'is_taxed' => $canvas->is_taxed,
                    'tax_rate' => $canvas->tax_rate,
                    'subtotal' => $canvas->subtotal,
                    'discount_total' => $canvas->discount_total,
                    'tax_amount' => $canvas->tax_amount,
                    'grand_total' => $canvas->grand_total,
                    'dp_amount' => 0,
                    'amount_due' => $canvas->grand_total,
                    'notes' => $canvas->notes,
                    'status' => 'draft',
                    'created_by' => Auth::id(),
                ]);

                foreach ($canvas->items as $item) {
                    $salesOrder->items()->create([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $item->warehouse_id,
                        'unit_id' => $item->unit_id,
                        'qty' => $item->qty,
                        'conversion' => $item->conversion,
                        'unit_price' => $item->unit_price,
                        'discount_amount' => $item->discount_amount,
                        'line_total' => $item->line_total,
                    ]);
                }

                $canvas->update(['status' => 'sales_order']);
            });

            $this->showConvertModal = false;
            $this->convertTargetId = null;
            $this->dispatch('toast', message: 'Penjualan kanvas berhasil dijadikan Sales Order.', type: 'success');
        } catch (\Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');
        }
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

        $canvas = SalesCanvasModel::findOrFail($this->deleteTargetId);

        if ($canvas->status !== 'draft') {
            $this->dispatch('toast', message: 'Transaksi posted tidak dapat dihapus.', type: 'error');

            return;
        }

        $canvas->delete();
        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Penjualan kanvas berhasil dihapus.', type: 'success');
    }

    public function restore(int $id): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat memulihkan data.', type: 'error');

            return;
        }

        SalesCanvasModel::onlyTrashed()->findOrFail($id)->restore();
        $this->dispatch('toast', message: 'Penjualan kanvas berhasil dipulihkan.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $canvas = SalesCanvasModel::withTrashed()->with([
            'salesman',
            'customer',
            'customerAddress',
            'items.product',
            'items.warehouse',
            'items.unit',
            'creator',
        ])->findOrFail($id);
        $this->authorizeCanvas($canvas);
        $this->selectedCanvas = $canvas;
        $this->showDetailModal = true;
    }

    public function totals(): array
    {
        $subtotal = collect($this->items)->sum(fn (array $item) => (int) ($item['qty'] ?? 0) * (int) ($item['unit_price'] ?? 0));
        $discount = collect($this->items)->sum(fn (array $item) => (int) ($item['discount_amount'] ?? 0));
        $taxable = max(0, $subtotal - $discount);
        $tax = $this->tax ? (int) round($taxable * 0.11) : 0;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'grand_total' => $taxable + $tax,
        ];
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
        $selectedPrice = $product->prices->firstWhere('unit_id', $unitId) ?? $product->prices->first();
        $warehouseId ??= Warehouse::query()->orderBy('id')->value('id');
        $stock = $warehouseId
            ? (int) (StockBalance::where('warehouse_id', $warehouseId)->where('product_id', $product->id)->value('quantity') ?? 0)
            : 0;

        return [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'warehouse_id' => $warehouseId,
            'unit_id' => $selectedPrice?->unit_id,
            'conversion' => (int) ($selectedPrice?->conversion ?? 1),
            'qty' => $qty,
            'unit_price' => $unitPrice ?? (int) ($selectedPrice?->price ?? 0),
            'discount_amount' => $discountAmount,
            'stock_available' => $stock,
            'base_unit_name' => $product->baseUnit?->name ?? '-',
            'unit_options' => $product->prices->map(fn ($price) => [
                'unit_id' => $price->unit_id,
                'unit_name' => $price->unit?->name ?? '-',
                'conversion' => (int) $price->conversion,
                'price' => (int) $price->price,
            ])->values()->all(),
        ];
    }

    private function refreshItemStock(int $index): void
    {
        $item = $this->items[$index];
        $this->items[$index]['stock_available'] = $item['warehouse_id']
            ? (int) (StockBalance::where('warehouse_id', $item['warehouse_id'])
                ->where('product_id', $item['product_id'])
                ->value('quantity') ?? 0)
            : 0;
    }

    private function validateItemAmounts(): void
    {
        $errors = [];

        foreach ($this->items as $index => $item) {
            $gross = (int) $item['qty'] * (int) $item['unit_price'];

            if ((int) $item['discount_amount'] > $gross) {
                $errors["items.{$index}.discount_amount"] = 'Diskon tidak boleh melebihi nilai produk.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function applySalesmanDefaults(Salesman $salesman): void
    {
        $this->customerId = $salesman->default_customer_id;
        $this->customerAddressId = $salesman->default_customer_address_id;
    }

    private function currentSalesman(): ?Salesman
    {
        return auth()->user()?->salesman()->where('is_active', true)->first();
    }

    private function authorizeCanvas(SalesCanvasModel $canvas): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            return;
        }

        abort_unless($this->currentSalesman()?->id === $canvas->salesman_id, 403);
    }

    private function resetForm(): void
    {
        $this->reset([
            'showModal',
            'showDeleteModal',
            'showProductModal',
            'showDetailModal',
            'editingId',
            'deleteTargetId',
            'selectedCanvas',
            'canvasNo',
            'salesmanId',
            'customerId',
            'customerAddressId',
            'tax',
            'notes',
            'status',
            'items',
            'productSearch',
            'categoryFilter',
            'selectedProductIds',
        ]);
        $this->date = now()->format('Y-m-d');
        $this->status = 'draft';
        $this->resetErrorBag();
    }

    private function generateCode(): string
    {
        $prefix = 'SC-'.now()->format('dmy').'-';
        $lastCode = SalesCanvasModel::withTrashed()
            ->where('canvas_no', 'like', $prefix.'%')
            ->orderByDesc('canvas_no')
            ->value('canvas_no');
        $sequence = $lastCode ? (int) substr($lastCode, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $currentSalesman = $this->currentSalesman();
        $salesCanvases = SalesCanvasModel::query()
            ->with(['salesman', 'customer', 'creator'])
            ->when(! auth()->user()->isSuperAdmin(), fn (Builder $query) => $query->where('salesman_id', $currentSalesman?->id ?? 0))
            ->when($this->showTrashed, fn (Builder $query) => $query->withTrashed())
            ->when($this->statusFilter !== '', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->dateFrom !== '', fn (Builder $query) => $query->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn (Builder $query) => $query->whereDate('date', '<=', $this->dateTo))
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('canvas_no', 'like', '%'.$this->search.'%')
                        ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%'.$this->search.'%'))
                        ->orWhereHas('salesman', fn (Builder $salesman) => $salesman->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.sales.transaction.sales-canvas', [
            'salesCanvases' => $salesCanvases,
            'salesmen' => Salesman::where('is_active', true)->orderBy('name')->get(),
            'selectedSalesman' => Salesman::withTrashed()->find($this->salesmanId),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'customerAddresses' => CustomerAddress::where('customer_id', $this->customerId)->orderByDesc('is_primary')->orderBy('label')->get(),
            'warehouses' => Warehouse::orderBy('name')->get(),
            'products' => Product::with('category')
                ->whereHas('prices')
                ->when($this->productSearch, function (Builder $query) {
                    $query->where(fn (Builder $product) => $product
                        ->where('name', 'like', '%'.$this->productSearch.'%')
                        ->orWhere('sku', 'like', '%'.$this->productSearch.'%'));
                })
                ->when($this->categoryFilter, fn (Builder $query) => $query->where('category_id', $this->categoryFilter))
                ->orderBy('name')
                ->limit(50)
                ->get(),
            'categories' => ProductCategory::orderBy('name')->get(),
            'totals' => $this->totals(),
        ]);
    }
}
