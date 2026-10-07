<?php

namespace App\Services\Inventory;

use App\Models\ChartOfAccount;
use App\Models\DeliveryOrder;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\StockBalance;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Harga pokok persediaan metode rata-rata bergerak (moving average) per produk, dihitung
 * dalam satuan dasar dan berlaku untuk semua gudang. Juga membuat jurnal persediaan/HPP.
 *
 * Panggil receive()/reverseReceipt() SEBELUM saldo stok diubah, karena rata-rata baru
 * dihitung dari total stok yang ada saat itu.
 */
class InventoryCostService
{
    public const INVENTORY_ACCOUNT = '1200';

    public const COGS_ACCOUNT = '5100';

    public const ADJUSTMENT_ACCOUNT = '6400';

    public function averageCost(int $productId): float
    {
        return (float) (Product::whereKey($productId)->value('average_cost') ?? 0);
    }

    public function totalQuantity(int $productId): int
    {
        return (int) StockBalance::where('product_id', $productId)->sum('quantity');
    }

    /**
     * Barang masuk dengan biaya tertentu: rata-rata = (nilai lama + nilai masuk) / qty total.
     */
    public function receive(int $productId, int $quantity, float $unitCost): void
    {
        if ($quantity <= 0) {
            return;
        }

        $product = Product::lockForUpdate()->findOrFail($productId);
        $onHand = max(0, $this->totalQuantity($productId));
        $current = (float) $product->average_cost;

        $average = $onHand <= 0
            ? $unitCost
            : (($onHand * $current) + ($quantity * $unitCost)) / ($onHand + $quantity);

        $product->forceFill(['average_cost' => round($average, 4)])->save();
    }

    /**
     * Membatalkan barang masuk (mis. GR dibatalkan): nilai masuk dikeluarkan lagi dari rata-rata.
     */
    public function reverseReceipt(int $productId, int $quantity, float $unitCost): void
    {
        if ($quantity <= 0) {
            return;
        }

        $product = Product::lockForUpdate()->findOrFail($productId);
        $onHand = $this->totalQuantity($productId);
        $remaining = $onHand - $quantity;

        if ($remaining <= 0) {
            return;
        }

        $average = (($onHand * (float) $product->average_cost) - ($quantity * $unitCost)) / $remaining;
        $product->forceFill(['average_cost' => round(max(0, $average), 4)])->save();
    }

    /**
     * Biaya per satuan dasar barang dari PO: harga setelah diskon dibagi konversi satuan.
     * PPN tidak dihitung karena tercatat sebagai Pajak Masukan.
     */
    public function purchaseUnitCost(?PurchaseOrderItem $orderItem): float
    {
        if (! $orderItem) {
            return 0;
        }

        $qty = max(1, (int) $orderItem->qty);
        $conversion = max(1, (int) $orderItem->conversion);

        return max(0, ((int) $orderItem->price - ((int) $orderItem->disc / $qty)) / $conversion);
    }

    /**
     * Catat HPP Surat Jalan yang dikirim: simpan biaya per baris lalu jurnal Dr HPP / Cr Persediaan.
     */
    public function recordDeliveryCogs(DeliveryOrder $deliveryOrder): void
    {
        $deliveryOrder->loadMissing('items');
        $value = 0;

        foreach ($deliveryOrder->items as $item) {
            $unitCost = $this->averageCost($item->product_id);
            $item->update(['unit_cost' => $unitCost]);
            $value += (int) round((int) $item->qty_base * $unitCost);
        }

        $this->postJournal(
            JournalEntry::SOURCE_DELIVERY_ORDER,
            $deliveryOrder->id,
            $deliveryOrder->delivery_date ?? now(),
            'HPP Surat Jalan '.$deliveryOrder->delivery_no,
            [
                [self::COGS_ACCOUNT, $value, 0, 'Harga pokok penjualan'],
                [self::INVENTORY_ACCOUNT, 0, $value, 'Persediaan keluar'],
            ],
        );
    }

    /**
     * Pembatalan pengiriman: barang kembali ke persediaan dengan biaya saat dikirim, jurnal HPP dibatalkan.
     * Panggil sebelum saldo stok dikembalikan.
     */
    public function reverseDeliveryCogs(DeliveryOrder $deliveryOrder): void
    {
        $deliveryOrder->loadMissing('items');

        foreach ($deliveryOrder->items as $item) {
            $this->receive($item->product_id, (int) $item->qty_base, (float) $item->unit_cost);
        }

        $this->cancelJournal(JournalEntry::SOURCE_DELIVERY_ORDER, $deliveryOrder->id);
    }

    /**
     * Buat jurnal terposting dari daftar baris [kode akun, debit, kredit, keterangan].
     * Baris bernilai nol diabaikan; tidak membuat jurnal bila semuanya nol.
     */
    public function postJournal(string $sourceType, int $sourceId, $date, string $description, array $lines): ?JournalEntry
    {
        $lines = array_values(array_filter($lines, fn (array $line) => (int) $line[1] > 0 || (int) $line[2] > 0));

        if ($lines === []) {
            return null;
        }

        $journal = JournalEntry::create([
            'code' => $this->generateJournalCode(),
            'date' => $date,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'description' => $description,
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => Auth::id(),
        ]);

        foreach ($lines as [$accountCode, $debit, $credit, $lineDescription]) {
            $journal->lines()->create([
                'chart_of_account_id' => $this->accountId($accountCode),
                'debit' => (int) $debit,
                'credit' => (int) $credit,
                'description' => $lineDescription,
            ]);
        }

        return $journal;
    }

    public function cancelJournal(string $sourceType, int $sourceId): void
    {
        JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->update(['status' => JournalEntry::STATUS_CANCELLED]);
    }

    private function accountId(string $code): int
    {
        $id = ChartOfAccount::where('code', $code)->where('is_active', true)->where('is_postable', true)->value('id');

        if (! $id) {
            throw new RuntimeException("Akun {$code} belum tersedia atau tidak dapat diposting.");
        }

        return (int) $id;
    }

    private function generateJournalCode(): string
    {
        $prefix = 'JE-'.now()->format('ym').'-';
        $last = JournalEntry::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');

        return $prefix.str_pad((string) ($last ? (int) substr($last, strlen($prefix)) + 1 : 1), 3, '0', STR_PAD_LEFT);
    }
}
