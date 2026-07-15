<?php

use App\Livewire\Inventory\Report\StockCard;
use App\Livewire\Inventory\Report\StockMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\StockMovementService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $category = ProductCategory::create(['code' => 'STOCK-REPORT-CAT', 'name' => 'Stock Report Category']);
    $this->unit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pieces']);
    $this->product = Product::create([
        'name' => 'Stock Report Product',
        'sku' => 'STOCK-REPORT-SKU',
        'category_id' => $category->id,
        'base_unit_id' => $this->unit->id,
        'created_by' => (string) $this->user->id,
    ]);
    $this->warehouse = Warehouse::create([
        'name' => 'Stock Report Warehouse',
        'desc' => 'Stock Report Warehouse',
        'address' => 'Test Address',
    ]);

    $opening = StockAdjustment::create([
        'adjustment_no' => 'ADJ-OPENING',
        'date' => '2026-06-30',
        'type' => 'in',
        'warehouse_id' => $this->warehouse->id,
        'status' => 'approved',
        'created_by' => $this->user->id,
    ]);
    $opening->items()->create([
        'product_id' => $this->product->id,
        'unit_id' => $this->unit->id,
        'qty' => 5,
        'conversion' => 2,
    ]);

    $out = StockAdjustment::create([
        'adjustment_no' => 'ADJ-PERIOD-OUT',
        'date' => '2026-07-10',
        'type' => 'out',
        'warehouse_id' => $this->warehouse->id,
        'status' => 'approved',
        'created_by' => $this->user->id,
    ]);
    $out->items()->create([
        'product_id' => $this->product->id,
        'unit_id' => $this->unit->id,
        'qty' => 3,
        'conversion' => 1,
    ]);

    $draft = StockAdjustment::create([
        'adjustment_no' => 'ADJ-DRAFT-IGNORED',
        'date' => '2026-07-11',
        'type' => 'in',
        'warehouse_id' => $this->warehouse->id,
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);
    $draft->items()->create([
        'product_id' => $this->product->id,
        'unit_id' => $this->unit->id,
        'qty' => 100,
        'conversion' => 1,
    ]);
});

it('builds a stock card with opening and running balances from approved transactions', function () {
    $component = new StockCard;
    $component->productFilter = (string) $this->product->id;
    $component->warehouseFilter = (string) $this->warehouse->id;
    $component->dateFrom = '2026-07-01';
    $component->dateTo = '2026-07-31';

    $data = $component->render(app(StockMovementService::class))->getData();
    $movement = $data['movements']->first();

    expect($data['openingBalance'])->toBe(10)
        ->and($movement['reference'])->toBe('ADJ-PERIOD-OUT')
        ->and($movement['quantity_out'])->toBe(3)
        ->and($movement['balance'])->toBe(7);
});

it('summarizes stock movement and excludes draft transactions', function () {
    $component = new StockMovement;
    $component->productFilter = (string) $this->product->id;
    $component->warehouseFilter = (string) $this->warehouse->id;
    $component->dateFrom = '2026-07-01';
    $component->dateTo = '2026-07-31';

    $row = $component->render(app(StockMovementService::class))->getData()['rows']->first();

    expect($row['opening_balance'])->toBe(10)
        ->and($row['quantity_in'])->toBe(0)
        ->and($row['quantity_out'])->toBe(3)
        ->and($row['ending_balance'])->toBe(7);
});

it('records both transfer-out and transfer-in movements in base quantity', function () {
    $destination = Warehouse::create([
        'name' => 'Destination Warehouse',
        'desc' => 'Destination Warehouse',
        'address' => 'Test Address',
    ]);
    $transfer = StockTransfer::create([
        'trf_no' => 'TRF-STOCK-REPORT',
        'date' => '2026-07-12',
        'warehouse_from_id' => $this->warehouse->id,
        'warehouse_to_id' => $destination->id,
        'status' => 'approved',
        'created_by' => $this->user->id,
    ]);
    $transfer->items()->create([
        'product_id' => $this->product->id,
        'unit_id' => $this->unit->id,
        'stock_available' => 20,
        'qty' => 2,
        'conversion' => 3,
    ]);

    $rows = app(StockMovementService::class)
        ->movements($this->product->id, null, '2026-07-12', '2026-07-12')
        ->where('reference', 'TRF-STOCK-REPORT')
        ->values();

    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('type')->all())->toBe(['Transfer Keluar', 'Transfer Masuk'])
        ->and($rows[0]['quantity_out'])->toBe(6)
        ->and($rows[1]['quantity_in'])->toBe(6);
});

it('allows authenticated users to open both stock report pages', function () {
    $this->actingAs($this->user)
        ->get(route('inventory.report.stock-card'))
        ->assertOk()
        ->assertSee('Kartu Stok');

    $this->get(route('inventory.report.stock-movement'))
        ->assertOk()
        ->assertSee('Pergerakan Stok');
});
