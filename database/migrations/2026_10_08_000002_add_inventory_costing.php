<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harga pokok rata-rata (moving average) per produk untuk jurnal HPP & nilai persediaan.
 * Biaya per satuan dasar disimpan di baris keluar/masuk agar pembatalan memakai biaya yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('average_cost', 18, 4)->default(0)->after('base_unit_id');
        });

        Schema::table('delivery_order_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 18, 4)->default(0)->after('qty_base');
        });

        Schema::table('sales_return_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 18, 4)->default(0)->after('qty_base');
        });

        Schema::table('goods_receive_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 18, 4)->default(0)->after('qty_base');
        });

        Schema::table('stock_adjustment_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 18, 4)->default(0)->after('conversion');
        });

        // Akun lawan penyesuaian stok & stok opname.
        if (! DB::table('chart_of_accounts')->where('code', '6400')->exists()) {
            DB::table('chart_of_accounts')->insert([
                'code' => '6400',
                'name' => 'Selisih Persediaan',
                'type' => 'Expense',
                'normal_balance' => 'Debit',
                'parent_id' => DB::table('chart_of_accounts')->where('code', '6000')->value('id'),
                'is_postable' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->backfillAverageCost();
    }

    /**
     * Harga pokok awal = rata-rata tertimbang harga beli (setelah diskon, per satuan dasar)
     * dari seluruh Penerimaan Barang yang sudah masuk stok.
     */
    private function backfillAverageCost(): void
    {
        $rows = DB::table('goods_receive_items')
            ->join('goods_receives', 'goods_receives.id', '=', 'goods_receive_items.goods_receive_id')
            ->join('purchase_order_items', 'purchase_order_items.id', '=', 'goods_receive_items.purchase_order_item_id')
            ->whereIn('goods_receives.status', ['Received', 'Invoiced'])
            ->whereNull('goods_receives.deleted_at')
            ->get([
                'goods_receive_items.id', 'goods_receive_items.product_id', 'goods_receive_items.qty_base',
                'purchase_order_items.price', 'purchase_order_items.disc', 'purchase_order_items.qty',
                'purchase_order_items.conversion',
            ]);

        $totals = [];
        foreach ($rows as $row) {
            $qty = max(1, (int) $row->qty);
            $conversion = max(1, (int) $row->conversion);
            $unitCost = max(0, ((int) $row->price - ((int) $row->disc / $qty)) / $conversion);

            DB::table('goods_receive_items')->where('id', $row->id)->update(['unit_cost' => $unitCost]);

            $totals[$row->product_id]['qty'] = ($totals[$row->product_id]['qty'] ?? 0) + (int) $row->qty_base;
            $totals[$row->product_id]['value'] = ($totals[$row->product_id]['value'] ?? 0) + (int) $row->qty_base * $unitCost;
        }

        foreach ($totals as $productId => $total) {
            if ($total['qty'] > 0) {
                DB::table('products')->where('id', $productId)->update(['average_cost' => $total['value'] / $total['qty']]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('average_cost'));
        Schema::table('delivery_order_items', fn (Blueprint $table) => $table->dropColumn('unit_cost'));
        Schema::table('sales_return_items', fn (Blueprint $table) => $table->dropColumn('unit_cost'));
        Schema::table('goods_receive_items', fn (Blueprint $table) => $table->dropColumn('unit_cost'));
        Schema::table('stock_adjustment_items', fn (Blueprint $table) => $table->dropColumn('unit_cost'));
    }
};
