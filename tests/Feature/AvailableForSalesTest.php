<?php

use App\Livewire\Inventory\Report\StockBalance as StockBalanceComponent;
use App\Livewire\Inventory\Report\StockCard;
use App\Livewire\Inventory\Report\StockMovement;
use App\Livewire\Sales\Transaction\PreOrder;
use App\Livewire\Sales\Transaction\SalesCanvas;
use App\Livewire\Sales\Transaction\SalesOrder;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\SalesOrder as SalesOrderModel;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\AvailableForSalesService;
use Livewire\Livewire;

beforeEach(function () {
    $role = Role::create(['name' => 'Super Admin', 'permissions' => ['*']]);
    $this->user = User::factory()->for($role)->create();
    $this->customer = Customer::factory()->create(['is_active' => true]);
    $category = ProductCategory::create(['code' => 'AFS-CAT', 'name' => 'AFS Category']);
    $this->unit = ProductUnit::create(['code' => 'BOX', 'name' => 'Box']);
    $this->baseUnit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pcs']);
    $this->warehouse = Warehouse::create([
        'name' => 'AFS Warehouse',
        'desc' => 'AFS Warehouse',
        'address' => 'Jakarta',
    ]);
    $this->product = Product::create([
        'name' => 'AFS Product',
        'sku' => 'AFS-SKU',
        'category_id' => $category->id,
        'base_unit_id' => $this->baseUnit->id,
        'created_by' => (string) $this->user->id,
    ]);
    ProductPrice::create([
        'product_id' => $this->product->id,
        'unit_id' => $this->unit->id,
        'conversion' => 12,
        'price' => 120000,
    ]);
    ProductPrice::create([
        'product_id' => $this->product->id,
        'unit_id' => $this->baseUnit->id,
        'conversion' => 1,
        'price' => 10000,
    ]);
    StockBalance::create([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product->id,
        'quantity' => 100,
    ]);

    $this->order = SalesOrderModel::create([
        'order_no' => 'SO-AFS-001',
        'date' => '2026-07-28',
        'customer_id' => $this->customer->id,
        'is_taxed' => false,
        'subtotal' => 240000,
        'grand_total' => 240000,
        'amount_due' => 240000,
        'status' => 'draft',
        'created_by' => $this->user->id,
    ]);
    $this->order->items()->create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'unit_id' => $this->unit->id,
        'qty' => 2,
        'conversion' => 12,
        'unit_price' => 120000,
        'discount_amount' => 0,
        'line_total' => 240000,
    ]);

    $this->actingAs($this->user);
});

it('calculates AFS from QOH minus active sales order bookings', function () {
    $service = app(AvailableForSalesService::class);

    expect($service->summary($this->product->id, $this->warehouse->id))->toBe([
        'quantity_on_hand' => 100,
        'reserved' => 24,
        'available_for_sales' => 76,
    ])->and($service->available(
        $this->product->id,
        $this->warehouse->id,
        $this->order->id,
    ))->toBe(100);

    $this->order->update(['status' => 'completed']);
    expect($service->available($this->product->id, $this->warehouse->id))->toBe(100);

    $this->order->update(['status' => 'draft']);
    $this->order->delete();

    expect($service->available($this->product->id, $this->warehouse->id))->toBe(100)
        ->and((new StockBalanceComponent)->formatStockQuantity($this->product, -20))->toStartWith('- ');
});

it('uses AFS in every sales transaction form', function () {
    foreach ([SalesCanvas::class, PreOrder::class, SalesOrder::class] as $component) {
        Livewire::test($component)
            ->call('openCreate')
            ->call('addProduct', $this->product->id)
            ->assertSet('items.0.stock_available', 76)
            ->assertSet('items.0.stock_available_display', '6 BOX, 4 PCS')
            ->assertSee('AFS (Stok Tersedia)')
            ->assertSee('6 BOX, 4 PCS');
    }

    Livewire::test(SalesOrder::class)
        ->call('openEdit', $this->order->id)
        ->assertSet('items.0.stock_available', 100)
        ->assertSet('items.0.stock_available_display', '8 BOX, 4 PCS');
});

it('shows real sales order bookings and AFS in stock balance', function () {
    Livewire::test(StockBalanceComponent::class)
        ->assertSee('AFS')
        ->assertDontSee('Booking SO')
        ->call('openDetail', $this->product->id, $this->warehouse->id)
        ->assertSet('selectedStock.quantity_on_hand', 100)
        ->assertSet('selectedStock.reserved', 24)
        ->assertSet('selectedStock.available_for_sales', 76)
        ->assertSet('selectedStock.available_for_sales_display', '6 BOX, 4 PCS')
        ->assertSee('6 BOX, 4 PCS')
        ->assertSee('SO-AFS-001')
        ->assertSee($this->customer->name);
});

it('shows AFS summaries in stock card and stock movement reports', function () {
    Livewire::test(StockCard::class)
        ->set('productFilter', (string) $this->product->id)
        ->set('warehouseFilter', (string) $this->warehouse->id)
        ->assertSee('AFS saat ini')
        ->assertDontSee('Booking SO')
        ->assertSee('6 BOX, 4 PCS');

    Livewire::test(StockMovement::class)
        ->assertSee('QOH Saat Ini')
        ->assertSee('AFS Saat Ini')
        ->assertDontSee('Booking SO');
});
