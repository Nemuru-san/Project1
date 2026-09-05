<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('default_salesman_id')
                ->nullable()
                ->after('tax_number')
                ->constrained('salesmen')
                ->nullOnDelete();
        });

        // Pertahankan relasi lama selama migrasi. Jika customer pernah menjadi
        // default beberapa salesman, salesman pertama menjadi pemilik awalnya.
        DB::table('salesmen')
            ->whereNotNull('default_customer_id')
            ->orderBy('id')
            ->get(['id', 'default_customer_id'])
            ->each(function (object $salesman): void {
                DB::table('customers')
                    ->where('id', $salesman->default_customer_id)
                    ->whereNull('default_salesman_id')
                    ->update(['default_salesman_id' => $salesman->id]);
            });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('salesman_id')
                ->nullable()
                ->after('pre_order_id')
                ->constrained('salesmen')
                ->nullOnDelete();
            $table->index(['salesman_id', 'date']);
        });

        DB::table('sales_orders')
            ->orderBy('id')
            ->get(['id', 'sales_canvas_id', 'customer_id'])
            ->each(function (object $order): void {
                $salesmanId = $order->sales_canvas_id
                    ? DB::table('sales_canvases')->where('id', $order->sales_canvas_id)->value('salesman_id')
                    : null;
                $salesmanId ??= DB::table('customers')->where('id', $order->customer_id)->value('default_salesman_id');

                DB::table('sales_orders')->where('id', $order->id)->update(['salesman_id' => $salesmanId]);
            });

        Schema::table('salesmen', function (Blueprint $table) {
            $table->dropForeign(['default_customer_address_id']);
            $table->dropForeign(['default_customer_id']);
            $table->dropColumn(['default_customer_address_id', 'default_customer_id']);
        });
    }

    public function down(): void
    {
        Schema::table('salesmen', function (Blueprint $table) {
            $table->foreignId('default_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('default_customer_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
        });

        DB::table('customers')
            ->whereNotNull('default_salesman_id')
            ->orderBy('id')
            ->get(['id', 'default_salesman_id'])
            ->each(function (object $customer): void {
                DB::table('salesmen')
                    ->where('id', $customer->default_salesman_id)
                    ->whereNull('default_customer_id')
                    ->update(['default_customer_id' => $customer->id]);
            });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['salesman_id']);
            $table->dropIndex(['salesman_id', 'date']);
            $table->dropColumn('salesman_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['default_salesman_id']);
            $table->dropColumn('default_salesman_id');
        });
    }
};
