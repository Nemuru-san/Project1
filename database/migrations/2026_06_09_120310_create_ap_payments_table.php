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
        Schema::create('ap_payments', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->date('payment_date');

            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();

            $table->unsignedBigInteger('total_amount')->default(0);

            $table->string('payment_method')->nullable();
            // cash, transfer, giro, etc

            $table->string('status')->default('Posted');
            // Draft, Posted, Cancelled

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
        Schema::dropIfExists('ap_payments');
    }
};
