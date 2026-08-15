<?php

use App\Livewire\Inventory\MasterProduct\ProductMaster as ProductMasterComponent;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\User;
use App\Support\Ean13;
use Livewire\Livewire;

it('saves base unit without forcing it into the first price row', function () {
    $user = User::factory()->create();

    $category = ProductCategory::create([
        'code' => 'PACKAGING',
        'name' => 'Packaging',
    ]);

    $pcs = ProductUnit::create([
        'code' => 'PCS',
        'name' => 'Piece',
    ]);

    $box = ProductUnit::create([
        'code' => 'BOX',
        'name' => 'Box',
    ]);

    $this->actingAs($user);

    Livewire::test(ProductMasterComponent::class)
        ->call('openCreate')
        ->assertSee('PACKAGING - Packaging')
        ->set('sku', 'SKU-FLEX-001')
        ->set('name', 'Flexible Price Product')
        ->set('barcode', '8991001000012')
        ->set('category_id', $category->id)
        ->set('base_unit_id', $pcs->id)
        ->set('priceRowsJson', json_encode([
            [
                'unit_id' => $box->id,
                'conversion' => 12,
                'price' => 120000,
            ],
            [
                'unit_id' => $pcs->id,
                'conversion' => 1,
                'price' => 10000,
            ],
        ]))
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('sku', 'SKU-FLEX-001')->firstOrFail();
    $priceRows = $product->prices()->orderBy('id')->get();

    expect($product->base_unit_id)->toBe($pcs->id)
        ->and($product->barcode)->toBe('8991001000012')
        ->and($priceRows)->toHaveCount(2)
        ->and($priceRows->pluck('unit_id')->all())->toBe([$box->id, $pcs->id])
        ->and($priceRows->first()->conversion)->toBe(12)
        ->and($priceRows->last()->conversion)->toBe(1);
});

it('generates a sequential internal barcode when the barcode field is left empty', function () {
    $user = User::factory()->create();
    $category = ProductCategory::create(['code' => 'SNACK', 'name' => 'Snack']);
    $pcs = ProductUnit::create(['code' => 'PCS', 'name' => 'Piece']);

    $this->actingAs($user);

    $save = function (string $sku, string $name, string $barcode) use ($category, $pcs) {
        Livewire::test(ProductMasterComponent::class)
            ->call('openCreate')
            ->set('sku', $sku)
            ->set('name', $name)
            ->set('barcode', $barcode)
            ->set('category_id', $category->id)
            ->set('base_unit_id', $pcs->id)
            ->set('priceRowsJson', json_encode([
                ['unit_id' => $pcs->id, 'conversion' => 1, 'price' => 5000],
            ]))
            ->call('save')
            ->assertHasNoErrors();

        return Product::where('sku', $sku)->firstOrFail();
    };

    $first = $save('SKU-AUTO-001', 'Produk Barcode Otomatis 1', '');
    $second = $save('SKU-AUTO-002', 'Produk Barcode Otomatis 2', '');
    $manual = $save('SKU-AUTO-003', 'Produk Barcode Manual', '8991001000029');

    expect($first->barcode)->toBe('2000000000015')
        ->and($second->barcode)->toBe('2000000000022')
        ->and(Ean13::isValid($first->barcode))->toBeTrue()
        ->and(Ean13::isValid($second->barcode))->toBeTrue()
        ->and($manual->barcode)->toBe('8991001000029');
});
