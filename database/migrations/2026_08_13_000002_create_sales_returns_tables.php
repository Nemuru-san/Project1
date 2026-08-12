<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique();
            $table->date('return_date');
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('delivery_order_id')->constrained('delivery_orders');
            $table->foreignId('sales_order_id')->constrained('sales_orders');
            $table->string('status')->default('Draft');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
            $table->foreignId('delivery_order_item_id')->constrained('delivery_order_items');
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('unit_id')->constrained('product_units');
            $table->unsignedBigInteger('conversion')->default(1);
            $table->unsignedBigInteger('qty')->default(0);
            $table->unsignedBigInteger('qty_base')->default(0);
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(['sales_return_id', 'delivery_order_item_id'], 'sr_items_return_do_unique');
        });

        Schema::create('sales_return_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_no')->unique();
            $table->string('customer_reference_no')->nullable();
            $table->date('invoice_date');
            $table->foreignId('sales_return_id')->unique()->constrained('sales_returns');
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices');
            $table->foreignId('customer_id')->constrained('customers');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->string('status')->default('Draft');
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_invoices');
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
    }
};
