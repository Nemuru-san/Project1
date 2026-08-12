<?php

namespace App\Services\Inventory;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\GoodsReceive;
use App\Models\GoodsReceiveItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\StockAdjustmentItem;
use App\Models\StockTransferItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StockMovementService
{
    public function movements(
        int|string|null $productId = null,
        int|string|null $warehouseId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): Collection {
        return collect()
            ->concat($this->goodsReceiveMovements($productId, $warehouseId, $dateFrom, $dateTo))
            ->concat($this->purchaseReturnMovements($productId, $warehouseId, $dateFrom, $dateTo))
            ->concat($this->deliveryOrderMovements($productId, $warehouseId, $dateFrom, $dateTo))
            ->concat($this->salesReturnMovements($productId, $warehouseId, $dateFrom, $dateTo))
            ->concat($this->transferMovements($productId, $warehouseId, $dateFrom, $dateTo))
            ->concat($this->adjustmentMovements($productId, $warehouseId, $dateFrom, $dateTo))
            ->sortBy([
                ['date', 'asc'],
                ['created_at', 'asc'],
                ['direction_order', 'asc'],
            ])
            ->values();
    }

    public function openingMovements(
        int|string|null $productId,
        int|string|null $warehouseId,
        ?string $dateFrom,
    ): Collection {
        if (! $dateFrom) {
            return collect();
        }

        return $this->movements(
            $productId,
            $warehouseId,
            null,
            Carbon::parse($dateFrom)->subDay()->toDateString(),
        );
    }

    private function goodsReceiveMovements($productId, $warehouseId, ?string $dateFrom, ?string $dateTo): Collection
    {
        return GoodsReceiveItem::query()
            ->with(['goodsReceive', 'product', 'warehouse'])
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->whereHas('goodsReceive', function ($query) use ($dateFrom, $dateTo) {
                $query->where('status', GoodsReceive::STATUS_RECEIVED)
                    ->when($dateFrom, fn ($query) => $query->whereDate('date', '>=', $dateFrom))
                    ->when($dateTo, fn ($query) => $query->whereDate('date', '<=', $dateTo));
            })
            ->get()
            ->map(fn (GoodsReceiveItem $item) => $this->movementRow(
                $item->goodsReceive?->date?->toDateString(),
                $item->goodsReceive?->created_at,
                $item->goodsReceive?->code ?? '-',
                'Penerimaan Barang',
                $item->product_id,
                $item->product?->sku ?? '-',
                $item->product?->name ?? '-',
                $item->warehouse_id,
                $item->warehouse?->name ?? '-',
                (int) $item->qty_base,
                0,
                $item->note,
            ));
    }

    private function purchaseReturnMovements($productId, $warehouseId, ?string $dateFrom, ?string $dateTo): Collection
    {
        return PurchaseReturnItem::query()
            ->with(['purchaseReturn', 'product', 'warehouse'])
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->whereHas('purchaseReturn', function ($query) use ($dateFrom, $dateTo) {
                $query->where('status', PurchaseReturn::STATUS_CONFIRMED)
                    ->when($dateFrom, fn ($query) => $query->whereDate('return_date', '>=', $dateFrom))
                    ->when($dateTo, fn ($query) => $query->whereDate('return_date', '<=', $dateTo));
            })
            ->get()
            ->map(fn (PurchaseReturnItem $item) => $this->movementRow(
                $item->purchaseReturn?->return_date?->toDateString(),
                $item->purchaseReturn?->created_at,
                $item->purchaseReturn?->return_no ?? '-',
                'Retur Pembelian',
                $item->product_id,
                $item->product?->sku ?? '-',
                $item->product?->name ?? '-',
                $item->warehouse_id,
                $item->warehouse?->name ?? '-',
                0,
                (int) $item->qty_base,
                $item->reason ?? $item->purchaseReturn?->notes,
            ));
    }

    private function deliveryOrderMovements($productId, $warehouseId, ?string $dateFrom, ?string $dateTo): Collection
    {
        return DeliveryOrderItem::query()
            ->with(['deliveryOrder', 'product', 'warehouse'])
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->whereHas('deliveryOrder', function ($query) use ($dateFrom, $dateTo) {
                $query->where('status', DeliveryOrder::STATUS_SHIPPED)
                    ->when($dateFrom, fn ($query) => $query->whereDate('delivery_date', '>=', $dateFrom))
                    ->when($dateTo, fn ($query) => $query->whereDate('delivery_date', '<=', $dateTo));
            })
            ->get()
            ->map(fn (DeliveryOrderItem $item) => $this->movementRow(
                $item->deliveryOrder?->delivery_date?->toDateString(),
                $item->deliveryOrder?->created_at,
                $item->deliveryOrder?->delivery_no ?? '-',
                'Pengiriman Penjualan',
                $item->product_id,
                $item->product?->sku ?? '-',
                $item->product?->name ?? '-',
                $item->warehouse_id,
                $item->warehouse?->name ?? '-',
                0,
                (int) $item->qty_base,
                $item->note ?? $item->deliveryOrder?->notes,
            ));
    }

    private function salesReturnMovements($productId, $warehouseId, ?string $dateFrom, ?string $dateTo): Collection
    {
        return SalesReturnItem::query()->with(['salesReturn', 'product', 'warehouse'])
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->whereHas('salesReturn', function ($query) use ($dateFrom, $dateTo) {
                $query->where('status', SalesReturn::STATUS_CONFIRMED)
                    ->when($dateFrom, fn ($query) => $query->whereDate('return_date', '>=', $dateFrom))
                    ->when($dateTo, fn ($query) => $query->whereDate('return_date', '<=', $dateTo));
            })->get()->map(fn (SalesReturnItem $item) => $this->movementRow(
                $item->salesReturn?->return_date?->toDateString(), $item->salesReturn?->created_at,
                $item->salesReturn?->return_no ?? '-', 'Retur Penjualan', $item->product_id,
                $item->product?->sku ?? '-', $item->product?->name ?? '-', $item->warehouse_id,
                $item->warehouse?->name ?? '-', (int) $item->qty_base, 0,
                $item->reason ?? $item->salesReturn?->notes,
            ));
    }

    private function transferMovements($productId, $warehouseId, ?string $dateFrom, ?string $dateTo): Collection
    {
        return StockTransferItem::query()
            ->with(['stockTransfer.warehouseFrom', 'stockTransfer.warehouseTo', 'product'])
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->whereHas('stockTransfer', function ($query) use ($warehouseId, $dateFrom, $dateTo) {
                $query->where('status', 'approved')
                    ->when($warehouseId, function ($query) use ($warehouseId) {
                        $query->where(function ($query) use ($warehouseId) {
                            $query->where('warehouse_from_id', $warehouseId)
                                ->orWhere('warehouse_to_id', $warehouseId);
                        });
                    })
                    ->when($dateFrom, fn ($query) => $query->whereDate('date', '>=', $dateFrom))
                    ->when($dateTo, fn ($query) => $query->whereDate('date', '<=', $dateTo));
            })
            ->get()
            ->flatMap(function (StockTransferItem $item) use ($warehouseId) {
                $transfer = $item->stockTransfer;
                $quantity = (int) round((float) $item->qty * (float) $item->conversion);
                $rows = [];

                if (! $warehouseId || (int) $warehouseId === (int) $transfer->warehouse_from_id) {
                    $rows[] = $this->movementRow(
                        $transfer->date?->toDateString(),
                        $transfer->created_at,
                        $transfer->trf_no,
                        'Transfer Keluar',
                        $item->product_id,
                        $item->product?->sku ?? '-',
                        $item->product?->name ?? '-',
                        $transfer->warehouse_from_id,
                        $transfer->warehouseFrom?->name ?? '-',
                        0,
                        $quantity,
                        $transfer->notes,
                    );
                }

                if (! $warehouseId || (int) $warehouseId === (int) $transfer->warehouse_to_id) {
                    $rows[] = $this->movementRow(
                        $transfer->date?->toDateString(),
                        $transfer->created_at,
                        $transfer->trf_no,
                        'Transfer Masuk',
                        $item->product_id,
                        $item->product?->sku ?? '-',
                        $item->product?->name ?? '-',
                        $transfer->warehouse_to_id,
                        $transfer->warehouseTo?->name ?? '-',
                        $quantity,
                        0,
                        $transfer->notes,
                    );
                }

                return $rows;
            });
    }

    private function adjustmentMovements($productId, $warehouseId, ?string $dateFrom, ?string $dateTo): Collection
    {
        return StockAdjustmentItem::query()
            ->with(['adjustment.warehouse', 'product'])
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->whereHas('adjustment', function ($query) use ($warehouseId, $dateFrom, $dateTo) {
                $query->where('status', 'approved')
                    ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
                    ->when($dateFrom, fn ($query) => $query->whereDate('date', '>=', $dateFrom))
                    ->when($dateTo, fn ($query) => $query->whereDate('date', '<=', $dateTo));
            })
            ->get()
            ->map(function (StockAdjustmentItem $item) {
                $adjustment = $item->adjustment;
                $quantity = (int) round((float) $item->qty * (float) $item->conversion);
                $isIn = $adjustment->type === 'in';

                return $this->movementRow(
                    $adjustment->date?->toDateString(),
                    $adjustment->created_at,
                    $adjustment->adjustment_no,
                    $isIn ? 'Penyesuaian Masuk' : 'Penyesuaian Keluar',
                    $item->product_id,
                    $item->product?->sku ?? '-',
                    $item->product?->name ?? '-',
                    $adjustment->warehouse_id,
                    $adjustment->warehouse?->name ?? '-',
                    $isIn ? $quantity : 0,
                    $isIn ? 0 : $quantity,
                    $adjustment->notes,
                );
            });
    }

    private function movementRow(
        ?string $date,
        $createdAt,
        string $reference,
        string $type,
        int $productId,
        string $productSku,
        string $productName,
        int $warehouseId,
        string $warehouseName,
        int $quantityIn,
        int $quantityOut,
        ?string $note,
    ): array {
        return [
            'date' => $date,
            'created_at' => $createdAt?->toDateTimeString() ?? $date,
            'direction_order' => $quantityOut > 0 ? 0 : 1,
            'reference' => $reference,
            'type' => $type,
            'product_id' => $productId,
            'product_sku' => $productSku,
            'product_name' => $productName,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
            'quantity_in' => $quantityIn,
            'quantity_out' => $quantityOut,
            'note' => $note,
        ];
    }
}
