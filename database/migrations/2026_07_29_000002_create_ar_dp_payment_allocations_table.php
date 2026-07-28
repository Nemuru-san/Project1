<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ar_dp_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ar_dp_payment_id')->constrained('ar_dp_payments')->cascadeOnDelete();
            $table->foreignId('pre_order_id')->constrained('pre_orders')->restrictOnDelete();
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            $table->unique(['ar_dp_payment_id', 'pre_order_id'], 'ar_dp_payment_pre_order_unique');
            $table->index(['pre_order_id', 'ar_dp_payment_id']);
        });

        DB::table('ar_dp_payments')
            ->select(['id', 'pre_order_id', 'amount', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->each(function ($payment) {
                DB::table('ar_dp_payment_allocations')->insert([
                    'ar_dp_payment_id' => $payment->id,
                    'pre_order_id' => $payment->pre_order_id,
                    'amount' => $payment->amount,
                    'created_at' => $payment->created_at,
                    'updated_at' => $payment->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('ar_dp_payment_allocations');
    }
};
