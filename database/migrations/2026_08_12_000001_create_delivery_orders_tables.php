<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_no', 50)->unique();
            $table->date('delivery_date');
            $table->foreignId('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sales_order_id', 'delivery_date'], 'do_sales_order_date_index');
            $table->index(['customer_id', 'delivery_date'], 'do_customer_date_index');
            $table->index(['status', 'delivery_date'], 'do_status_date_index');
        });

        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained('delivery_orders')->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('product_units')->restrictOnDelete();
            $table->unsignedInteger('conversion')->default(1);
            $table->unsignedInteger('qty_order');
            $table->unsignedInteger('qty_delivered');
            $table->unsignedInteger('qty_outstanding');
            $table->unsignedBigInteger('qty_base');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['delivery_order_id', 'sales_order_item_id'], 'do_item_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
    }
};
