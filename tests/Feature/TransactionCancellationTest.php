<?php

use App\Livewire\Finance\Transaction\ArDpPayment as ArDpPaymentComponent;
use App\Livewire\Purchasing\Transaction\PurchaseInvoice as PurchaseInvoiceComponent;
use App\Livewire\Purchasing\Transaction\PurchaseOrder as PurchaseOrderComponent;
use App\Livewire\Sales\SalesMaster\CustomerMaster;
use App\Livewire\Sales\Transaction\DeliveryOrder as DeliveryOrderComponent;
use App\Livewire\Sales\Transaction\SalesInvoice as SalesInvoiceComponent;
use App\Livewire\Sales\Transaction\SalesOrder as SalesOrderComponent;
use App\Models\ArDpPayment;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DeliveryOrder;
use App\Models\GoodsReceive;
use App\Models\JournalEntry;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockBalance;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

function cancellationUser(string $roleName = 'Owner', array $permissions = ['*']): User
{
    return User::factory()->for(Role::create(['name' => $roleName, 'permissions' => $permissions]))->create();
}

function cancellationSalesFixture(User $user, int $stock = 10): array
{
    $customer = Customer::factory()->create();
    $address = CustomerAddress::factory()->for($customer)->create();
    $unit = ProductUnit::create(['code' => 'CPCS', 'name' => 'Pcs']);
    $category = ProductCategory::create(['code' => 'CCAT', 'name' => 'Kategori']);
    $warehouse = Warehouse::create(['name' => 'Gudang Batal', 'desc' => '-', 'address' => '-']);
    $product = Product::create(['name' => 'Barang Batal', 'sku' => 'SKU-BTL', 'category_id' => $category->id, 'base_unit_id' => $unit->id, 'created_by' => (string) $user->id]);
    StockBalance::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => $stock]);

    $order = SalesOrder::create([
        'order_no' => 'SO-BTL-001', 'date' => now()->toDateString(), 'customer_id' => $customer->id,
        'customer_address_id' => $address->id, 'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 0,
        'grand_total' => 100000, 'dp_amount' => 0, 'amount_due' => 100000, 'status' => 'draft', 'created_by' => $user->id,
    ]);
    $order->items()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id, 'qty' => 10, 'conversion' => 1, 'unit_price' => 10000, 'discount_amount' => 0, 'line_total' => 100000]);

    return compact('customer', 'address', 'unit', 'warehouse', 'product', 'order');
}

it('only accepts digits for customer phone numbers', function () {
    $this->actingAs(cancellationUser('Super Admin'));

    Livewire::test(CustomerMaster::class)
        ->call('openCreate')
        ->set('phone', '0812-abc')
        ->call('save')
        ->assertHasErrors(['phone' => 'regex']);
});

it('shows draft pre orders in dp receipt so users know to confirm them first', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $customer = Customer::factory()->create();
    $address = CustomerAddress::factory()->for($customer)->create();
    PreOrder::create([
        'pre_order_no' => 'PRE-DRAF-001', 'date' => now()->toDateString(), 'customer_id' => $customer->id,
        'customer_address_id' => $address->id, 'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 0,
        'grand_total' => 100000, 'dp_amount' => 50000, 'dp_payment_status' => PreOrder::DP_STATUS_UNPAID,
        'status' => PreOrder::STATUS_DRAFT, 'created_by' => $user->id,
    ]);

    Livewire::test(ArDpPaymentComponent::class)
        ->call('openCreate')
        ->set('customerId', $customer->id)
        ->assertSee('PRE-DRAF-001')
        ->assertSee('Belum dikonfirmasi');
});

it('blocks sales order confirmation when stock on hand or afs is insufficient', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $data = cancellationSalesFixture($user, stock: 5);

    Livewire::test(SalesOrderComponent::class)
        ->call('openConfirmOrder', $data['order']->id)
        ->call('confirmOrder')
        ->assertHasErrors('stock');

    expect($data['order']->fresh()->status)->toBe('draft');
});

it('counts stock already booked by confirmed sales orders when confirming another one', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $data = cancellationSalesFixture($user, stock: 10);

    $booked = $data['order']->replicate(['order_no']);
    $booked->forceFill(['order_no' => 'SO-BTL-BOOKED', 'status' => 'verified', 'verified_at' => now()])->save();
    $booked->items()->create(['product_id' => $data['product']->id, 'warehouse_id' => $data['warehouse']->id, 'unit_id' => $data['unit']->id, 'qty' => 4, 'conversion' => 1, 'unit_price' => 10000, 'discount_amount' => 0, 'line_total' => 40000]);

    Livewire::test(SalesOrderComponent::class)
        ->call('openConfirmOrder', $data['order']->id)
        ->call('confirmOrder')
        ->assertHasErrors('stock');
});

it('loads posted pre order dp into a sales order created from the form and marks its source', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $data = cancellationSalesFixture($user);
    $preOrder = PreOrder::create([
        'pre_order_no' => 'PRE-DP-001', 'date' => now()->toDateString(), 'customer_id' => $data['customer']->id,
        'customer_address_id' => $data['address']->id, 'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 0,
        'grand_total' => 100000, 'dp_amount' => 30000, 'dp_payment_status' => PreOrder::DP_STATUS_PAID,
        'status' => PreOrder::STATUS_CONFIRMED, 'created_by' => $user->id,
    ]);
    $preOrder->items()->create(['product_id' => $data['product']->id, 'warehouse_id' => $data['warehouse']->id, 'unit_id' => $data['unit']->id, 'qty' => 10, 'conversion' => 1, 'unit_price' => 10000, 'discount_amount' => 0, 'line_total' => 100000]);
    ProductPrice::create(['product_id' => $data['product']->id, 'unit_id' => $data['unit']->id, 'conversion' => 1, 'price' => 10000]);
    $bankCoa = ChartOfAccount::create(['code' => '1120', 'name' => 'Bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);
    $bank = BankAccount::create(['name' => 'Bank Utama', 'bank_name' => 'Bank Test', 'account_number' => '123456', 'account_holder' => 'ERP', 'chart_of_account_id' => $bankCoa->id, 'is_active' => true]);
    $payment = ArDpPayment::create([
        'code' => 'ARDP-T-001', 'payment_date' => now()->toDateString(), 'pre_order_id' => $preOrder->id,
        'bank_account_id' => $bank->id,
        'customer_id' => $data['customer']->id, 'amount' => 30000, 'payment_method' => 'Transfer',
        'status' => ArDpPayment::STATUS_POSTED, 'created_by' => $user->id,
    ]);
    $payment->allocations()->create(['pre_order_id' => $preOrder->id, 'amount' => 30000]);

    Livewire::test(SalesOrderComponent::class)
        ->call('openCreate')
        ->set('sourceType', 'pre_order')
        ->set('preOrderId', $preOrder->id)
        ->assertSet('dpAmount', 30000)
        ->assertSee('DP dari Pre Order')
        ->call('save')
        ->assertHasNoErrors();

    $order = SalesOrder::where('pre_order_id', $preOrder->id)->firstOrFail();
    expect($order->dp_amount)->toBe(30000)
        ->and($order->amount_due)->toBe(70000);

    Livewire::test(SalesOrderComponent::class)->assertSee('Pre')->assertSee('PRE-DP-001');
});

it('lets only the owner cancel a sales order without delivery orders and releases its pre order', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $data = cancellationSalesFixture($user);
    $preOrder = PreOrder::create([
        'pre_order_no' => 'PRE-CANCEL-001', 'date' => now()->toDateString(), 'customer_id' => $data['customer']->id,
        'customer_address_id' => $data['address']->id, 'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 0,
        'grand_total' => 100000, 'dp_amount' => 10000, 'dp_payment_status' => PreOrder::DP_STATUS_PAID,
        'status' => PreOrder::STATUS_SALES_ORDER, 'created_by' => $user->id,
    ]);
    $data['order']->update(['pre_order_id' => $preOrder->id]);

    // Tanpa izin "Batalkan Transaksi", pembatalan ditolak walaupun punya akses modul SO.
    $this->actingAs(cancellationUser('Staff Penjualan', ['sales.transaction.salesOrder', 'sales.transaction.salesOrder.verify']));
    Livewire::test(SalesOrderComponent::class)
        ->call('confirmCancelOrder', $data['order']->id)
        ->assertSet('showCancelOrderModal', false);

    $this->actingAs($user);
    Livewire::test(SalesOrderComponent::class)
        ->call('confirmCancelOrder', $data['order']->id)
        ->assertSet('showCancelOrderModal', true)
        ->call('cancelOrder');

    expect($data['order']->fresh()->status)->toBe('cancelled')
        ->and($preOrder->fresh()->status)->toBe(PreOrder::STATUS_CONFIRMED)
        ->and($preOrder->fresh()->salesOrder)->toBeNull();
});

it('refuses to cancel a sales order that already has a delivery order', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $data = cancellationSalesFixture($user);
    $data['order']->update(['status' => 'verified', 'verified_at' => now()]);
    DeliveryOrder::create([
        'delivery_no' => 'SJ-BTL-001', 'delivery_date' => now()->toDateString(), 'sales_order_id' => $data['order']->id,
        'customer_id' => $data['customer']->id, 'customer_address_id' => $data['address']->id,
        'status' => DeliveryOrder::STATUS_DRAFT, 'created_by' => $user->id,
    ]);

    Livewire::test(SalesOrderComponent::class)
        ->call('confirmCancelOrder', $data['order']->id)
        ->assertSet('showCancelOrderModal', false);

    expect($data['order']->fresh()->status)->toBe('verified');
});

it('cancels a shipped delivery order without invoice and returns its stock', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $data = cancellationSalesFixture($user, stock: 6);
    $data['order']->update(['status' => 'processing', 'verified_at' => now()]);
    $deliveryOrder = DeliveryOrder::create([
        'delivery_no' => 'SJ-BTL-002', 'delivery_date' => now()->toDateString(), 'sales_order_id' => $data['order']->id,
        'customer_id' => $data['customer']->id, 'customer_address_id' => $data['address']->id,
        'status' => DeliveryOrder::STATUS_SHIPPED, 'created_by' => $user->id,
    ]);
    $deliveryOrder->items()->create([
        'sales_order_item_id' => $data['order']->items()->value('id'), 'product_id' => $data['product']->id,
        'warehouse_id' => $data['warehouse']->id, 'unit_id' => $data['unit']->id, 'qty_order' => 10,
        'qty_delivered' => 4, 'qty_outstanding' => 6, 'conversion' => 1, 'qty_base' => 4,
    ]);

    Livewire::test(DeliveryOrderComponent::class)
        ->call('openCancelShipment', $deliveryOrder->id)
        ->call('cancelShipment');

    expect($deliveryOrder->fresh()->status)->toBe(DeliveryOrder::STATUS_CANCELLED)
        ->and(StockBalance::where('product_id', $data['product']->id)->value('quantity'))->toBe(10);
});

it('cancels a confirmed sales invoice without payment and releases its delivery orders', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $data = cancellationSalesFixture($user);
    $data['order']->update(['status' => 'completed', 'verified_at' => now()]);
    $deliveryOrder = DeliveryOrder::create([
        'delivery_no' => 'SJ-BTL-003', 'delivery_date' => now()->toDateString(), 'sales_order_id' => $data['order']->id,
        'customer_id' => $data['customer']->id, 'customer_address_id' => $data['address']->id,
        'status' => DeliveryOrder::STATUS_INVOICED, 'created_by' => $user->id,
    ]);
    $invoice = SalesInvoice::create([
        'invoice_no' => 'FP-BTL-001', 'invoice_date' => now()->toDateString(), 'sales_order_id' => $data['order']->id,
        'customer_id' => $data['customer']->id, 'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 0,
        'grand_total' => 100000, 'dp_amount' => 0, 'paid_amount' => 0, 'amount_due' => 100000,
        'status' => SalesInvoice::STATUS_CONFIRMED, 'created_by' => $user->id,
    ]);
    $invoice->deliveryOrders()->attach($deliveryOrder->id);
    $journal = JournalEntry::create([
        'code' => 'JE-BTL-001', 'date' => now()->toDateString(), 'source_type' => JournalEntry::SOURCE_SALES_INVOICE,
        'source_id' => $invoice->id, 'description' => 'Faktur', 'status' => JournalEntry::STATUS_POSTED, 'created_by' => $user->id,
    ]);

    Livewire::test(SalesInvoiceComponent::class)
        ->call('confirmCancelInvoice', $invoice->id)
        ->assertSet('showCancelInvoiceModal', true)
        ->call('cancelInvoice');

    $cancelled = SalesInvoice::withTrashed()->findOrFail($invoice->id);
    expect($cancelled->status)->toBe(SalesInvoice::STATUS_CANCELLED)
        ->and($cancelled->trashed())->toBeTrue()
        ->and($deliveryOrder->fresh()->status)->toBe(DeliveryOrder::STATUS_SHIPPED)
        ->and($journal->fresh()->status)->toBe(JournalEntry::STATUS_CANCELLED);

    Livewire::test(SalesInvoiceComponent::class)
        ->set('showCancelled', true)
        ->assertSee('FP-BTL-001')
        ->assertSee('Dibatalkan');
});

it('refuses to cancel a sales invoice that already has a payment', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $data = cancellationSalesFixture($user);
    $invoice = SalesInvoice::create([
        'invoice_no' => 'FP-BTL-002', 'invoice_date' => now()->toDateString(), 'sales_order_id' => $data['order']->id,
        'customer_id' => $data['customer']->id, 'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 0,
        'grand_total' => 100000, 'dp_amount' => 0, 'paid_amount' => 40000, 'amount_due' => 60000,
        'status' => SalesInvoice::STATUS_CONFIRMED, 'created_by' => $user->id,
    ]);

    Livewire::test(SalesInvoiceComponent::class)
        ->call('confirmCancelInvoice', $invoice->id)
        ->assertSet('showCancelInvoiceModal', false);

    expect($invoice->fresh()->status)->toBe(SalesInvoice::STATUS_CONFIRMED);
});

it('cancels a purchase order only while it has no goods receive', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $supplier = Supplier::create(['code' => 'SUP-BTL', 'name' => 'Pemasok', 'address' => 'A', 'contact' => '1', 'created_by' => (string) $user->id]);
    $makeOrder = fn (string $code) => PurchaseOrder::create([
        'code' => $code, 'date' => now()->toDateString(), 'supplier_id' => $supplier->id, 'user_id' => $user->id,
        'total_price' => 0, 'tax' => false, 'ppn' => 0, 'gross' => 0, 'nett' => 0, 'status' => PurchaseOrder::STATUS_APPROVED,
    ]);
    $free = $makeOrder('PO-BTL-1');
    $received = $makeOrder('PO-BTL-2');
    GoodsReceive::create([
        'code' => 'GR-BTL-1', 'date' => now()->toDateString(), 'purchase_order_id' => $received->id,
        'supplier_id' => $supplier->id, 'status' => GoodsReceive::STATUS_DRAFT, 'created_by' => $user->id,
    ]);

    Livewire::test(PurchaseOrderComponent::class)
        ->call('confirmCancelOrder', $free->id)
        ->call('cancelOrder')
        ->call('confirmCancelOrder', $received->id)
        ->assertSet('showCancelOrderModal', false);

    expect($free->fresh()->status)->toBe(PurchaseOrder::STATUS_CANCELLED)
        ->and($received->fresh()->status)->toBe(PurchaseOrder::STATUS_APPROVED);
});

it('cancels a purchase invoice without payment and releases its goods receives', function () {
    $user = cancellationUser();
    $this->actingAs($user);
    $supplier = Supplier::create(['code' => 'SUP-BTL2', 'name' => 'Pemasok', 'address' => 'A', 'contact' => '1', 'created_by' => (string) $user->id]);
    $order = PurchaseOrder::create([
        'code' => 'PO-BTL-3', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id, 'user_id' => $user->id,
        'total_price' => 0, 'tax' => false, 'ppn' => 0, 'gross' => 0, 'nett' => 0, 'status' => PurchaseOrder::STATUS_RECEIVED,
    ]);
    $goodsReceive = GoodsReceive::create([
        'code' => 'GR-BTL-2', 'date' => now()->toDateString(), 'purchase_order_id' => $order->id,
        'supplier_id' => $supplier->id, 'status' => GoodsReceive::STATUS_INVOICED, 'created_by' => $user->id,
    ]);
    $invoice = PurchaseInvoice::create([
        'code' => 'PIV-BTL-1', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'purchase_order_id' => $order->id, 'sub_total' => 50000, 'grand_total' => 50000, 'remaining_amount' => 50000,
        'status' => PurchaseInvoice::STATUS_POSTED, 'payment_status' => PurchaseInvoice::PAYMENT_UNPAID, 'created_by' => $user->id,
    ]);
    $invoice->goodsReceives()->attach($goodsReceive->id);

    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('confirmCancelInvoice', $invoice->id)
        ->call('cancelInvoice');

    expect(PurchaseInvoice::withTrashed()->find($invoice->id)->status)->toBe(PurchaseInvoice::STATUS_CANCELLED)
        ->and($goodsReceive->fresh()->status)->toBe(GoodsReceive::STATUS_RECEIVED)
        ->and($goodsReceive->purchaseInvoices()->exists())->toBeFalse();
});
