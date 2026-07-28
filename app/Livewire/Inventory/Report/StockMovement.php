<?php

namespace App\Livewire\Inventory\Report;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\AvailableForSalesService;
use App\Services\Inventory\StockMovementService;
use App\Services\Inventory\StockQuantityFormatter;
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

    public function render(StockMovementService $service, ?AvailableForSalesService $availabilityService = null)
    {
        $availabilityService ??= app(AvailableForSalesService::class);
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
                    'product_id' => $first['product_id'],
                    'warehouse_id' => $first['warehouse_id'],
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

        $availabilitySummaries = $availabilityService->summaries(
            $rows->map(fn (array $row) => [
                'product_id' => $row['product_id'],
                'warehouse_id' => $row['warehouse_id'],
            ]),
        );
        $rows = $rows->map(function (array $row) use ($availabilitySummaries) {
            $summary = $availabilitySummaries->get($row['product_id'].'-'.$row['warehouse_id'], [
                'quantity_on_hand' => 0,
                'reserved' => 0,
                'available_for_sales' => 0,
            ]);

            return $row + [
                'current_qoh' => $summary['quantity_on_hand'],
                'reserved' => $summary['reserved'],
                'available_for_sales' => $summary['available_for_sales'],
            ];
        });

        $productsById = Product::with('prices.unit')
            ->whereIn('id', $rows->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');
        $formatter = app(StockQuantityFormatter::class);
        $rows = $rows->map(fn (array $row) => $row + [
            'current_qoh_display' => $formatter->format($productsById->get($row['product_id']), $row['current_qoh']),
            'available_for_sales_display' => $formatter->format($productsById->get($row['product_id']), $row['available_for_sales']),
        ]);

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
