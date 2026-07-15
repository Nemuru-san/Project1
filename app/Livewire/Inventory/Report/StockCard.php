<?php

namespace App\Livewire\Inventory\Report;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\StockMovementService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class StockCard extends Component
{
    use WithPagination;

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
        if (in_array($property, ['productFilter', 'warehouseFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['productFilter', 'warehouseFilter']);
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->resetPage();
    }

    public function render(StockMovementService $service)
    {
        $openingBalance = 0;
        $movements = collect();

        if ($this->productFilter) {
            $openingBalance = $service
                ->openingMovements($this->productFilter, $this->warehouseFilter, $this->dateFrom)
                ->sum(fn ($row) => $row['quantity_in'] - $row['quantity_out']);

            $runningBalance = $openingBalance;
            $movements = $service
                ->movements($this->productFilter, $this->warehouseFilter, $this->dateFrom, $this->dateTo)
                ->map(function ($row) use (&$runningBalance) {
                    $runningBalance += $row['quantity_in'] - $row['quantity_out'];
                    $row['balance'] = $runningBalance;

                    return $row;
                });
        }

        $page = $this->getPage();
        $paginatedMovements = new LengthAwarePaginator(
            $movements->forPage($page, $this->perPage)->values(),
            $movements->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );

        return view('livewire.inventory.report.stock-card', [
            'movements' => $paginatedMovements,
            'openingBalance' => $openingBalance,
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
