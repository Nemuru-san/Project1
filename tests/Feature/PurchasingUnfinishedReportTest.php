<?php

use App\Livewire\Purchasing\Report\UnfinishedPurchaseInvoice;
use App\Livewire\Purchasing\Report\UnfinishedPurchaseOrder;
use App\Models\GoodsReceive;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->supplier = Supplier::create([
        'code' => 'SUP-REPORT',
        'name' => 'Supplier Report',
        'address' => 'Test Address',
        'contact' => 'Test Contact',
        'created_by' => (string) $this->user->id,
    ]);
});

it('shows only unfinished purchase orders and calculates their outstanding quantity', function () {
    $category = ProductCategory::create(['code' => 'REPORT-CAT', 'name' => 'Report Category']);
    $unit = ProductUnit::create(['code' => 'PCS', 'name' => 'Pieces']);
    $product = Product::create([
        'name' => 'Report Product',
        'sku' => 'REPORT-SKU',
        'category_id' => $category->id,
        'created_by' => (string) $this->user->id,
    ]);
    $price = ProductPrice::create([
        'product_id' => $product->id,
        'unit_id' => $unit->id,
        'conversion' => 1,
        'price' => 1000,
    ]);
    $warehouse = Warehouse::create([
        'name' => 'Report Warehouse',
        'desc' => 'Report Warehouse',
        'address' => 'Test Address',
    ]);

    $unfinished = PurchaseOrder::create([
        'code' => 'PO-UNFINISHED',
        'date' => now()->toDateString(),
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'total_price' => 10000,
        'tax' => false,
        'ppn' => 0,
        'gross' => 10000,
        'nett' => 10000,
        'status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
    ]);
    $item = $unfinished->items()->create([
        'product_id' => $product->id,
        'price_id' => $price->id,
        'unit_id' => $unit->id,
        'qty' => 10,
        'price' => 1000,
        'conversion' => 1,
        'qty_base' => 10,
        'total_harga' => 10000,
        'disc' => 0,
    ]);
    $goodsReceive = GoodsReceive::create([
        'code' => 'GR-REPORT',
        'date' => now()->toDateString(),
        'supplier_id' => $this->supplier->id,
        'purchase_order_id' => $unfinished->id,
        'status' => GoodsReceive::STATUS_RECEIVED,
        'created_by' => $this->user->id,
    ]);
    $goodsReceive->items()->create([
        'purchase_order_item_id' => $item->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'unit_id' => $unit->id,
        'conversion' => 1,
        'qty_order' => 10,
        'qty_received' => 4,
        'qty_outstanding' => 6,
        'qty_base' => 4,
    ]);

    PurchaseOrder::create([
        'code' => 'PO-FINISHED',
        'date' => now()->toDateString(),
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'total_price' => 10000,
        'tax' => false,
        'ppn' => 0,
        'gross' => 10000,
        'nett' => 10000,
        'status' => PurchaseOrder::STATUS_RECEIVED,
    ]);

    Livewire::test(UnfinishedPurchaseOrder::class)
        ->assertSee('PO-UNFINISHED')
        ->assertSeeInOrder(['10', '4', '6'])
        ->assertDontSee('PO-FINISHED');
});

it('shows only posted purchase invoices with a remaining balance', function () {
    $purchaseOrder = PurchaseOrder::create([
        'code' => 'PO-INVOICE-REPORT',
        'date' => now()->toDateString(),
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'total_price' => 100000,
        'tax' => false,
        'ppn' => 0,
        'gross' => 100000,
        'nett' => 100000,
        'status' => PurchaseOrder::STATUS_RECEIVED,
    ]);

    foreach ([
        ['INV-UNFINISHED', PurchaseInvoice::STATUS_POSTED, PurchaseInvoice::PAYMENT_PARTIAL_PAID, 40000],
        ['INV-FINISHED', PurchaseInvoice::STATUS_POSTED, PurchaseInvoice::PAYMENT_PAID, 0],
        ['INV-DRAFT', PurchaseInvoice::STATUS_DRAFT, PurchaseInvoice::PAYMENT_UNPAID, 100000],
    ] as [$code, $status, $paymentStatus, $remaining]) {
        PurchaseInvoice::create([
            'code' => $code,
            'date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'supplier_id' => $this->supplier->id,
            'purchase_order_id' => $purchaseOrder->id,
            'sub_total' => 100000,
            'grand_total' => 100000,
            'paid_amount' => 100000 - $remaining,
            'remaining_amount' => $remaining,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'created_by' => $this->user->id,
        ]);
    }

    Livewire::test(UnfinishedPurchaseInvoice::class)
        ->assertSee('INV-UNFINISHED')
        ->assertDontSee('INV-FINISHED')
        ->assertDontSee('INV-DRAFT');
});

it('allows authenticated users to open both purchasing report pages', function () {
    $this->actingAs($this->user)
        ->get(route('purchases.report.unfinished-purchase-order'))
        ->assertOk()
        ->assertSee('PO Belum Selesai');

    $this->get(route('purchases.report.unfinished-purchase-invoice'))
        ->assertOk()
        ->assertSee('Faktur Belum Selesai');
});
