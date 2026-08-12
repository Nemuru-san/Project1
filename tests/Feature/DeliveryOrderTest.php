<?php

use App\Livewire\Sales\Transaction\DeliveryOrder as DeliveryOrderComponent;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DeliveryOrder;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\AvailableForSalesService;
use App\Services\Inventory\StockMovementService;
use Livewire\Livewire;

function deliveryOrderFixture(): array
{
    $role = Role::create(['name' => 'Petugas Pengiriman', 'permissions' => ['sales.transaction.delivery-order']]);
    $user = User::factory()->for($role)->create();
    $customer = Customer::create([
        'code' => 'CUS-SJ-001', 'name' => 'Pelanggan Surat Jalan',
        'is_active' => true, 'created_by' => $user->id,
    ]);
    $address = CustomerAddress::create([
        'customer_id' => $customer->id, 'code' => 'ADDR-SJ-001', 'label' => 'Gudang Pelanggan',
        'address_type' => 'shipping', 'address' => 'Jl. Pengiriman No. 1', 'city' => 'Jakarta', 'is_primary' => true,
    ]);
    $category = ProductCategory::create(['code' => 'CAT-SJ', 'name' => 'Barang Jadi']);
    $unit = ProductUnit::create(['code' => 'PCS-SJ', 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'Gudang Surat Jalan', 'desc' => 'Gudang pengujian', 'address' => 'Jl. Gudang']);
    $product = Product::create([
        'name' => 'Produk Surat Jalan', 'sku' => 'SKU-SJ-001', 'category_id' => $category->id,
        'base_unit_id' => $unit->id, 'created_by' => (string) $user->id,
    ]);
    $preOrder = PreOrder::create([
        'pre_order_no' => 'PA-SJ-001', 'date' => '2026-08-12', 'customer_id' => $customer->id,
        'customer_address_id' => $address->id, 'grand_total' => 100000, 'dp_amount' => 10000,
        'dp_payment_status' => PreOrder::DP_STATUS_PAID, 'status' => PreOrder::STATUS_SALES_ORDER,
        'created_by' => $user->id,
    ]);
    $salesOrder = SalesOrder::create([
        'order_no' => 'SO-PA-SJ-001', 'date' => '2026-08-12', 'pre_order_id' => $preOrder->id,
        'customer_id' => $customer->id, 'customer_address_id' => $address->id,
        'grand_total' => 100000, 'amount_due' => 90000, 'status' => 'verified', 'created_by' => $user->id,
    ]);
    $salesOrderItem = $salesOrder->items()->create([
        'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id,
        'qty' => 10, 'conversion' => 1, 'unit_price' => 10000, 'line_total' => 100000,
    ]);
    $stockBalance = StockBalance::create([
        'warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'quantity' => 10,
    ]);

    return compact('role', 'user', 'customer', 'address', 'product', 'warehouse', 'unit', 'preOrder', 'salesOrder', 'salesOrderItem', 'stockBalance');
}

it('creates draft delivery orders and only updates stock and sales order after shipment confirmation', function () {
    $data = deliveryOrderFixture();
    $this->actingAs($data['user']);

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $data['salesOrder']->id)
        ->assertSet('customerId', $data['customer']->id)
        ->assertSet('customerAddressId', $data['address']->id)
        ->assertSet('items.0.qty_order', 10)
        ->assertSet('items.0.qty_outstanding', 10)
        ->set('items.0.qty_delivered', 6)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    $firstDelivery = DeliveryOrder::firstOrFail();
    expect($data['salesOrder']->fresh()->status)->toBe('verified')
        ->and($data['stockBalance']->fresh()->quantity)->toBe(10)
        ->and($firstDelivery->status)->toBe(DeliveryOrder::STATUS_DRAFT)
        ->and($firstDelivery->items->first()->qty_outstanding)->toBe(4);

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openConfirmShipment', $firstDelivery->id)
        ->call('confirmShipment')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    expect($data['salesOrder']->fresh()->status)->toBe('processing')
        ->and($data['stockBalance']->fresh()->quantity)->toBe(4)
        ->and($firstDelivery->fresh()->status)->toBe(DeliveryOrder::STATUS_SHIPPED);

    $movement = app(StockMovementService::class)
        ->movements($data['product']->id, $data['warehouse']->id)
        ->firstWhere('reference', $firstDelivery->delivery_no);
    expect($movement['type'])->toBe('Pengiriman Penjualan')
        ->and($movement['quantity_out'])->toBe(6)
        ->and(app(AvailableForSalesService::class)->summary($data['product']->id, $data['warehouse']->id))->toBe([
            'quantity_on_hand' => 4,
            'reserved' => 4,
            'available_for_sales' => 0,
        ]);

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $data['salesOrder']->id)
        ->assertSet('items.0.qty_already_allocated', 6)
        ->assertSet('items.0.qty_outstanding', 4)
        ->set('items.0.qty_delivered', 4)
        ->call('save')
        ->assertHasNoErrors();

    $secondDelivery = DeliveryOrder::latest('id')->firstOrFail();
    expect($data['salesOrder']->fresh()->status)->toBe('processing')
        ->and($secondDelivery->status)->toBe(DeliveryOrder::STATUS_DRAFT);

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openConfirmShipment', $secondDelivery->id)
        ->call('confirmShipment')
        ->assertHasNoErrors();

    expect($data['salesOrder']->fresh()->deliveryOrders)->toHaveCount(2)
        ->and($data['salesOrder']->fresh()->status)->toBe('completed')
        ->and($data['stockBalance']->fresh()->quantity)->toBe(0);
});
it('rejects a delivery quantity greater than the remaining sales order quantity', function () {
    $data = deliveryOrderFixture();
    $this->actingAs($data['user']);

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $data['salesOrder']->id)
        ->set('items.0.qty_delivered', 11)
        ->call('save')
        ->assertHasErrors(['items.0.qty_delivered']);

    expect(DeliveryOrder::count())->toBe(0);
});

it('rejects shipment confirmation when warehouse stock is insufficient', function () {
    $data = deliveryOrderFixture();
    $data['stockBalance']->update(['quantity' => 5]);
    $this->actingAs($data['user']);

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $data['salesOrder']->id)
        ->set('items.0.qty_delivered', 6)
        ->call('save');

    $deliveryOrder = DeliveryOrder::firstOrFail();
    Livewire::test(DeliveryOrderComponent::class)
        ->call('openConfirmShipment', $deliveryOrder->id)
        ->call('confirmShipment')
        ->assertHasErrors(['shipment']);

    expect($deliveryOrder->fresh()->status)->toBe(DeliveryOrder::STATUS_DRAFT)
        ->and($data['stockBalance']->fresh()->quantity)->toBe(5)
        ->and($data['salesOrder']->fresh()->status)->toBe('verified');
});

it('allows a super admin to cancel shipment and restore warehouse stock', function () {
    $data = deliveryOrderFixture();
    $this->actingAs($data['user']);

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $data['salesOrder']->id)
        ->set('items.0.qty_delivered', 6)
        ->call('save');

    $deliveryOrder = DeliveryOrder::firstOrFail();
    Livewire::test(DeliveryOrderComponent::class)
        ->call('openConfirmShipment', $deliveryOrder->id)
        ->call('confirmShipment');

    $data['role']->update(['name' => 'Super Admin', 'permissions' => ['*']]);
    $data['user']->unsetRelation('role');

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openCancelShipment', $deliveryOrder->id)
        ->call('cancelShipment')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    expect($deliveryOrder->fresh()->status)->toBe(DeliveryOrder::STATUS_CANCELLED)
        ->and($data['stockBalance']->fresh()->quantity)->toBe(10)
        ->and($data['salesOrder']->fresh()->status)->toBe('verified');
});
it('renders the Indonesian delivery order page and printable pre order reference', function () {
    $data = deliveryOrderFixture();
    $this->actingAs($data['user']);

    $deliveryOrder = DeliveryOrder::create([
        'delivery_no' => 'SJ-TEST-001', 'delivery_date' => '2026-08-12',
        'sales_order_id' => $data['salesOrder']->id, 'customer_id' => $data['customer']->id,
        'customer_address_id' => $data['address']->id, 'status' => DeliveryOrder::STATUS_ISSUED,
        'created_by' => $data['user']->id,
    ]);
    $deliveryOrder->items()->create([
        'sales_order_item_id' => $data['salesOrderItem']->id, 'product_id' => $data['product']->id,
        'warehouse_id' => $data['warehouse']->id, 'unit_id' => $data['unit']->id,
        'conversion' => 1, 'qty_order' => 10, 'qty_delivered' => 5,
        'qty_outstanding' => 5, 'qty_base' => 5,
    ]);

    $this->get(route('sales.transaction.deliveryOrder'))
        ->assertOk()->assertSee('Tambah Surat Jalan')->assertSee('Pesanan Penjualan');

    $this->get(route('sales.transaction.deliveryOrder', ['print' => $deliveryOrder->id]))
        ->assertOk()->assertSee('Surat Jalan')->assertSee('PA-SJ-001')->assertSee('Produk Surat Jalan');
});
