<?php

use App\Livewire\Inventory\MasterProduct\ProductMaster as ProductMasterComponent;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\User;
use Livewire\Livewire;

it('saves base unit without forcing it into the first price row', function () {
    $user = User::factory()->create();

    $category = ProductCategory::create([
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
        ->set('sku', 'SKU-FLEX-001')
        ->set('name', 'Flexible Price Product')
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
        ->and($priceRows)->toHaveCount(2)
        ->and($priceRows->pluck('unit_id')->all())->toBe([$box->id, $pcs->id])
        ->and($priceRows->first()->conversion)->toBe(12)
        ->and($priceRows->last()->conversion)->toBe(1);
});
