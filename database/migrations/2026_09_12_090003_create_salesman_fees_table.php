<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fee perekrutan customer: dicatat per faktur penjualan yang dikonfirmasi
        // selama salesman perekrut masih aktif (snapshot, tidak berubah bila salesman nonaktif setelahnya).
        Schema::create('salesman_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salesman_id')->constrained('salesmen')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sales_invoice_id')->unique()->constrained('sales_invoices')->cascadeOnDelete();
            $table->date('invoice_date');
            $table->unsignedBigInteger('base_amount');
            $table->decimal('fee_percent', 5, 2);
            $table->unsignedBigInteger('fee_amount');
            $table->timestamps();

            $table->index(['salesman_id', 'invoice_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salesman_fees');
    }
};
