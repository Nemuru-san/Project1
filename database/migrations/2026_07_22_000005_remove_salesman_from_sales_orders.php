<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['salesman_id']);
        });

        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropIndex(['salesman_id', 'date']);
            $table->dropColumn('salesman_id');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->foreignId('salesman_id')->nullable()->after('sales_canvas_id')->constrained('salesmen')->restrictOnDelete();
            $table->index(['salesman_id', 'date']);
        });
    }
};
