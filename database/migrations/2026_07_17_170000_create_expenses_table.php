<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('expense_date');
            $table->foreignId('bank_account_id')->constrained('bank_accounts');
            $table->string('payee')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('status')->default('Draft');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
