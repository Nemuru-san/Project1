<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->after('status');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('ar_payments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->date('payment_date');
            $table->foreignId('sales_order_id')->constrained('sales_orders')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('payment_method', 30)->default('Transfer');
            $table->string('status', 20)->default('Draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sales_order_id', 'status'], 'arp_so_status_index');
            $table->index(['customer_id', 'payment_date'], 'arp_customer_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_payments');
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn('verified_at');
        });
    }
};
