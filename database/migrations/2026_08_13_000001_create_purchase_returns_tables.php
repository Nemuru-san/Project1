<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_no')->unique();
            $table->date('return_date');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('goods_receive_id')->constrained('goods_receives');
            $table->foreignId('purchase_order_id')->constrained('purchase_orders');
            $table->string('status')->default('Draft');
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('goods_receive_item_id')->constrained('goods_receive_items');
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items');
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
            $table->unique(['purchase_return_id', 'goods_receive_item_id'], 'pr_items_return_gr_unique');
        });

        Schema::create('purchase_return_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_no')->unique();
            $table->string('supplier_credit_no')->nullable();
            $table->date('invoice_date');
            $table->foreignId('purchase_return_id')->unique()->constrained('purchase_returns');
            $table->foreignId('purchase_invoice_id')->constrained('purchase_invoices');
            $table->foreignId('supplier_id')->constrained('suppliers');
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
        Schema::dropIfExists('purchase_return_invoices');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
