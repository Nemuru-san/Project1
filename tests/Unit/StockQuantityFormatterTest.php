<?php

use App\Services\Inventory\StockQuantityFormatter;

it('formats base stock into the largest available product units', function () {
    $formatter = app(StockQuantityFormatter::class);
    $units = [
        ['conversion' => 1, 'code' => 'PCS', 'name' => 'Pcs'],
        ['conversion' => 12, 'code' => 'KTK', 'name' => 'Kotak'],
    ];

    expect($formatter->formatUnits(25, $units))->toBe('2 KTK, 1 PCS')
        ->and($formatter->formatUnits(12, $units))->toBe('1 KTK')
        ->and($formatter->formatUnits(0, $units))->toBe('0 PCS')
        ->and($formatter->formatUnits(-13, $units))->toBe('- 1 KTK, 1 PCS');
});
