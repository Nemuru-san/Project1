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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // nomor invoice internal
            $table->string('supplier_invoice_number')->nullable(); // nomor invoice dari supplier

            $table->date('date');
            $table->date('due_date')->nullable();

            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();

            $table->unsignedBigInteger('sub_total')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);

            $table->boolean('tax')->default(false);
            $table->unsignedBigInteger('tax_amount')->default(0);

            $table->unsignedBigInteger('grand_total')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('remaining_amount')->default(0);

            $table->string('status')->default('Draft');
            // Draft, Posted, Cancelled

            $table->string('payment_status')->default('Unpaid');
            // Unpaid, Partial, Paid

            $table->text('note')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
