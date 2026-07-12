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
