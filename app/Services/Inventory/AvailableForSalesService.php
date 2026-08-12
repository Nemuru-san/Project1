<?php

namespace App\Services\Inventory;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\SalesOrderItem;
use App\Models\StockBalance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AvailableForSalesService
{
    private const NON_RESERVING_STATUSES = [
        'cancelled',
        'Cancelled',
        'completed',
        'Completed',
        'fulfilled',
        'Fulfilled',
    ];

    public function quantityOnHand(int $productId, int $warehouseId): int
    {
        return (int) (StockBalance::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->value('quantity') ?? 0);
    }

    public function reserved(int $productId, int $warehouseId, ?int $excludeSalesOrderId = null): int
    {
        return (int) ($this->reservationQuery($productId, $warehouseId, $excludeSalesOrderId)
            ->selectRaw('COALESCE(SUM('.$this->remainingReservationSql().'), 0) as reserved')
            ->value('reserved') ?? 0);
    }

    public function available(int $productId, int $warehouseId, ?int $excludeSalesOrderId = null): int
    {
        return $this->quantityOnHand($productId, $warehouseId)
            - $this->reserved($productId, $warehouseId, $excludeSalesOrderId);
    }

    public function summary(int $productId, int $warehouseId, ?int $excludeSalesOrderId = null): array
    {
        $quantityOnHand = $this->quantityOnHand($productId, $warehouseId);
        $reserved = $this->reserved($productId, $warehouseId, $excludeSalesOrderId);

        return [
            'quantity_on_hand' => $quantityOnHand,
            'reserved' => $reserved,
            'available_for_sales' => $quantityOnHand - $reserved,
        ];
    }

    public function summaries(Collection|array $pairs): Collection
    {
        $pairs = collect($pairs)
            ->map(fn (array $pair) => [
                'product_id' => (int) $pair['product_id'],
                'warehouse_id' => (int) $pair['warehouse_id'],
            ])
            ->unique(fn (array $pair) => $pair['product_id'].'-'.$pair['warehouse_id'])
            ->values();

        if ($pairs->isEmpty()) {
            return collect();
        }

        $productIds = $pairs->pluck('product_id')->unique();
        $warehouseIds = $pairs->pluck('warehouse_id')->unique();

        $balances = StockBalance::query()
            ->whereIn('product_id', $productIds)
            ->whereIn('warehouse_id', $warehouseIds)
            ->get()
            ->keyBy(fn (StockBalance $balance) => $balance->product_id.'-'.$balance->warehouse_id);

        $reservations = $this->baseReservationQuery()
            ->whereIn('sales_order_items.product_id', $productIds)
            ->whereIn('sales_order_items.warehouse_id', $warehouseIds)
            ->selectRaw('sales_order_items.product_id, sales_order_items.warehouse_id, SUM('.$this->remainingReservationSql().') as reserved')
            ->groupBy('sales_order_items.product_id', 'sales_order_items.warehouse_id')
            ->get()
            ->keyBy(fn ($row) => $row->product_id.'-'.$row->warehouse_id);

        return $pairs->mapWithKeys(function (array $pair) use ($balances, $reservations) {
            $key = $pair['product_id'].'-'.$pair['warehouse_id'];
            $quantityOnHand = (int) ($balances->get($key)?->quantity ?? 0);
            $reserved = (int) ($reservations->get($key)?->reserved ?? 0);

            return [$key => [
                'quantity_on_hand' => $quantityOnHand,
                'reserved' => $reserved,
                'available_for_sales' => $quantityOnHand - $reserved,
            ]];
        });
    }

    public function bookings(int $productId, int $warehouseId): Collection
    {
        return $this->reservationQuery($productId, $warehouseId)
            ->select('sales_order_items.*')
            ->selectRaw('COALESCE(shipped_delivery.shipped_base, 0) as shipped_base')
            ->with(['salesOrder.customer'])
            ->orderBy('sales_orders.date')
            ->orderBy('sales_orders.id')
            ->get()
            ->map(fn (SalesOrderItem $item) => [
                'sales_order_id' => $item->sales_order_id,
                'so_code' => $item->salesOrder?->order_no ?? '-',
                'customer_name' => $item->salesOrder?->customer?->name ?? '-',
                'date' => $item->salesOrder?->date?->format('d/m/Y') ?? '-',
                'qty_order' => (int) $item->qty,
                'conversion' => (int) $item->conversion,
                'qty_booking' => max(0, ((int) $item->qty * (int) $item->conversion) - (int) $item->shipped_base),
                'status' => $item->salesOrder?->status ?? '-',
            ]);
    }

    private function reservationQuery(
        int $productId,
        int $warehouseId,
        ?int $excludeSalesOrderId = null,
    ): Builder {
        return $this->baseReservationQuery()
            ->where('sales_order_items.product_id', $productId)
            ->where('sales_order_items.warehouse_id', $warehouseId)
            ->when($excludeSalesOrderId, fn (Builder $query) => $query->where('sales_orders.id', '!=', $excludeSalesOrderId));
    }

    private function baseReservationQuery(): Builder
    {
        $shipped = DeliveryOrderItem::query()
            ->selectRaw('delivery_order_items.sales_order_item_id, SUM(delivery_order_items.qty_base) as shipped_base')
            ->join('delivery_orders', 'delivery_orders.id', '=', 'delivery_order_items.delivery_order_id')
            ->whereNull('delivery_orders.deleted_at')
            ->where('delivery_orders.status', DeliveryOrder::STATUS_SHIPPED)
            ->groupBy('delivery_order_items.sales_order_item_id');

        return SalesOrderItem::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_items.sales_order_id')
            ->leftJoinSub($shipped, 'shipped_delivery', fn ($join) => $join->on(
                'shipped_delivery.sales_order_item_id', '=', 'sales_order_items.id'
            ))
            ->whereNull('sales_orders.deleted_at')
            ->whereNotIn('sales_orders.status', self::NON_RESERVING_STATUSES);
    }

    private function remainingReservationSql(): string
    {
        $ordered = '(sales_order_items.qty * sales_order_items.conversion)';
        $shipped = 'COALESCE(shipped_delivery.shipped_base, 0)';

        return "CASE WHEN {$ordered} > {$shipped} THEN {$ordered} - {$shipped} ELSE 0 END";
    }
}
