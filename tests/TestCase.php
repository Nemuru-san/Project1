<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pastikan test tidak pernah menyentuh database asli.
     *
     * RefreshDatabase menjalankan migrate:fresh, jadi kalau koneksi test
     * terlanjur mengarah ke MySQL (misalnya karena config ter-cache
     * mengalahkan setelan phpunit.xml) seluruh isi database akan terhapus.
     *
     * Pemeriksaan diletakkan di sini, bukan di setUp(), karena setUp() milik
     * parent sudah menjalankan RefreshDatabase lebih dulu — terlambat.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || ! in_array($database, [':memory:', null], true)) {
            throw new RuntimeException(
                "Test dibatalkan sebelum menyentuh database: koneksi test adalah [{$connection}: {$database}], ".
                'bukan sqlite in-memory. Jalankan "php artisan config:clear" lalu ulangi.'
            );
        }

        return $app;
    }
}
