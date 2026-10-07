<?php

namespace App\Livewire\Inventory\Report;

use App\Models\ProductCategory;
use App\Models\Warehouse;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Nilai persediaan = qty stok saat ini × harga pokok rata-rata produk.
 */
class StockValuation extends Component
{
    use WithPagination;

    public string $search = '';

    public string $warehouseFilter = '';

    public string $categoryFilter = '';

    public bool $showZeroBalance = false;

    public int $perPage = 25;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'warehouseFilter', 'categoryFilter', 'showZeroBalance', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'warehouseFilter', 'categoryFilter', 'showZeroBalance']);
        $this->resetPage();
    }

    private function baseQuery(): Builder
    {
        $balances = DB::table('stock_balances')
            ->when($this->warehouseFilter, fn ($query) => $query->where('warehouse_id', $this->warehouseFilter))
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->groupBy('product_id');

        return DB::table('products')
            ->leftJoinSub($balances, 'balances', 'balances.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.category_id')
            ->leftJoin('product_units', 'product_units.id', '=', 'products.base_unit_id')
            ->whereNull('products.deleted_at')
            ->when(! $this->showZeroBalance, fn ($query) => $query->where('balances.quantity', '!=', 0))
            ->when($this->categoryFilter, fn ($query) => $query->where('products.category_id', $this->categoryFilter))
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query
                ->where('products.name', 'like', '%'.$this->search.'%')
                ->orWhere('products.sku', 'like', '%'.$this->search.'%')));
    }

    public function render()
    {
        $rows = $this->baseQuery()
            ->select([
                'products.id', 'products.sku', 'products.name', 'products.average_cost',
                'product_categories.name as category_name', 'product_units.name as unit_name',
                DB::raw('COALESCE(balances.quantity, 0) as quantity'),
                DB::raw('COALESCE(balances.quantity, 0) * products.average_cost as stock_value'),
            ])
            ->orderBy('products.name')
            ->paginate($this->perPage);

        $totals = $this->baseQuery()
            ->selectRaw('COALESCE(SUM(COALESCE(balances.quantity, 0) * products.average_cost), 0) as total_value')
            ->selectRaw('COUNT(products.id) as product_count')
            ->first();

        return view('livewire.inventory.report.stock-valuation', [
            'rows' => $rows,
            'totalValue' => (float) ($totals->total_value ?? 0),
            'productCount' => (int) ($totals->product_count ?? 0),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'categories' => ProductCategory::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
