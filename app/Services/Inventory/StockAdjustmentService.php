<?php

namespace App\Services\Inventory;

use App\Models\JournalEntry;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use RuntimeException;

/**
 * Menyetujui & membatalkan Penyesuaian Stok (juga dipakai Stok Opname): mengubah saldo stok,
 * mencatat harga pokok per baris, dan membuat jurnal Persediaan lawan Selisih Persediaan.
 * Pemanggil bertanggung jawab membungkusnya dalam DB::transaction.
 */
class StockAdjustmentService
{
    public const STATUS_CANCELLED = 'cancelled';

    public function __construct(private InventoryCostService $costs) {}

    public function approve(StockAdjustment $adjustment): void
    {
        $adjustment = StockAdjustment::with('items.product')->lockForUpdate()->findOrFail($adjustment->id);

        if ($adjustment->status !== 'draft') {
            throw new RuntimeException('Hanya penyesuaian berstatus Draf yang dapat disetujui.');
        }

        $isIn = $adjustment->type === 'in';
        $value = 0;

        foreach ($adjustment->items as $item) {
            $qtyBase = (int) round((float) $item->qty * (float) $item->conversion);
            // Penyesuaian tidak punya harga beli; nilainya memakai harga pokok rata-rata saat ini.
            $unitCost = $this->costs->averageCost($item->product_id);

            if ($isIn) {
                $this->costs->receive($item->product_id, $qtyBase, $unitCost);
                $stock = StockBalance::firstOrCreate(
                    ['warehouse_id' => $adjustment->warehouse_id, 'product_id' => $item->product_id],
                    ['quantity' => 0],
                );
                StockBalance::whereKey($stock->id)->lockForUpdate()->firstOrFail()->increment('quantity', $qtyBase);
            } else {
                $stock = StockBalance::where('warehouse_id', $adjustment->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock || $stock->quantity < $qtyBase) {
                    throw new RuntimeException('Stok tidak cukup untuk '.($item->product?->name ?? 'salah satu produk').'.');
                }

                $stock->decrement('quantity', $qtyBase);
            }

            $item->update(['unit_cost' => $unitCost]);
            $value += (int) round($qtyBase * $unitCost);
        }

        $adjustment->update(['status' => 'approved']);

        $this->costs->postJournal(
            JournalEntry::SOURCE_STOCK_ADJUSTMENT,
            $adjustment->id,
            $adjustment->date,
            ($isIn ? 'Penyesuaian Stok Masuk ' : 'Penyesuaian Stok Keluar ').$adjustment->adjustment_no,
            $isIn
                ? [
                    [InventoryCostService::INVENTORY_ACCOUNT, $value, 0, 'Persediaan bertambah'],
                    [InventoryCostService::ADJUSTMENT_ACCOUNT, 0, $value, 'Selisih persediaan'],
                ]
                : [
                    [InventoryCostService::ADJUSTMENT_ACCOUNT, $value, 0, 'Selisih persediaan'],
                    [InventoryCostService::INVENTORY_ACCOUNT, 0, $value, 'Persediaan berkurang'],
                ],
        );
    }

    /**
     * Membalik penyesuaian yang sudah disetujui: stok dikembalikan dan jurnalnya dibatalkan.
     */
    public function cancel(StockAdjustment $adjustment): void
    {
        $adjustment = StockAdjustment::with('items.product')->lockForUpdate()->findOrFail($adjustment->id);

        if ($adjustment->status === self::STATUS_CANCELLED) {
            throw new RuntimeException('Penyesuaian stok sudah dibatalkan.');
        }

        if ($adjustment->status === 'approved') {
            $isIn = $adjustment->type === 'in';

            foreach ($adjustment->items as $item) {
                $qtyBase = (int) round((float) $item->qty * (float) $item->conversion);

                if ($isIn) {
                    $stock = StockBalance::where('warehouse_id', $adjustment->warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $stock || $stock->quantity < $qtyBase) {
                        throw new RuntimeException('Stok '.($item->product?->name ?? '-').' sudah terpakai sehingga penyesuaian masuk tidak dapat dibatalkan.');
                    }

                    $this->costs->reverseReceipt($item->product_id, $qtyBase, (float) $item->unit_cost);
                    $stock->decrement('quantity', $qtyBase);
                } else {
                    $this->costs->receive($item->product_id, $qtyBase, (float) $item->unit_cost);
                    $stock = StockBalance::firstOrCreate(
                        ['warehouse_id' => $adjustment->warehouse_id, 'product_id' => $item->product_id],
                        ['quantity' => 0],
                    );
                    StockBalance::whereKey($stock->id)->lockForUpdate()->firstOrFail()->increment('quantity', $qtyBase);
                }
            }

            $this->costs->cancelJournal(JournalEntry::SOURCE_STOCK_ADJUSTMENT, $adjustment->id);
        }

        $adjustment->update(['status' => self::STATUS_CANCELLED]);
    }
}
