<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receive_purchase_invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();
            $table->foreignId('goods_receive_id')->constrained('goods_receives')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['purchase_invoice_id', 'goods_receive_id'], 'pi_gr_unique');
            $table->index(['goods_receive_id', 'purchase_invoice_id'], 'gr_pi_index');
        });

        Schema::create('delivery_order_sales_invoice', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['sales_invoice_id', 'delivery_order_id'], 'si_do_unique');
            $table->index(['delivery_order_id', 'sales_invoice_id'], 'do_si_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_sales_invoice');
        Schema::dropIfExists('goods_receive_purchase_invoice');
    }
};
