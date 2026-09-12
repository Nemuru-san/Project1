<?php

namespace App\Console\Commands;

use App\Services\Sales\SalesmanInactivityService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CheckSalesmanInactivity extends Command
{
    protected $signature = 'salesman:check-inactivity
                            {--date= : Simulasikan pengecekan pada tanggal tertentu (Y-m-d)}';

    protected $description = 'Kirim peringatan dan nonaktifkan salesman yang tidak membuat Penjualan Kanvas terverifikasi selama 3 bulan';

    public function handle(SalesmanInactivityService $service): int
    {
        $today = $this->option('date') ? CarbonImmutable::parse($this->option('date')) : null;

        $result = $service->run($today);

        $this->info("Peringatan dikirim: {$result['warned']}, salesman dinonaktifkan: {$result['deactivated']}.");

        return self::SUCCESS;
    }
}
