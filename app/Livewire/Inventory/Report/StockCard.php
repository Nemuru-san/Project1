<?php

namespace App\Livewire\Inventory\Report;

use App\Models\Product;
use App\Models\Warehouse;
use App\Services\Inventory\AvailableForSalesService;
use App\Services\Inventory\StockMovementService;
use App\Services\Inventory\StockQuantityFormatter;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class StockCard extends Component
{
    use WithPagination;

    public string $productFilter = '';

    public string $warehouseFilter = '';

    // '' = semua, 'in' = masuk saja, 'out' = keluar saja.
    public string $typeFilter = '';

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
        if (in_array($property, ['productFilter', 'warehouseFilter', 'typeFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['productFilter', 'warehouseFilter', 'typeFilter']);
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->resetPage();
    }

    public function render(StockMovementService $service, ?AvailableForSalesService $availabilityService = null)
    {
        $availabilityService ??= app(AvailableForSalesService::class);
        $openingBalance = 0;
        $movements = collect();
        $totalIn = 0;
        $totalOut = 0;
        $availability = ['quantity_on_hand' => 0, 'reserved' => 0, 'available_for_sales' => 0];

        if ($this->productFilter) {
            $warehouseIds = $this->warehouseFilter
                ? collect([(int) $this->warehouseFilter])
                : Warehouse::query()->pluck('id')->map(fn ($id) => (int) $id);
            $availability = $availabilityService->summaries(
                $warehouseIds->map(fn (int $warehouseId) => [
                    'product_id' => (int) $this->productFilter,
                    'warehouse_id' => $warehouseId,
                ]),
            )->reduce(fn (array $total, array $summary) => [
                'quantity_on_hand' => $total['quantity_on_hand'] + $summary['quantity_on_hand'],
                'reserved' => $total['reserved'] + $summary['reserved'],
                'available_for_sales' => $total['available_for_sales'] + $summary['available_for_sales'],
            ], ['quantity_on_hand' => 0, 'reserved' => 0, 'available_for_sales' => 0]);

            $openingBalance = $service
                ->openingMovements($this->productFilter, $this->warehouseFilter, $this->dateFrom)
                ->sum(fn ($row) => $row['quantity_in'] - $row['quantity_out']);

            $runningBalance = $openingBalance;
            $movements = $this->groupByTransaction(
                $service->movements($this->productFilter, $this->warehouseFilter, $this->dateFrom, $this->dateTo)
            )->map(function (array $row) use (&$runningBalance) {
                $runningBalance += $row['quantity_in'] - $row['quantity_out'];
                $row['balance'] = $runningBalance;

                return $row;
            });

            // Total & saldo dihitung dari semua transaksi; filter Jenis hanya menyembunyikan baris.
            $totalIn = (int) $movements->sum('quantity_in');
            $totalOut = (int) $movements->sum('quantity_out');
            $movements = $movements
                ->when($this->typeFilter === 'in', fn ($rows) => $rows->where('quantity_in', '>', 0))
                ->when($this->typeFilter === 'out', fn ($rows) => $rows->where('quantity_out', '>', 0))
                ->values();
        }

        $selectedProduct = $this->productFilter
            ? Product::with(['prices.unit', 'baseUnit'])->find((int) $this->productFilter)
            : null;
        $availability['quantity_on_hand_display'] = app(StockQuantityFormatter::class)->format($selectedProduct, $availability['quantity_on_hand']);
        $availability['available_for_sales_display'] = app(StockQuantityFormatter::class)->format($selectedProduct, $availability['available_for_sales']);

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
            'totalIn' => $totalIn,
            'totalOut' => $totalOut,
            'closingBalance' => $openingBalance + $totalIn - $totalOut,
            'baseUnitName' => $selectedProduct?->baseUnit?->name,
            'availability' => $availability,
            'products' => Product::query()->orderBy('name')->get(['id', 'sku', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Satu baris per nomor transaksi: item dari transaksi yang sama (mis. beberapa gudang
     * dalam satu Penerimaan Barang) digabung, rinciannya disimpan di 'details'.
     */
    private function groupByTransaction(Collection $movements): Collection
    {
        return $movements
            ->groupBy(fn (array $row) => $row['date'].'|'.$row['reference'].'|'.$row['type'])
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'key' => md5($first['date'].'|'.$first['reference'].'|'.$first['type']),
                    'date' => $first['date'],
                    'reference' => $first['reference'],
                    'type' => $first['type'],
                    'description' => $first['description'] ?? $first['type'],
                    'quantity_in' => (int) $rows->sum('quantity_in'),
                    'quantity_out' => (int) $rows->sum('quantity_out'),
                    'details' => $rows->map(fn (array $row) => [
                        'warehouse_name' => $row['warehouse_name'],
                        'unit_quantity' => $row['unit_quantity'] ?? null,
                        'unit_name' => $row['unit_name'] ?? null,
                        'quantity_in' => $row['quantity_in'],
                        'quantity_out' => $row['quantity_out'],
                        'note' => $row['note'],
                    ])->values()->all(),
                ];
            })
            ->values();
    }
}
