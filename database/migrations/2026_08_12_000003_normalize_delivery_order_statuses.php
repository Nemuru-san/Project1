<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Surat Jalan lama belum pernah memotong stok, sehingga aman dianggap draf.
        DB::table('delivery_orders')->where('status', 'issued')->update(['status' => 'draft']);
    }

    public function down(): void
    {
        DB::table('delivery_orders')->where('status', 'draft')->update(['status' => 'issued']);
    }
};
