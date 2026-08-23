<?php

namespace App\Livewire\Inventory\InventoryTransaction;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AdjustmentOut extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showTrashed = false;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public bool $showApproveModal = false;

    public bool $showDetail = false;

    public ?int $deleteTargetId = null;

    public ?int $approveTargetId = null;

    public ?int $editingId = null;

    public ?StockAdjustment $selectedAdjustment = null;

    public string $adjustment_no = '';

    public string $date = '';

    public ?int $warehouse_id = null;

    public ?string $notes = null;

    public string $status = 'draft';

    public array $items = [];

    public string $productSearch = '';

    public ?int $categoryFilter = null;

    public array $selectedProductIds = [];

    protected function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'exists:product_units,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

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

    public function sortBy(string $field): void
    {
        $this->sortDirection = $this->sortField === $field
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';

        $this->sortField = $field;
    }

    public function openCreate(): void
    {
        $this->resetForm();

        $this->adjustment_no = $this->generateCode();
        $this->date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $adjustment = StockAdjustment::with(['items.product', 'items.unit'])
            ->where('type', 'out')
            ->findOrFail($id);

        if ($adjustment->status !== 'draft') {
            $this->dispatch('toast', message: 'Penyesuaian yang sudah disetujui tidak dapat diubah.', type: 'error');

            return;
        }

        $this->editingId = $adjustment->id;
        $this->adjustment_no = $adjustment->adjustment_no;
        $this->date = $adjustment->date->format('Y-m-d');
        $this->warehouse_id = $adjustment->warehouse_id;
        $this->notes = $adjustment->notes;
        $this->status = $adjustment->status;

        $this->items = $adjustment->items->map(function ($item) {
            $product = Product::with('prices.unit')->find($item->product_id);

            $stockAvailable = StockBalance::where('product_id', $item->product_id)
                ->where('warehouse_id', $this->warehouse_id)
                ->value('quantity') ?? 0;

            return [
                'product_id' => $item->product_id,
                'sku' => $item->product?->name,
                'name' => $item->product?->sku,
                'stock_available' => $stockAvailable,
                'qty' => (int) $item->qty,
                'unit_id' => $item->unit_id,
                'conversion' => (int) $item->conversion,

                'unit_options' => $product?->prices
                    ->map(fn ($price) => [
                        'unit_id' => $price->unit_id,
                        'unit_name' => $price->unit?->name ?? '-',
                        'conversion' => $price->conversion,
                    ])
                    ->values()
                    ->toArray() ?? [],
            ];
        })->toArray();

        $this->showModal = true;
    }

    public function addSelectedProducts(): void
    {
        if (empty($this->selectedProductIds)) {
            $this->dispatch('toast', message: 'Pilih produk terlebih dahulu.', type: 'error');

            return;
        }

        foreach ($this->selectedProductIds as $productId) {
            $this->addProduct((int) $productId);
        }

        $this->selectedProductIds = [];

        $this->dispatch('toast', message: 'Produk berhasil ditambahkan.', type: 'success');
    }

    public function addProduct(int $productId): void
    {
        if (collect($this->items)->contains(fn ($item) => (int) $item['product_id'] === $productId)) {
            $this->dispatch('toast', message: 'Produk sudah ada di detail.', type: 'error');

            return;
        }

        $product = Product::with('prices.unit')->findOrFail($productId);
        $defaultPrice = $product->prices->first();

        $stockAvailable = 0;

        if ($this->warehouse_id) {
            $stockAvailable = StockBalance::where('product_id', $productId)
                ->where('warehouse_id', $this->warehouse_id)
                ->value('quantity') ?? 0;
        }

        $this->items[] = [
            'product_id' => $product->id,
            'sku' => $product->name,
            'name' => $product->sku,
            'stock_available' => $stockAvailable,
            'qty' => $stockAvailable > 0 ? 1 : 0,
            'unit_id' => $defaultPrice?->unit_id,
            'conversion' => $defaultPrice?->conversion ?? 1,

            'unit_options' => $product->prices
                ->map(fn ($price) => [
                    'unit_id' => $price->unit_id,
                    'unit_name' => $price->unit?->name ?? '-',
                    'conversion' => $price->conversion,
                ])
                ->values()
                ->toArray(),
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedWarehouseId(): void
    {
        foreach ($this->items as $index => $item) {
            $stockAvailable = StockBalance::where('product_id', $item['product_id'])
                ->where('warehouse_id', $this->warehouse_id)
                ->value('quantity') ?? 0;

            $this->items[$index]['stock_available'] = $stockAvailable;

            if ($stockAvailable <= 0) {
                $this->items[$index]['qty'] = 0;
            } elseif (($this->items[$index]['qty'] ?? 0) <= 0) {
                $this->items[$index]['qty'] = 1;
            }
        }
    }

    public function updatedItems($value, string $key): void
    {
        if (! str_ends_with($key, '.unit_id')) {
            return;
        }

        [$index] = explode('.', $key);

        $productId = $this->items[$index]['product_id'] ?? null;

        if (! $productId || ! $value) {
            return;
        }

        $price = ProductPrice::where('product_id', $productId)
            ->where('unit_id', $value)
            ->first();

        $this->items[$index]['conversion'] = $price?->conversion ?? 1;
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $adjustment = StockAdjustment::updateOrCreate(
                ['id' => $this->editingId],
                [
                    'adjustment_no' => $this->editingId ? $this->adjustment_no : $this->generateCode(),
                    'date' => $this->date,
                    'type' => 'out',
                    'warehouse_id' => $this->warehouse_id,
                    'notes' => $this->notes,
                    'status' => 'draft',
                    'created_by' => Auth::id(),
                ]
            );

            $adjustment->items()->delete();

            foreach ($this->items as $item) {
                $adjustment->items()->create([
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'qty' => (int) $item['qty'],
                    'conversion' => $item['conversion'] ?? 1,
                ]);
            }
        });

        $this->resetForm();

        $this->dispatch('toast', message: 'Penyesuaian stok keluar berhasil disimpan.', type: 'success');
    }

    public function confirmApprove(int $id): void
    {
        if (! auth()->user()?->hasPermission('inventory.transaction.adjustment-out.approve')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menyetujui Penyesuaian Stok Keluar.', type: 'error');

            return;
        }

        $this->approveTargetId = $id;
        $this->showApproveModal = true;
    }

    public function cancelApprove(): void
    {
        $this->approveTargetId = null;
        $this->showApproveModal = false;
    }

    public function approve(): void
    {
        if (! auth()->user()?->hasPermission('inventory.transaction.adjustment-out.approve')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menyetujui Penyesuaian Stok Keluar.', type: 'error');

            return;
        }

        if (! $this->approveTargetId) {
            return;
        }

        try {
            DB::transaction(function () {
                $adjustment = StockAdjustment::with('items')
                    ->where('type', 'out')
                    ->findOrFail($this->approveTargetId);

                if ($adjustment->status !== 'draft') {
                    return;
                }

                foreach ($adjustment->items as $item) {
                    $qtyBase = $item->qty * $item->conversion;

                    $stock = StockBalance::where('warehouse_id', $adjustment->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $stock || $stock->quantity < $qtyBase) {
                        throw new \Exception('Stock tidak cukup untuk salah satu produk.');
                    }

                    $stock->decrement('quantity', $qtyBase);
                }

                $adjustment->update([
                    'status' => 'approved',
                ]);
            });

            $this->showApproveModal = false;
            $this->approveTargetId = null;

            $this->dispatch('toast', message: 'Penyesuaian stok keluar berhasil disetujui.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function confirmDelete(int $id): void
    {
        if (! auth()->user()?->hasPermission('inventory.transaction.adjustment-out.delete')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menghapus Penyesuaian Stok Keluar.', type: 'error');

            return;
        }

        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! auth()->user()?->hasPermission('inventory.transaction.adjustment-out.delete')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menghapus Penyesuaian Stok Keluar.', type: 'error');

            return;
        }

        if (! $this->deleteTargetId) {
            return;
        }

        $adjustment = StockAdjustment::where('type', 'out')->findOrFail($this->deleteTargetId);

        if ($adjustment->status !== 'draft') {
            $this->dispatch('toast', message: 'Penyesuaian yang sudah disetujui tidak dapat dihapus.', type: 'error');

            return;
        }

        $adjustment->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'Penyesuaian stok keluar berhasil dihapus.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $this->selectedAdjustment = StockAdjustment::with([
            'warehouse',
            'creator',
            'items.product',
            'items.unit',
        ])
            ->where('type', 'out')
            ->withTrashed()
            ->findOrFail($id);

        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->selectedAdjustment = null;
    }

    private function resetForm(): void
    {
        $this->reset([
            'showModal',
            'showDeleteModal',
            'showApproveModal',
            'showDetail',
            'deleteTargetId',
            'approveTargetId',
            'editingId',
            'selectedAdjustment',
            'adjustment_no',
            'warehouse_id',
            'notes',
            'status',
            'items',
            'productSearch',
            'categoryFilter',
            'selectedProductIds',
        ]);

        $this->date = now()->format('Y-m-d');
        $this->status = 'draft';
    }

    private function generateCode(): string
    {
        $date = now()->format('dmy');
        $prefix = "ADO-{$date}-";

        $last = StockAdjustment::withTrashed()
            ->where('type', 'out')
            ->where('adjustment_no', 'like', $prefix.'%')
            ->orderByDesc('adjustment_no')
            ->value('adjustment_no');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $adjustments = StockAdjustment::with('warehouse')
            ->where('type', 'out')
            ->when($this->showTrashed, fn ($q) => $q->withTrashed())
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('adjustment_no', 'like', '%'.$this->search.'%')
                        ->orWhereHas('warehouse', fn ($w) => $w->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.inventory.inventory-transaction.adjustment-out', [
            'adjustments' => $adjustments,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'units' => ProductUnit::orderBy('name')->get(),
            'products' => Product::with('category')
                ->when($this->productSearch, function ($q) {
                    $q->where(function ($query) {
                        $query->where('name', 'like', '%'.$this->productSearch.'%')
                            ->orWhere('sku', 'like', '%'.$this->productSearch.'%');
                    });
                })
                ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
                ->limit(20)
                ->get(),
            'categories' => ProductCategory::orderBy('name')->get(),
        ]);
    }
}
