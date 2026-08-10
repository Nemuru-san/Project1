<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('dp_amount')->default(0)->after('grand_total');
            $table->string('dp_payment_status', 20)->default('unpaid')->after('dp_amount')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pre_orders', function (Blueprint $table) {
            $table->dropIndex(['dp_payment_status']);
            $table->dropColumn(['dp_amount', 'dp_payment_status']);
        });
    }
};
