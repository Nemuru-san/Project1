<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Identitas Perusahaan
    |--------------------------------------------------------------------------
    |
    | Dipakai pada kop dokumen cetak (invoice, surat jalan, dsb).
    |
    */

    'name' => env('COMPANY_NAME', env('APP_NAME', 'Perusahaan')),
    'address' => env('COMPANY_ADDRESS', ''),
    'city' => env('COMPANY_CITY', 'Pekanbaru'),
    'phone' => env('COMPANY_PHONE', ''),
    'tax_number' => env('COMPANY_TAX_NUMBER', ''),

    /*
    | Rekening tujuan transfer yang dicetak pada invoice. Dibiarkan kosong
    | berarti memakai rekening bank aktif pertama dari master Bank Account.
    */
    'bank' => [
        'name' => env('COMPANY_BANK_NAME', ''),
        'account_number' => env('COMPANY_BANK_ACCOUNT', ''),
        'account_holder' => env('COMPANY_BANK_HOLDER', ''),
    ],
];
