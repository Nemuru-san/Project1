<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_orders', function (Blueprint $table) {
            $table->id();
            $table->string('pre_order_no', 50)->unique();
            $table->date('date');
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->boolean('is_taxed')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(11);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount_total')->default(0);
            $table->unsignedBigInteger('tax_amount')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'date']);
            $table->index(['status', 'date']);
        });

        Schema::create('pre_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_order_id')->constrained('pre_orders')->cascadeOnDelete();
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

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('pre_order_id')->nullable()->unique()->after('sales_canvas_id')->constrained('pre_orders')->restrictOnDelete();
            $table->unsignedBigInteger('dp_amount')->default(0)->after('grand_total');
            $table->unsignedBigInteger('amount_due')->default(0)->after('dp_amount');
        });
        DB::table('sales_orders')->update(['amount_due' => DB::raw('grand_total')]);

        Schema::create('ar_dp_payments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->date('payment_date');
            $table->foreignId('pre_order_id')->constrained('pre_orders')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('payment_method', 30)->default('Transfer');
            $table->string('status', 20)->default('Draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pre_order_id', 'status']);
            $table->index(['customer_id', 'payment_date']);
        });

        $liabilityId = DB::table('chart_of_accounts')->where('code', '2000')->value('id');
        DB::table('chart_of_accounts')->updateOrInsert(
            ['code' => '2300'],
            [
                'name' => 'Customer Advances',
                'type' => 'Liability',
                'normal_balance' => 'Credit',
                'parent_id' => $liabilityId,
                'is_postable' => true,
                'is_active' => true,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_dp_payments');

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pre_order_id');
            $table->dropColumn(['dp_amount', 'amount_due']);
        });

        Schema::dropIfExists('pre_order_items');
        Schema::dropIfExists('pre_orders');
        DB::table('chart_of_accounts')->where('code', '2300')->delete();
    }
};
