<?php

namespace App\Support;

class Ean13
{
    /**
     * Awalan GS1 yang dicadangkan untuk pemakaian internal (restricted circulation),
     * aman dipakai barcode buatan sendiri karena tidak bentrok dengan barcode pabrikan.
     */
    public const INTERNAL_PREFIX = '200';

    /**
     * Hitung check digit (digit ke-13) dari 12 digit pertama.
     */
    public static function checkDigit(string $body): string
    {
        $sum = 0;
        foreach (str_split(substr(str_pad($body, 12, '0', STR_PAD_LEFT), 0, 12)) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (string) ((10 - $sum % 10) % 10);
    }

    /**
     * Lengkapi 12 digit menjadi barcode EAN-13 yang valid.
     */
    public static function withCheckDigit(string $body): string
    {
        $body = substr(str_pad($body, 12, '0', STR_PAD_LEFT), 0, 12);

        return $body.self::checkDigit($body);
    }

    /**
     * Bentuk barcode EAN-13 dari nomor urut, mis. 1 => 2000000000015.
     */
    public static function fromSequence(int $sequence, string $prefix = self::INTERNAL_PREFIX): string
    {
        return self::withCheckDigit($prefix.str_pad((string) $sequence, 12 - strlen($prefix), '0', STR_PAD_LEFT));
    }

    public static function isValid(string $code): bool
    {
        return (bool) preg_match('/^\d{13}$/', $code)
            && self::checkDigit(substr($code, 0, 12)) === substr($code, -1);
    }
}
