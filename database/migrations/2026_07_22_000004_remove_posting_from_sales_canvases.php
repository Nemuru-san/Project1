<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_canvases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('posted_by');
            $table->dropColumn('posted_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales_canvases', function (Blueprint $table) {
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
        });
    }
};
