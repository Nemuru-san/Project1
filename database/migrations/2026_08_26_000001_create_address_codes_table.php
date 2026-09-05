<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('customer_addresses')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('code')
            ->pluck('code')
            ->map(fn (string $code) => mb_strtoupper(trim($code)))
            ->filter()
            ->unique()
            ->each(fn (string $code) => DB::table('address_codes')->insertOrIgnore([
                'code' => $code,
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        DB::table('customer_addresses')
            ->whereNotNull('code')
            ->get(['id', 'code'])
            ->each(fn (object $address) => DB::table('customer_addresses')
                ->where('id', $address->id)
                ->update(['code' => mb_strtoupper(trim($address->code))]));
    }

    public function down(): void
    {
        Schema::dropIfExists('address_codes');
    }
};
