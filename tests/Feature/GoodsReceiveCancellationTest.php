<?php

use App\Livewire\Purchasing\Transaction\GoodsReceive as GoodsReceiveComponent;
use App\Models\GoodsReceive;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;

it('cancels a received goods receive by reversing stock and recalculating purchase order status', function () {
    $user = User::factory()->create();

    $supplier = Supplier::create([
        'code' => 'SUP-001',
        'name' => 'Test Supplier',
        'address' => 'Test Address',
        'contact' => 'Test Contact',
        'created_by' => (string) $user->id,
    ]);

    $category = ProductCategory::create([
        'code' => 'TEST-CAT',
        'name' => 'Test Category',
    ]);

    $unit = ProductUnit::create([
        'code' => 'PCS',
        'name' => 'Pcs',
    ]);

    $product = Product::create([
        'name' => 'Test Product',
        'sku' => 'SKU-001',
        'category_id' => $category->id,
        'created_by' => (string) $user->id,
    ]);

    $price = ProductPrice::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'conversion' => 1,
        'price' => 10000,
    ]);

    $warehouse = Warehouse::create([
        'name' => 'WH-001',
        'desc' => 'Main Warehouse',
        'address' => 'Warehouse Address',
    ]);

    $purchaseOrder = PurchaseOrder::create([
        'code' => 'PO-TEST-001',
        'date' => now()->toDateString(),
        'supplier_id' => $supplier->id,
        'user_id' => $user->id,
        'total_price' => 100000,
        'tax' => false,
        'ppn' => 0,
        'purchase_note' => null,
        'gross' => 100000,
        'nett' => 100000,
        'status' => PurchaseOrder::STATUS_RECEIVED,
    ]);

    $purchaseOrderItem = $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'price_id' => $price->id,
        'unit_id' => $unit->id,
        'qty' => 10,
        'price' => 10000,
        'conversion' => 1,
        'qty_base' => 10,
        'total_harga' => 100000,
        'disc' => 0,
    ]);

    $stockBalance = StockBalance::create([
        'warehouse_id' => $warehouse->id,
        'product_id' => $product->id,
        'quantity' => 15,
    ]);

    $goodsReceive = GoodsReceive::create([
        'code' => 'GR-TEST-001',
        'date' => now()->toDateString(),
        'supplier_id' => $supplier->id,
        'purchase_order_id' => $purchaseOrder->id,
        'status' => GoodsReceive::STATUS_RECEIVED,
        'created_by' => $user->id,
    ]);

    $goodsReceive->items()->create([
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'unit_id' => $unit->id,
        'conversion' => 1,
        'qty_order' => 10,
        'qty_received' => 10,
        'qty_outstanding' => 0,
        'qty_base' => 10,
    ]);

    $component = new GoodsReceiveComponent;
    $component->selectedGR = $goodsReceive;
    $component->selectedStatus = GoodsReceive::STATUS_CANCELLED;

    $component->updateStatus();

    expect($goodsReceive->fresh()->status)->toBe(GoodsReceive::STATUS_CANCELLED)
        ->and($stockBalance->fresh()->quantity)->toBe(5)
        ->and($purchaseOrder->fresh()->status)->toBe(PurchaseOrder::STATUS_APPROVED);
});

it('keeps partially received purchase orders available for the next goods receive', function () {
    $user = User::factory()->create();

    $supplier = Supplier::create([
        'code' => 'SUP-001',
        'name' => 'Test Supplier',
        'address' => 'Test Address',
        'contact' => 'Test Contact',
        'created_by' => (string) $user->id,
    ]);

    $category = ProductCategory::create([
        'code' => 'TEST-CAT',
        'name' => 'Test Category',
    ]);

    $unit = ProductUnit::create([
        'code' => 'PCS',
        'name' => 'Pcs',
    ]);

    $product = Product::create([
        'name' => 'Test Product',
        'sku' => 'SKU-001',
        'category_id' => $category->id,
        'created_by' => (string) $user->id,
    ]);

    $price = ProductPrice::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'conversion' => 1,
        'price' => 10000,
    ]);

    $warehouse = Warehouse::create([
        'name' => 'WH-001',
        'desc' => 'Main Warehouse',
        'address' => 'Warehouse Address',
    ]);

    $purchaseOrder = PurchaseOrder::create([
        'code' => 'PO-TEST-002',
        'date' => now()->toDateString(),
        'supplier_id' => $supplier->id,
        'user_id' => $user->id,
        'total_price' => 1500000,
        'tax' => false,
        'ppn' => 0,
        'purchase_note' => null,
        'gross' => 1500000,
        'nett' => 1500000,
        'status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
    ]);

    $purchaseOrderItem = $purchaseOrder->items()->create([
        'product_id' => $product->id,
        'price_id' => $price->id,
        'unit_id' => $unit->id,
        'qty' => 150,
        'price' => 10000,
        'conversion' => 1,
        'qty_base' => 150,
        'total_harga' => 1500000,
        'disc' => 0,
    ]);

    $goodsReceive = GoodsReceive::create([
        'code' => 'GR-TEST-002',
        'date' => now()->toDateString(),
        'supplier_id' => $supplier->id,
        'purchase_order_id' => $purchaseOrder->id,
        'status' => GoodsReceive::STATUS_RECEIVED,
        'created_by' => $user->id,
    ]);

    $goodsReceive->items()->create([
        'purchase_order_item_id' => $purchaseOrderItem->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'unit_id' => $unit->id,
        'conversion' => 1,
        'qty_order' => 150,
        'qty_received' => 100,
        'qty_outstanding' => 50,
        'qty_base' => 100,
    ]);

    $view = (new GoodsReceiveComponent)->render();
    $purchaseOrders = $view->getData()['purchaseOrders'];

    expect($purchaseOrders->pluck('id')->all())->toContain($purchaseOrder->id);
});
