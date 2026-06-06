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
        Schema::create('goods_receive_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('goods_receive_id')
                ->constrained('goods_receives')
                ->cascadeOnDelete();

            $table->foreignId('purchase_order_item_id')
                ->constrained('purchase_order_items')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete();

            $table->foreignId('unit_id')
                ->constrained('product_units')
                ->cascadeOnDelete();

            $table->integer('conversion')->default(1);
            $table->integer('qty_order')->default(0);
            $table->integer('qty_received')->default(0);
            $table->integer('qty_outstanding')->default(0);

            $table->integer('qty_base')->default(0);

            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receive_items');
    }
};
