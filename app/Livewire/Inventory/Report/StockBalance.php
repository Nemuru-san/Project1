<?php

namespace App\Livewire\Inventory\Report;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockBalance as StockBalanceModel;
use App\Models\Warehouse;
use App\Services\Inventory\AvailableForSalesService;
use App\Services\Inventory\StockQuantityFormatter;
use Livewire\Component;
use Livewire\WithPagination;

class StockBalance extends Component
{
    use WithPagination;

    public string $search = '';

    public string $warehouseFilter = '';

    public string $categoryFilter = '';

    public string $productFilter = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showZeroBalance = false;

    public bool $showDetailModal = false;

    public ?array $selectedStock = null;

    public array $stockBookings = [];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingProductFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingShowZeroBalance(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $allowedFields = [
            'sku',
            'name',
            'category',
            'warehouse',
            'quantity',
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

    public function formatStockQuantity(?Product $product, int $quantity): string
    {
        return app(StockQuantityFormatter::class)->format($product, $quantity);
    }

    public function openDetail(int $productId, int|string $warehouseId = ''): void
    {
        $product = Product::with(['category', 'prices.unit'])->findOrFail($productId);
        $warehouse = $warehouseId ? Warehouse::find($warehouseId) : null;
        $availability = $warehouse
            ? app(AvailableForSalesService::class)->summary($product->id, $warehouse->id)
            : ['quantity_on_hand' => 0, 'reserved' => 0, 'available_for_sales' => 0];

        $this->selectedStock = [
            'product_sku' => $product->sku,
            'product_name' => $product->name,
            'category_name' => $product->category?->name ?? '-',
            'warehouse_name' => $warehouse?->name ?? '-',
            ...$availability,
            'quantity_on_hand_display' => app(StockQuantityFormatter::class)->format($product, $availability['quantity_on_hand']),
            'available_for_sales_display' => app(StockQuantityFormatter::class)->format($product, $availability['available_for_sales']),
        ];

        $this->stockBookings = $warehouse
            ? app(AvailableForSalesService::class)->bookings($product->id, $warehouse->id)->all()
            : [];

        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedStock = null;
        $this->stockBookings = [];
    }

    public function render(AvailableForSalesService $availabilityService)
    {
        $warehouses = Warehouse::query()
            ->orderBy('name')
            ->get();

        $categories = ProductCategory::query()
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->orderBy('name')
            ->get();

        if ($this->showZeroBalance && $this->warehouseFilter) {
            $query = Product::query()
                ->with([
                    'category',
                    'prices.unit',
                    'stockBalances' => function ($q) {
                        $q->where('warehouse_id', $this->warehouseFilter);
                    },
                ]);

            if ($this->categoryFilter) {
                $query->where('category_id', $this->categoryFilter);
            }

            if ($this->productFilter) {
                $query->where('id', $this->productFilter);
            }

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('sku', 'like', '%'.$this->search.'%')
                        ->orWhere('name', 'like', '%'.$this->search.'%')
                        ->orWhereHas('category', function ($categoryQuery) {
                            $categoryQuery->where('name', 'like', '%'.$this->search.'%');
                        });
                });
            }

            if ($this->sortField === 'sku') {
                $query->orderBy('sku', $this->sortDirection);
            } elseif ($this->sortField === 'name') {
                $query->orderBy('name', $this->sortDirection);
            } else {
                $query->orderBy('name', 'asc');
            }

            $stockBalances = $query->paginate($this->perPage);
            $stockSummaries = $availabilityService->summaries(
                $stockBalances->getCollection()->map(fn (Product $product) => [
                    'product_id' => $product->id,
                    'warehouse_id' => (int) $this->warehouseFilter,
                ]),
            );

            return view('livewire.inventory.report.stock-balance', [
                'stockBalances' => $stockBalances,
                'warehouses' => $warehouses,
                'categories' => $categories,
                'products' => $products,
                'selectedWarehouse' => Warehouse::find($this->warehouseFilter),
                'isZeroMode' => true,
                'stockSummaries' => $stockSummaries,
            ]);
        }

        $query = StockBalanceModel::query()
            ->with([
                'warehouse',
                'product.category',
                'product.prices.unit',
            ]);

        if ($this->warehouseFilter) {
            $query->where('warehouse_id', $this->warehouseFilter);
        }

        if ($this->productFilter) {
            $query->where('product_id', $this->productFilter);
        }

        if ($this->categoryFilter) {
            $query->whereHas('product', function ($productQuery) {
                $productQuery->where('category_id', $this->categoryFilter);
            });
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('product', function ($productQuery) {
                    $productQuery->where('sku', 'like', '%'.$this->search.'%')
                        ->orWhere('name', 'like', '%'.$this->search.'%');
                })
                    ->orWhereHas('product.category', function ($categoryQuery) {
                        $categoryQuery->where('name', 'like', '%'.$this->search.'%');
                    })
                    ->orWhereHas('warehouse', function ($warehouseQuery) {
                        $warehouseQuery->where('name', 'like', '%'.$this->search.'%');
                    });
            });
        }

        if ($this->sortField === 'quantity') {
            $query->orderBy('quantity', $this->sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $stockBalances = $query->paginate($this->perPage);
        $stockSummaries = $availabilityService->summaries(
            $stockBalances->getCollection()->map(fn (StockBalanceModel $balance) => [
                'product_id' => $balance->product_id,
                'warehouse_id' => $balance->warehouse_id,
            ]),
        );

        return view('livewire.inventory.report.stock-balance', [
            'stockBalances' => $stockBalances,
            'warehouses' => $warehouses,
            'categories' => $categories,
            'products' => $products,
            'selectedWarehouse' => null,
            'isZeroMode' => false,
            'stockSummaries' => $stockSummaries,
        ]);
    }
}
