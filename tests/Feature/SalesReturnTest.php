<?php

use App\Livewire\Sales\ReturnTransaction\SalesReturn as SalesReturnComponent;
use App\Livewire\Sales\ReturnTransaction\SalesReturnInvoice as ReturnInvoiceComponent;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\SalesReturnInvoice;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

function salesReturnFixture(array $permissions): array
{
    $role = Role::create(['name' => 'Sales Return '.uniqid(), 'permissions' => $permissions]);
    $user = User::factory()->for($role)->create();
    test()->actingAs($user);
    $customer = Customer::create(['code' => 'CUS-RET-'.uniqid(), 'name' => 'Pelanggan Retur', 'is_active' => true, 'created_by' => $user->id]);
    $category = ProductCategory::create(['code' => 'SCAT-'.uniqid(), 'name' => 'Kategori Sales Retur']);
    $unit = ProductUnit::create(['code' => 'SPCS-'.uniqid(), 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'Gudang Sales Retur '.uniqid(), 'desc' => '-', 'address' => '-']);
    $product = Product::create(['name' => 'Produk Sales Retur', 'sku' => 'SSKU-'.uniqid(), 'category_id' => $category->id, 'base_unit_id' => $unit->id, 'created_by' => (string) $user->id]);
    $order = SalesOrder::create(['order_no' => 'SO-RET-'.uniqid(), 'date' => today(), 'customer_id' => $customer->id, 'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 11000, 'grand_total' => 111000, 'dp_amount' => 0, 'amount_due' => 111000, 'status' => 'completed', 'verified_at' => now(), 'verified_by' => $user->id, 'created_by' => $user->id]);
    $orderItem = $order->items()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id, 'qty' => 10, 'conversion' => 1, 'unit_price' => 10000, 'discount_amount' => 0, 'line_total' => 100000]);
    $delivery = DeliveryOrder::create(['delivery_no' => 'DO-RET-'.uniqid(), 'delivery_date' => today(), 'sales_order_id' => $order->id, 'customer_id' => $customer->id, 'status' => DeliveryOrder::STATUS_SHIPPED, 'created_by' => $user->id]);
    $deliveryItem = $delivery->items()->create(['sales_order_item_id' => $orderItem->id, 'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id, 'conversion' => 1, 'qty_order' => 10, 'qty_delivered' => 10, 'qty_outstanding' => 0, 'qty_base' => 10]);
    $stock = StockBalance::create(['warehouse_id' => $warehouse->id, 'product_id' => $product->id, 'quantity' => 0]);

    return compact('user', 'customer', 'unit', 'warehouse', 'product', 'order', 'orderItem', 'delivery', 'deliveryItem', 'stock');
}

it('creates a sales return draft and only restores stock after confirmation', function () {
    $data = salesReturnFixture(['sales.return.sales-return', 'sales.return.sales-return.confirm']);

    Livewire::test(SalesReturnComponent::class)
        ->call('openCreate')->set('deliveryOrderId', $data['delivery']->id)
        ->set('items.0.qty', 3)->set('items.0.reason', 'Barang rusak')
        ->call('save')->assertHasNoErrors();

    $return = SalesReturn::firstOrFail();
    expect($return->status)->toBe(SalesReturn::STATUS_DRAFT)
        ->and($return->items->first()->qty)->toBe(3)
        ->and($data['stock']->fresh()->quantity)->toBe(0);

    Livewire::test(SalesReturnComponent::class)
        ->call('confirmReturn', $return->id)->call('confirm')->assertHasNoErrors();

    expect($return->fresh()->status)->toBe(SalesReturn::STATUS_CONFIRMED)
        ->and($data['stock']->fresh()->quantity)->toBe(3);
});

it('posts a sales return invoice and reduces receivable with a balanced journal', function () {
    $data = salesReturnFixture(['sales.return.sales-return-invoice', 'sales.return.sales-return-invoice.post']);
    $return = SalesReturn::create(['return_no' => 'SR/TEST/0001', 'return_date' => today(), 'customer_id' => $data['customer']->id, 'delivery_order_id' => $data['delivery']->id, 'sales_order_id' => $data['order']->id, 'status' => SalesReturn::STATUS_CONFIRMED, 'confirmed_at' => now(), 'confirmed_by' => $data['user']->id, 'created_by' => $data['user']->id]);
    $return->items()->create(['delivery_order_item_id' => $data['deliveryItem']->id, 'sales_order_item_id' => $data['orderItem']->id, 'product_id' => $data['product']->id, 'warehouse_id' => $data['warehouse']->id, 'unit_id' => $data['unit']->id, 'conversion' => 1, 'qty' => 3, 'qty_base' => 3, 'unit_price' => 10000, 'subtotal' => 30000, 'reason' => 'Rusak']);
    $salesInvoice = SalesInvoice::create(['invoice_no' => 'SINV-RET-001', 'invoice_date' => today(), 'sales_order_id' => $data['order']->id, 'customer_id' => $data['customer']->id, 'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 11000, 'grand_total' => 111000, 'dp_amount' => 0, 'paid_amount' => 0, 'amount_due' => 111000, 'status' => SalesInvoice::STATUS_CONFIRMED, 'confirmed_at' => now(), 'confirmed_by' => $data['user']->id, 'created_by' => $data['user']->id]);
    foreach ([['1300', 'Piutang Usaha', 'Asset', 'Debit'], ['4100', 'Pendapatan', 'Revenue', 'Credit'], ['2200', 'PPN Keluaran', 'Liability', 'Credit']] as [$code, $name, $type, $normal]) {
        ChartOfAccount::create(['code' => $code, 'name' => $name, 'type' => $type, 'normal_balance' => $normal, 'is_active' => true, 'is_postable' => true]);
    }

    Livewire::test(ReturnInvoiceComponent::class)
        ->call('openCreate')->set('salesReturnId', $return->id)->call('save')->assertHasNoErrors();

    $returnInvoice = SalesReturnInvoice::firstOrFail();
    expect($returnInvoice->grand_total)->toBe(33300)
        ->and($returnInvoice->status)->toBe(SalesReturnInvoice::STATUS_DRAFT);

    Livewire::test(ReturnInvoiceComponent::class)
        ->call('confirmPost', $returnInvoice->id)->call('post')->assertHasNoErrors();

    $journal = JournalEntry::where('source_type', JournalEntry::SOURCE_SALES_RETURN_INVOICE)->firstOrFail();
    expect($returnInvoice->fresh()->status)->toBe(SalesReturnInvoice::STATUS_POSTED)
        ->and($salesInvoice->fresh()->amount_due)->toBe(77700)
        ->and((int) $journal->lines->sum('debit'))->toBe(33300)
        ->and((int) $journal->lines->sum('credit'))->toBe(33300);
});

it('exposes both authenticated sales return pages', function () {
    $data = salesReturnFixture(['*']);
    $this->actingAs($data['user'])->get(route('sales.return.sales-return'))->assertOk()->assertSee('Retur Penjualan');
    $this->get(route('sales.return.sales-return-invoice'))->assertOk()->assertSee('Faktur Retur Penjualan');
});
