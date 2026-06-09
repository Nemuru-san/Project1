<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices')->cascadeOnDelete();

            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();

            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('unit_id')->nullable()->constrained('product_units')->nullOnDelete();

            $table->unsignedBigInteger('conversion')->default(1);

            $table->unsignedBigInteger('qty')->default(0);
            $table->unsignedBigInteger('qty_base')->default(0);

            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('total')->default(0);

            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_items');
    }
};
