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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('prd_code');
            $table->string('prd_name');
            $table->string('prd_desc')->nullable();
            $table->string('prd_category');
            $table->string('prd_brand');
            $table->string('prd_unit');
            $table->string('prd_barcode');
            $table->boolean('status')->default(1);
            $table->string('prd_image')->nullable();
            $table->string('created_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
