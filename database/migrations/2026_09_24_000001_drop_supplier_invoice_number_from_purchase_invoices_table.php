<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom nomor faktur pemasok sudah tidak dipakai: form Faktur Pembelian
     * tidak lagi mengisinya, jadi seluruh tampilan yang membacanya ikut dihapus.
     */
    public function up(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->dropColumn('supplier_invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            $table->string('supplier_invoice_number')->nullable()->after('code');
        });
    }
};
