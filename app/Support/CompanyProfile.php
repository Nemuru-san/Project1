<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Arr;

/**
 * Identitas perusahaan untuk kop dokumen cetak. Diisi dari menu Profil Perusahaan (tabel settings),
 * dengan config/company.php (.env) sebagai nilai awal bila belum pernah diubah lewat aplikasi.
 */
class CompanyProfile
{
    public const FIELDS = [
        'name', 'address', 'city', 'phone', 'email', 'tax_number',
        'bank.name', 'bank.account_number', 'bank.account_holder',
    ];

    private static ?array $cache = null;

    public static function get(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $profile = config('company');
        $stored = Setting::query()
            ->whereIn('key', array_map(fn (string $field) => 'company.'.$field, self::FIELDS))
            ->pluck('value', 'key');

        foreach (self::FIELDS as $field) {
            if ($stored->has('company.'.$field)) {
                Arr::set($profile, $field, (string) $stored->get('company.'.$field));
            }
        }

        $profile['email'] ??= '';

        return self::$cache = $profile;
    }

    public static function save(array $values, ?int $userId = null): void
    {
        foreach (self::FIELDS as $field) {
            Setting::set('company.'.$field, trim((string) Arr::get($values, $field, '')), $userId);
        }

        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
