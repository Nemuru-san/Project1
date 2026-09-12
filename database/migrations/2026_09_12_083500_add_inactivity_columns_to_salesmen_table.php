<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salesmen', function (Blueprint $table) {
            // Titik awal hitungan inaktivitas (diisi saat dibuat / diaktifkan kembali).
            $table->timestamp('activity_checkpoint_at')->nullable()->after('is_active');
            // Kapan notifikasi peringatan terakhir dikirim (agar tidak dikirim berulang).
            $table->timestamp('inactivity_warned_at')->nullable()->after('activity_checkpoint_at');
            // Kapan salesman dinonaktifkan otomatis oleh sistem.
            $table->timestamp('deactivated_at')->nullable()->after('inactivity_warned_at');
        });

        DB::table('salesmen')->update(['activity_checkpoint_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('salesmen', function (Blueprint $table) {
            $table->dropColumn(['activity_checkpoint_at', 'inactivity_warned_at', 'deactivated_at']);
        });
    }
};
