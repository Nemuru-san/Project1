<?php

use App\Livewire\Sales\Transaction\SalesOrder as SalesOrderComponent;
use App\Models\ArPayment;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

function directSalesFixture(): array
{
    $role = Role::create(['name' => 'Kasir Penjualan', 'permissions' => [
        'sales.transaction.salesOrder', 'sales.transaction.salesOrder.verify',
    ]]);
    $user = User::factory()->for($role)->create();
    $customer = Customer::create([
        'code' => 'C-DIRECT', 'name' => 'Pelanggan Langsung', 'credit_limit' => 30000,
        'payment_terms_days' => 14, 'is_active' => true, 'created_by' => $user->id,
    ]);
    $category = ProductCategory::create(['code' => 'DIRECT', 'name' => 'Produk Scan']);
    $unit = ProductUnit::create(['code' => 'PCS-D', 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'Toko Utama', 'desc' => '-', 'address' => '-']);
    $productA = Product::create([
        'name' => 'Barang Scan A', 'sku' => 'SKU-SCAN-A', 'barcode' => '899000000001',
        'category_id' => $category->id, 'base_unit_id' => $unit->id, 'created_by' => (string) $user->id,
    ]);
    $productB = Product::create([
        'name' => 'Barang Scan B', 'sku' => 'SKU-SCAN-B', 'barcode' => '899000000002',
        'category_id' => $category->id, 'base_unit_id' => $unit->id, 'created_by' => (string) $user->id,
    ]);
    ProductPrice::create(['product_id' => $productA->id, 'unit_id' => $unit->id, 'conversion' => 1, 'price' => 10000]);
    ProductPrice::create(['product_id' => $productB->id, 'unit_id' => $unit->id, 'conversion' => 1, 'price' => 20000]);
    StockBalance::create(['warehouse_id' => $warehouse->id, 'product_id' => $productA->id, 'quantity' => 10]);
    StockBalance::create(['warehouse_id' => $warehouse->id, 'product_id' => $productB->id, 'quantity' => 5]);

    $cashCoa = ChartOfAccount::create(['code' => '1110-D', 'name' => 'Kas Toko', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);
    foreach ([
        ['1300', 'Piutang Usaha', 'Asset', 'Debit'],
        ['4100', 'Pendapatan Penjualan', 'Revenue', 'Credit'],
        ['2200', 'PPN Keluaran', 'Liability', 'Credit'],
    ] as [$code, $name, $type, $normal]) {
        ChartOfAccount::create(['code' => $code, 'name' => $name, 'type' => $type, 'normal_balance' => $normal, 'is_postable' => true, 'is_active' => true]);
    }
    $cashAccount = BankAccount::create([
        'name' => 'Kas Toko', 'bank_name' => 'Kas', 'account_number' => '-',
        'account_holder' => 'Toko', 'chart_of_account_id' => $cashCoa->id, 'is_active' => true,
    ]);

    return compact('user', 'customer', 'warehouse', 'productA', 'productB', 'cashAccount');
}

it('scans products and completes a fully paid direct sale in one checkout', function () {
    $data = directSalesFixture();
    $data['customer']->update(['credit_limit' => 0]);
    $this->actingAs($data['user']);

    $component = Livewire::test(SalesOrderComponent::class)
        ->call('openCreate')
        ->set('orderType', 'direct')
        ->set('customerId', $data['customer']->id)
        ->set('scanWarehouseId', $data['warehouse']->id)
        ->set('scanCode', $data['productA']->barcode)->call('scanProduct')
        ->assertSet('items.0.qty', 1)
        ->set('scanCode', $data['productB']->barcode)->call('scanProduct')
        ->set('scanCode', $data['productA']->barcode)->call('scanProduct')
        ->assertSet('items.0.qty', 2)
        ->assertSet('items.1.qty', 1)
        ->set('paymentMode', 'paid')
        ->set('paymentMethod', 'Tunai')
        ->set('bankAccountId', $data['cashAccount']->id)
        ->call('checkout')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    $order = SalesOrder::sole();
    $invoice = SalesInvoice::with('items')->sole();
    $delivery = DeliveryOrder::with('items')->sole();
    $payment = ArPayment::sole();

    expect($component->get('showModal'))->toBeFalse()
        ->and($order->order_type)->toBe('direct')
        ->and($order->status)->toBe('completed')
        ->and($order->grand_total)->toBe(40000)
        ->and($order->amount_due)->toBe(0)
        ->and($delivery->status)->toBe(DeliveryOrder::STATUS_SHIPPED)
        ->and($delivery->items)->toHaveCount(2)
        ->and($invoice->status)->toBe(SalesInvoice::STATUS_CONFIRMED)
        ->and($invoice->items)->toHaveCount(2)
        ->and($invoice->paid_amount)->toBe(40000)
        ->and($invoice->amount_due)->toBe(0)
        ->and($payment->status)->toBe(ArPayment::STATUS_POSTED)
        ->and($payment->amount)->toBe(40000)
        ->and(StockBalance::where('product_id', $data['productA']->id)->value('quantity'))->toBe(8)
        ->and(StockBalance::where('product_id', $data['productB']->id)->value('quantity'))->toBe(4)
        ->and(JournalEntry::where('source_type', JournalEntry::SOURCE_SALES_INVOICE)->count())->toBe(1)
        ->and(JournalEntry::where('source_type', JournalEntry::SOURCE_AR_PAYMENT)->count())->toBe(1);

    JournalEntry::with('lines')->get()->each(function (JournalEntry $journal): void {
        expect((int) $journal->lines->sum('debit'))->toBe((int) $journal->lines->sum('credit'));
    });

    $this->get(route('sales.transaction.salesOrder.thermal-print', $order))
        ->assertOk()
        ->assertSee($invoice->invoice_no)
        ->assertSee('Barang Scan A')
        ->assertSee('LUNAS')
        ->assertSee('size: 80mm auto', false);
});

it('posts a partial payment and leaves the remainder as customer receivable', function () {
    $data = directSalesFixture();
    $this->actingAs($data['user']);

    Livewire::test(SalesOrderComponent::class)
        ->call('openCreate')
        ->set('date', '2026-09-01')
        ->set('orderType', 'direct')
        ->set('customerId', $data['customer']->id)
        ->set('scanWarehouseId', $data['warehouse']->id)
        ->set('scanCode', $data['productB']->sku)->call('scanProduct')
        ->set('paymentMode', 'partial')
        ->set('paidAmount', 5000)
        ->set('paymentMethod', 'Transfer')
        ->set('bankAccountId', $data['cashAccount']->id)
        ->call('checkout')
        ->assertHasNoErrors();

    $invoice = SalesInvoice::sole();
    expect($invoice->grand_total)->toBe(20000)
        ->and($invoice->paid_amount)->toBe(5000)
        ->and($invoice->amount_due)->toBe(15000)
        ->and($invoice->due_date->toDateString())->toBe('2026-09-15')
        ->and(SalesOrder::sole()->amount_due)->toBe(15000)
        ->and(ArPayment::sole()->amount)->toBe(5000);
});

it('rejects direct-sale credit above the customer limit without reducing stock', function () {
    $data = directSalesFixture();
    $data['customer']->update(['credit_limit' => 15000]);
    $this->actingAs($data['user']);

    Livewire::test(SalesOrderComponent::class)
        ->call('openCreate')
        ->set('orderType', 'direct')
        ->set('customerId', $data['customer']->id)
        ->set('scanWarehouseId', $data['warehouse']->id)
        ->set('scanCode', $data['productB']->barcode)->call('scanProduct')
        ->set('paymentMode', 'credit')
        ->call('checkout')
        ->assertHasErrors(['credit_limit']);

    expect(SalesOrder::sole()->status)->toBe('draft')
        ->and(SalesInvoice::count())->toBe(0)
        ->and(DeliveryOrder::count())->toBe(0)
        ->and(ArPayment::count())->toBe(0)
        ->and(StockBalance::where('product_id', $data['productB']->id)->value('quantity'))->toBe(5);
});
