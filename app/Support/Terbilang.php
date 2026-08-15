<?php

namespace App\Support;

class Terbilang
{
    private const UNITS = [
        '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
        'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas',
    ];

    /**
     * Ubah angka menjadi kata dalam Bahasa Indonesia, mis. 6.200.000 => "Enam Juta Dua Ratus Ribu".
     */
    public static function make(int $number): string
    {
        if ($number < 0) {
            return 'Minus '.self::make(abs($number));
        }
        if ($number === 0) {
            return 'Nol';
        }

        return self::words($number);
    }

    /**
     * Terbilang lengkap dengan satuan mata uang, mis. "Enam Juta Dua Ratus Ribu Rupiah".
     */
    public static function rupiah(int $number): string
    {
        return self::make($number).' Rupiah';
    }

    private static function words(int $number): string
    {
        if ($number < 12) {
            return self::UNITS[$number];
        }
        if ($number < 20) {
            return self::words($number - 10).' Belas';
        }
        if ($number < 100) {
            return self::words(intdiv($number, 10)).' Puluh'.self::remainder($number % 10);
        }
        if ($number < 200) {
            return 'Seratus'.self::remainder($number % 100);
        }
        if ($number < 1000) {
            return self::words(intdiv($number, 100)).' Ratus'.self::remainder($number % 100);
        }
        if ($number < 2000) {
            return 'Seribu'.self::remainder($number % 1000);
        }

        foreach ([1_000_000_000_000 => 'Triliun', 1_000_000_000 => 'Miliar', 1_000_000 => 'Juta', 1000 => 'Ribu'] as $value => $label) {
            if ($number >= $value) {
                return self::words(intdiv($number, $value)).' '.$label.self::remainder($number % $value);
            }
        }

        return '';
    }

    private static function remainder(int $rest): string
    {
        return $rest > 0 ? ' '.self::words($rest) : '';
    }
}
