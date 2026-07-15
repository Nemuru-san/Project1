<?php

namespace App\Livewire\Inventory\Report;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\StockMovementService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class StockMovement extends Component
{
    use WithPagination;

    public string $search = '';

    public string $productFilter = '';

    public string $warehouseFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 25;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'productFilter', 'warehouseFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'productFilter', 'warehouseFilter']);
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->resetPage();
    }

    public function render(StockMovementService $service)
    {
        $opening = $service->openingMovements($this->productFilter, $this->warehouseFilter, $this->dateFrom)
            ->groupBy(fn ($row) => $row['product_id'].'-'.$row['warehouse_id'])
            ->map(fn ($rows) => $rows->sum(fn ($row) => $row['quantity_in'] - $row['quantity_out']));

        $periodMovements = $service->movements(
            $this->productFilter,
            $this->warehouseFilter,
            $this->dateFrom,
            $this->dateTo,
        );

        $rows = $periodMovements
            ->groupBy(fn ($row) => $row['product_id'].'-'.$row['warehouse_id'])
            ->map(function ($movements, $key) use ($opening) {
                $first = $movements->first();
                $openingBalance = (int) ($opening[$key] ?? 0);
                $quantityIn = $movements->sum('quantity_in');
                $quantityOut = $movements->sum('quantity_out');

                return [
                    'product_sku' => $first['product_sku'],
                    'product_name' => $first['product_name'],
                    'warehouse_name' => $first['warehouse_name'],
                    'opening_balance' => $openingBalance,
                    'quantity_in' => $quantityIn,
                    'quantity_out' => $quantityOut,
                    'ending_balance' => $openingBalance + $quantityIn - $quantityOut,
                ];
            })
            ->when($this->search, function ($rows) {
                $search = mb_strtolower($this->search);

                return $rows->filter(fn ($row) => str_contains(mb_strtolower($row['product_sku'].' '.$row['product_name'].' '.$row['warehouse_name']), $search));
            })
            ->sortBy([
                ['product_name', 'asc'],
                ['warehouse_name', 'asc'],
            ])
            ->values();

        $page = $this->getPage();
        $paginatedRows = new LengthAwarePaginator(
            $rows->forPage($page, $this->perPage)->values(),
            $rows->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );

        return view('livewire.inventory.report.stock-movement', [
            'rows' => $paginatedRows,
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
