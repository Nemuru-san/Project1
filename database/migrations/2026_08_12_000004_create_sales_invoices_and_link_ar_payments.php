<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 50)->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->foreignId('sales_order_id')->unique('si_sales_order_unique')->constrained('sales_orders')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->unsignedBigInteger('dp_amount')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('amount_due')->default(0);
            $table->string('status', 20)->default('Draft');
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'invoice_date'], 'si_customer_date_index');
            $table->index(['status', 'invoice_date'], 'si_status_date_index');
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_invoice_id')->constrained('sales_invoices')->cascadeOnDelete();
            $table->foreignId('sales_order_item_id')->constrained('sales_order_items')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('product_units')->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('conversion')->default(1);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('line_total');
            $table->timestamps();

            $table->unique(['sales_invoice_id', 'sales_order_item_id'], 'si_item_order_unique');
        });

        Schema::table('ar_payments', function (Blueprint $table) {
            $table->foreignId('sales_invoice_id')->nullable()->after('sales_order_id')->constrained('sales_invoices')->restrictOnDelete();
            $table->index(['sales_invoice_id', 'status'], 'arp_invoice_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('ar_payments', function (Blueprint $table) {
            $table->dropIndex('arp_invoice_status_index');
            $table->dropConstrainedForeignId('sales_invoice_id');
        });
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
    }
};
