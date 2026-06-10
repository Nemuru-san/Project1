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
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('price_id')
                ->nullable()
                ->constrained('product_prices')
                ->nullOnDelete();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('product_units')
                ->nullOnDelete();

            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('conversion')->default(1);
            $table->unsignedInteger('qty_base')->default(1);

            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('total_harga')->default(0);
            $table->unsignedInteger('disc')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
