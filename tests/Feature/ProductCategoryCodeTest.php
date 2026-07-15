<?php

use App\Livewire\Inventory\MasterProduct\ProductCategory as ProductCategoryComponent;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

it('stores a normalized product category code', function () {
    expect(Schema::hasColumn('product_categories', 'code'))->toBeTrue();

    Livewire::test(ProductCategoryComponent::class)
        ->set('code', ' food-main ')
        ->set('name', 'Makanan Utama')
        ->set('desc', 'Kategori makanan utama')
        ->call('save')
        ->assertHasNoErrors();

    $category = ProductCategory::where('code', 'FOOD-MAIN')->first();

    expect($category)->not->toBeNull()
        ->and($category->name)->toBe('Makanan Utama');
});

it('rejects a duplicate product category code', function () {
    ProductCategory::create([
        'code' => 'FOOD',
        'name' => 'Makanan',
    ]);

    Livewire::test(ProductCategoryComponent::class)
        ->set('code', 'food')
        ->set('name', 'Makanan Lain')
        ->call('save')
        ->assertHasErrors(['code' => 'unique']);
});
