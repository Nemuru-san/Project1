<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek salesman tidak aktif: kirim peringatan 2 minggu sebelum nonaktif,
// dan nonaktifkan bila 3 bulan tanpa Penjualan Kanvas terverifikasi.
Schedule::command('salesman:check-inactivity')
    ->dailyAt('07:00')
    ->withoutOverlapping();
