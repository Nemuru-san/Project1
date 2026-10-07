<?php

namespace App\Services\Inventory;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockOpname;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Stok opname: selisih hitung fisik vs stok sistem dibukukan sebagai Penyesuaian Stok Masuk
 * (fisik lebih banyak) dan/atau Keluar (fisik lebih sedikit), sehingga kartu stok, nilai
 * persediaan, dan jurnal selisih persediaan ikut tercatat. Bungkus dalam DB::transaction.
 */
class StockOpnameService
{
    public function __construct(private StockAdjustmentService $adjustments) {}

    public function approve(StockOpname $opname, ?int $userId = null): void
    {
        $opname = StockOpname::with('items')->lockForUpdate()->findOrFail($opname->id);

        if ($opname->status !== StockOpname::STATUS_DRAFT) {
            throw new RuntimeException('Hanya stok opname berstatus Draf yang dapat disetujui.');
        }

        $increase = [];
        $decrease = [];

        foreach ($opname->items as $item) {
            // Selisih dihitung ulang dari stok saat disetujui, karena stok bisa berubah sejak dihitung.
            $system = (int) (StockBalance::where('warehouse_id', $opname->warehouse_id)
                ->where('product_id', $item->product_id)
                ->value('quantity') ?? 0);
            $difference = (int) $item->physical_qty - $system;
            $item->update(['system_qty' => $system, 'difference' => $difference]);

            if ($difference > 0) {
                $increase[$item->product_id] = $difference;
            } elseif ($difference < 0) {
                $decrease[$item->product_id] = abs($difference);
            }
        }

        foreach (['in' => $increase, 'out' => $decrease] as $type => $quantities) {
            if ($quantities === []) {
                continue;
            }

            $adjustment = StockAdjustment::create([
                'adjustment_no' => $this->adjustmentCode($type),
                'date' => $opname->date,
                'type' => $type,
                'warehouse_id' => $opname->warehouse_id,
                'stock_opname_id' => $opname->id,
                'notes' => 'Selisih Stok Opname '.$opname->opname_no,
                'status' => 'draft',
                'created_by' => $userId ?? Auth::id(),
            ]);

            $baseUnits = Product::whereIn('id', array_keys($quantities))->pluck('base_unit_id', 'id');
            foreach ($quantities as $productId => $quantity) {
                $adjustment->items()->create([
                    'product_id' => $productId,
                    'unit_id' => $baseUnits[$productId],
                    'qty' => $quantity,
                    'conversion' => 1,
                ]);
            }

            $this->adjustments->approve($adjustment);
        }

        $opname->update([
            'status' => StockOpname::STATUS_APPROVED,
            'approved_by' => $userId ?? Auth::id(),
            'approved_at' => now(),
        ]);
    }

    public function cancel(StockOpname $opname): void
    {
        $opname = StockOpname::with('adjustments')->lockForUpdate()->findOrFail($opname->id);

        if ($opname->status === StockOpname::STATUS_CANCELLED) {
            throw new RuntimeException('Stok opname sudah dibatalkan.');
        }

        foreach ($opname->adjustments as $adjustment) {
            if ($adjustment->status !== StockAdjustmentService::STATUS_CANCELLED) {
                $this->adjustments->cancel($adjustment);
            }
        }

        $opname->update(['status' => StockOpname::STATUS_CANCELLED]);
    }

    private function adjustmentCode(string $type): string
    {
        $prefix = ($type === 'in' ? 'ADI-' : 'ADO-').now()->format('ym').'-';
        $last = StockAdjustment::withTrashed()
            ->where('type', $type)
            ->where('adjustment_no', 'like', $prefix.'%')
            ->orderByDesc('adjustment_no')
            ->value('adjustment_no');

        return $prefix.str_pad((string) ($last ? (int) substr($last, strlen($prefix)) + 1 : 1), 3, '0', STR_PAD_LEFT);
    }
}
