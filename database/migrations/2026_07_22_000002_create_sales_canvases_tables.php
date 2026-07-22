<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_canvases', function (Blueprint $table) {
            $table->id();
            $table->string('canvas_no', 50)->unique();
            $table->date('date');
            $table->foreignId('salesman_id')->constrained('salesmen')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->boolean('is_taxed')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(11);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['salesman_id', 'date']);
            $table->index(['customer_id', 'date']);
            $table->index(['status', 'date']);
        });

        Schema::create('sales_canvas_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_canvas_id')->constrained('sales_canvases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('product_units')->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('conversion')->default(1);
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_canvas_items');
        Schema::dropIfExists('sales_canvases');
    }
};
