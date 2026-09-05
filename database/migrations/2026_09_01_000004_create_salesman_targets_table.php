<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salesman_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salesman_id')->constrained('salesmen')->cascadeOnDelete();
            $table->date('target_month');
            $table->unsignedBigInteger('target_amount');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['salesman_id', 'target_month']);
            $table->index('target_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salesman_targets');
    }
};
