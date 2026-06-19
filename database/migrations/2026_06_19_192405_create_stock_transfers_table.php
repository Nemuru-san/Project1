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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('trf_no')->unique();
            $table->date('date');

            $table->foreignId('warehouse_from_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->foreignId('warehouse_to_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->text('notes')->nullable();

            $table->string('status')->default('draft');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
