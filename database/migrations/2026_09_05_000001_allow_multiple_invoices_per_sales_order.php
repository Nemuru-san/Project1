<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu Pesanan Penjualan bisa ditagih bertahap (satu faktur per Surat Jalan),
     * jadi sales_order_id tidak lagi unik pada tabel faktur.
     */
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->index('sales_order_id', 'si_sales_order_index');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropUnique('si_sales_order_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->unique('sales_order_id', 'si_sales_order_unique');
        });

        Schema::table('sales_invoices', function (Blueprint $table) {
            $table->dropIndex('si_sales_order_index');
        });
    }
};
