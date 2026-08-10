<?php

use App\Livewire\Purchasing\Transaction\PurchaseOrder as PurchaseOrderComponent;

it('recalculates purchase order totals when a whole item row is synced without tax', function () {
    $component = new PurchaseOrderComponent;

    $component->tax = false;
    $component->items = [
        [
            'qty' => 2,
            'conversion' => 1,
            'price' => 50000,
            'disc' => 10000,
        ],
    ];

    $component->updatedItems($component->items[0], '0');

    expect($component->gross)->toBe(100000)
        ->and($component->totalDisc)->toBe(10000)
        ->and($component->ppn)->toBe(0)
        ->and($component->nett)->toBe(90000)
        ->and($component->items[0]['subtotal'])->toBe(90000);
});
it('clears the manual price and its display when the purchase unit changes', function () {
    $component = new PurchaseOrderComponent;

    $component->items = [
        [
            'price_id' => 1,
            'unit_id' => 10,
            'unit_name' => 'Pcs',
            'conversion' => 1,
            'qty' => 2,
            'qty_base' => 2,
            'price' => 5000,
            'price_display' => '5.000',
            'disc' => 0,
            'prices' => [
                ['id' => 1, 'unit_id' => 10, 'unit_name' => 'Pcs', 'conversion' => 1],
                ['id' => 2, 'unit_id' => 20, 'unit_name' => 'Kotak', 'conversion' => 12],
            ],
        ],
    ];

    $component->updatedItems(2, '0.price_id');

    expect($component->items[0]['unit_id'])->toBe(20)
        ->and($component->items[0]['conversion'])->toBe(12)
        ->and($component->items[0]['price'])->toBeNull()
        ->and($component->items[0]['price_display'])->toBe('')
        ->and($component->gross)->toBe(0)
        ->and($component->nett)->toBe(0);
});
