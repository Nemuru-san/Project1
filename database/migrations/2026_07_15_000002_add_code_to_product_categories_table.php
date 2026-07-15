<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('id');
        });

        DB::table('product_categories')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($category) {
                DB::table('product_categories')
                    ->where('id', $category->id)
                    ->update(['code' => 'CAT-'.str_pad((string) $category->id, 4, '0', STR_PAD_LEFT)]);
            });

        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('code', 50)->nullable(false)->change();
            $table->unique('code');
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
