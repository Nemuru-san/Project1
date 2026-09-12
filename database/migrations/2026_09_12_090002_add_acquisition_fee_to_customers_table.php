<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Salesman yang merekrut / menginput customer — berhak atas fee dari semua penjualan customer ini.
            $table->foreignId('acquired_by_salesman_id')->nullable()->after('default_salesman_id')
                ->constrained('salesmen')->nullOnDelete();
            // Persentase fee khusus customer ini; null = pakai default global (settings).
            $table->decimal('acquisition_fee_percent', 5, 2)->nullable()->after('acquired_by_salesman_id');
        });

        // Backfill: customer yang dibuat oleh user salesman → salesman tersebut sebagai perekrut.
        DB::table('customers')
            ->whereNull('acquired_by_salesman_id')
            ->whereNotNull('created_by')
            ->orderBy('id')
            ->each(function (object $customer): void {
                $salesmanId = DB::table('salesmen')->where('user_id', $customer->created_by)->value('id');
                if ($salesmanId) {
                    DB::table('customers')->where('id', $customer->id)->update(['acquired_by_salesman_id' => $salesmanId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['acquired_by_salesman_id']);
            $table->dropColumn(['acquired_by_salesman_id', 'acquisition_fee_percent']);
        });
    }
};
