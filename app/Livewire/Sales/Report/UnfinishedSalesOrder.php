<?php

namespace App\Livewire\Sales\Report;

use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class UnfinishedSalesOrder extends Component
{
    use WithPagination;

    public string $search = '';

    public string $customerFilter = '';

    public string $warehouseFilter = '';

    public string $deliveryFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public string $sortField = 'date';

    public string $sortDirection = 'desc';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'customerFilter', 'warehouseFilter', 'deliveryFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['order_no', 'date'], true)) {
            return;
        }
        $this->sortDirection = $this->sortField === $field ? ($this->sortDirection === 'asc' ? 'desc' : 'asc') : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'customerFilter', 'warehouseFilter', 'deliveryFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function query(): Builder
    {
        $ordered = SalesOrderItem::query()
            ->selectRaw('COALESCE(SUM(sales_order_items.qty), 0)')
            ->whereColumn('sales_order_items.sales_order_id', 'sales_orders.id');
        $shipped = DeliveryOrderItem::query()
            ->selectRaw('COALESCE(SUM(delivery_order_items.qty_delivered), 0)')
            ->join('delivery_orders', 'delivery_orders.id', '=', 'delivery_order_items.delivery_order_id')
            ->join('sales_order_items as shipped_so_items', 'shipped_so_items.id', '=', 'delivery_order_items.sales_order_item_id')
            ->whereColumn('shipped_so_items.sales_order_id', 'sales_orders.id')
            ->whereNull('delivery_orders.deleted_at')
            ->where('delivery_orders.status', DeliveryOrder::STATUS_SHIPPED);

        return SalesOrder::query()
            ->with(['customer', 'preOrder', 'salesCanvas'])
            ->withCount(['deliveryOrders as delivery_orders_count' => fn (Builder $query) => $query->where('status', DeliveryOrder::STATUS_SHIPPED)])
            ->addSelect(['ordered_qty' => clone $ordered, 'shipped_qty' => clone $shipped])
            ->whereNotNull('verified_at')
            ->whereNotIn('status', ['cancelled', 'Cancelled'])
            ->whereRaw(
                '('.$ordered->toSql().') > ('.$shipped->toSql().')',
                [...$ordered->getBindings(), ...$shipped->getBindings()],
            )
            ->when($this->search, function (Builder $query) {
                $search = '%'.$this->search.'%';
                $query->where(fn (Builder $query) => $query->where('order_no', 'like', $search)
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $search)));
            })
            ->when($this->customerFilter, fn (Builder $query) => $query->where('customer_id', $this->customerFilter))
            ->when($this->warehouseFilter, fn (Builder $query) => $query->whereHas('items', fn (Builder $items) => $items->where('warehouse_id', $this->warehouseFilter)))
            ->when($this->deliveryFilter === 'pending', fn (Builder $query) => $query->whereRaw('('.$shipped->toSql().') = 0', $shipped->getBindings()))
            ->when($this->deliveryFilter === 'partial', fn (Builder $query) => $query->whereRaw('('.$shipped->toSql().') > 0', $shipped->getBindings()))
            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('date', '<=', $this->dateTo));
    }

    public function render()
    {
        $summaryRows = (clone $this->query())->get();
        $salesOrders = $this->query()->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);
        $salesOrders->setCollection($salesOrders->getCollection()->map(function (SalesOrder $order) {
            $order->setAttribute('outstanding_qty', max(0, (int) $order->ordered_qty - (int) $order->shipped_qty));
            $order->setAttribute('age_days', max(0, $order->date?->startOfDay()->diffInDays(today(), false) ?? 0));

            return $order;
        }));

        return view('livewire.sales.report.unfinished-sales-order', [
            'salesOrders' => $salesOrders,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'summary' => [
                'count' => $summaryRows->count(),
                'ordered' => (int) $summaryRows->sum('ordered_qty'),
                'shipped' => (int) $summaryRows->sum('shipped_qty'),
                'outstanding' => (int) $summaryRows->sum(fn ($order) => max(0, (int) $order->ordered_qty - (int) $order->shipped_qty)),
            ],
        ]);
    }
}
