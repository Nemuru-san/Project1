<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Hak membatalkan transaksi kini berupa izin "transactions.cancel" (sebelumnya terikat
 * nama role Owner). Berikan izin itu ke role Owner yang sudah ada, dan hapus izin
 * "Termin Pembayaran" yang modulnya tidak dipakai.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->get(['id', 'name', 'permissions'])->each(function ($role) {
            $permissions = json_decode($role->permissions ?? '[]', true) ?: [];
            $updated = array_values(array_diff($permissions, ['finance.master.payment-terms']));

            if ($role->name === 'Owner' && ! in_array('transactions.cancel', $updated, true)) {
                $updated[] = 'transactions.cancel';
            }

            if ($updated !== $permissions) {
                DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($updated)]);
            }
        });
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'Owner')->get(['id', 'permissions'])->each(function ($role) {
            $permissions = array_values(array_diff(json_decode($role->permissions ?? '[]', true) ?: [], ['transactions.cancel']));
            DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
        });
    }
};
