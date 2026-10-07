<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * SO yang dibuat dari Pre Order lewat form Pesanan Penjualan dulu tersimpan dengan DP 0.
 * Isi ulang DP-nya dari DP terposting Pesanan Awal, hanya untuk SO yang belum difakturkan
 * supaya faktur yang sudah ada tidak berubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orders = DB::table('sales_orders')
            ->whereNotNull('pre_order_id')
            ->where('dp_amount', 0)
            ->whereNull('deleted_at')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                ->from('sales_invoices')
                ->whereColumn('sales_invoices.sales_order_id', 'sales_orders.id')
                ->whereNull('sales_invoices.deleted_at'))
            ->get(['id', 'pre_order_id', 'grand_total']);

        foreach ($orders as $order) {
            $postedDp = (int) DB::table('ar_dp_payment_allocations')
                ->join('ar_dp_payments', 'ar_dp_payments.id', '=', 'ar_dp_payment_allocations.ar_dp_payment_id')
                ->where('ar_dp_payment_allocations.pre_order_id', $order->pre_order_id)
                ->where('ar_dp_payments.status', 'Posted')
                ->whereNull('ar_dp_payments.deleted_at')
                ->sum('ar_dp_payment_allocations.amount');

            $dp = min($postedDp, (int) $order->grand_total);

            if ($dp <= 0) {
                continue;
            }

            DB::table('sales_orders')->where('id', $order->id)->update([
                'dp_amount' => $dp,
                'amount_due' => max(0, (int) $order->grand_total - $dp),
            ]);
        }
    }

    public function down(): void
    {
        // Data koreksi; tidak dikembalikan.
    }
};
