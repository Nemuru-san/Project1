<?php

use App\Livewire\Finance\Transaction\ArPayment as ArPaymentComponent;
use App\Livewire\Sales\Transaction\DeliveryOrder as DeliveryOrderComponent;
use App\Livewire\Sales\Transaction\SalesInvoice as SalesInvoiceComponent;
use App\Livewire\Sales\Transaction\SalesOrder as SalesOrderComponent;
use App\Models\ArPayment;
use App\Models\BankAccount;
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
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

function verifiedSalesOrderFixture(): array
{
    $role = Role::create(['name' => 'Verifikator Penjualan', 'permissions' => [
        'sales.transaction.salesOrder', 'sales.transaction.salesOrder.verify',
        'sales.transaction.delivery-order', 'sales.transaction.sales-invoice',
        'sales.transaction.sales-invoice.confirm', 'finance.transaction.ar-payment',
    ]]);
    $user = User::factory()->for($role)->create();
    $customer = Customer::create(['code' => 'C-VERIFY', 'name' => 'Pelanggan Konfirmasi', 'is_active' => true, 'created_by' => $user->id]);
    $category = ProductCategory::create(['code' => 'VERIFY', 'name' => 'Produk Konfirmasi']);
    $unit = ProductUnit::create(['code' => 'VPCS', 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'Gudang Konfirmasi', 'desc' => '-', 'address' => '-']);
    $product = Product::create(['name' => 'Barang Konfirmasi', 'sku' => 'SKU-V', 'category_id' => $category->id, 'base_unit_id' => $unit->id, 'created_by' => (string) $user->id]);
    $order = SalesOrder::create([
        'order_no' => 'SO-VERIFY-001', 'date' => '2026-08-12', 'customer_id' => $customer->id,
        'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 0,
        'grand_total' => 100000, 'dp_amount' => 0, 'amount_due' => 100000,
        'status' => 'draft', 'created_by' => $user->id,
    ]);
    $order->items()->create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id, 'qty' => 10, 'conversion' => 1, 'unit_price' => 10000, 'discount_amount' => 0, 'line_total' => 100000]);

    return compact('role', 'user', 'customer', 'order');
}

function createSalesInvoiceAccounts(): void
{
    ChartOfAccount::updateOrCreate(['code' => '1300'], ['name' => 'Piutang Usaha', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);
    ChartOfAccount::updateOrCreate(['code' => '2300'], ['name' => 'Uang Muka Pelanggan', 'type' => 'Liability', 'normal_balance' => 'Credit', 'is_postable' => true, 'is_active' => true]);
    ChartOfAccount::updateOrCreate(['code' => '4100'], ['name' => 'Pendapatan Penjualan', 'type' => 'Revenue', 'normal_balance' => 'Credit', 'is_postable' => true, 'is_active' => true]);
    ChartOfAccount::updateOrCreate(['code' => '2200'], ['name' => 'PPN Keluaran', 'type' => 'Liability', 'normal_balance' => 'Credit', 'is_postable' => true, 'is_active' => true]);
}

it('requires sales order confirmation for delivery and invoicing, then invoice confirmation for ar payment', function () {
    $data = verifiedSalesOrderFixture();
    $this->actingAs($data['user']);

    Livewire::test(DeliveryOrderComponent::class)->call('openCreate')->assertDontSee('SO-VERIFY-001');
    Livewire::test(SalesInvoiceComponent::class)->call('openCreate')->assertDontSee('SO-VERIFY-001');
    Livewire::test(ArPaymentComponent::class)->call('openCreate')->assertDontSee('SO-VERIFY-001');

    Livewire::test(SalesOrderComponent::class)
        ->call('openConfirmOrder', $data['order']->id)
        ->assertSet('showConfirmModal', true)
        ->call('confirmOrder')
        ->assertDispatched('toast', message: 'Pesanan Penjualan berhasil dikonfirmasi.', type: 'success');

    expect($data['order']->fresh()->status)->toBe('verified')
        ->and($data['order']->fresh()->verified_at)->not->toBeNull();

    Livewire::test(DeliveryOrderComponent::class)->call('openCreate')->assertSee('SO-VERIFY-001');
    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')->assertSee('SO-VERIFY-001')
        ->set('salesOrderId', $data['order']->id)
        ->assertSet('items.0.qty', 10)
        ->call('save')->assertHasNoErrors();

    $invoice = SalesInvoice::sole();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_DRAFT)
        ->and($invoice->items)->toHaveCount(1)
        ->and($invoice->amount_due)->toBe(100000);

    Livewire::test(ArPaymentComponent::class)->call('openCreate')->assertDontSee($invoice->invoice_no);

    createSalesInvoiceAccounts();
    Livewire::test(SalesInvoiceComponent::class)
        ->call('openConfirmInvoice', $invoice->id)
        ->assertSet('showConfirmModal', true)
        ->call('confirmInvoice')
        ->assertDispatched('toast', type: 'success');

    expect($invoice->fresh()->status)->toBe(SalesInvoice::STATUS_CONFIRMED)
        ->and($invoice->fresh()->confirmed_by)->toBe($data['user']->id);
    Livewire::test(ArPaymentComponent::class)->call('openCreate')->assertSee($invoice->invoice_no);
});

it('enforces one sales invoice per sales order', function () {
    $data = verifiedSalesOrderFixture();
    $this->actingAs($data['user']);
    $data['order']->forceFill(['status' => 'verified', 'verified_at' => now(), 'verified_by' => $data['user']->id])->save();

    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')->set('salesOrderId', $data['order']->id)->call('save')->assertHasNoErrors();

    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $data['order']->id)
        ->call('save')
        ->assertHasErrors(['salesOrderId']);

    expect(SalesInvoice::count())->toBe(1);
});

it('posts ar payment only for a confirmed sales invoice and updates invoice balance', function () {
    $data = verifiedSalesOrderFixture();
    $this->actingAs($data['user']);
    $data['order']->forceFill(['status' => 'verified', 'verified_at' => now(), 'verified_by' => $data['user']->id])->save();
    createSalesInvoiceAccounts();

    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')->set('salesOrderId', $data['order']->id)->call('save');
    $invoice = SalesInvoice::sole();
    Livewire::test(SalesInvoiceComponent::class)
        ->call('openConfirmInvoice', $invoice->id)->call('confirmInvoice');

    $bankCoa = ChartOfAccount::create(['code' => '1120-V', 'name' => 'Bank Konfirmasi', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);
    $bank = BankAccount::create(['name' => 'Bank AR', 'bank_name' => 'Bank', 'account_number' => '9988', 'account_holder' => 'ERP', 'chart_of_account_id' => $bankCoa->id, 'is_active' => true]);

    Livewire::test(ArPaymentComponent::class)
        ->call('openCreate')->set('salesInvoiceId', $invoice->id)
        ->set('bankAccountId', $bank->id)->set('amount', 40000)->call('save')->assertHasNoErrors();
    $payment = ArPayment::sole();
    Livewire::test(ArPaymentComponent::class)->call('confirmPost', $payment->id)->call('post');

    $journal = JournalEntry::where('source_type', 'ArPayment')->sole();
    expect($payment->fresh()->status)->toBe(ArPayment::STATUS_POSTED)
        ->and($payment->sales_invoice_id)->toBe($invoice->id)
        ->and($invoice->fresh()->paid_amount)->toBe(40000)
        ->and($invoice->fresh()->amount_due)->toBe(60000)
        ->and($data['order']->fresh()->amount_due)->toBe(60000)
        ->and($journal->lines->sum('debit'))->toBe(40000)
        ->and($journal->lines->sum('credit'))->toBe(40000);
});
it('allows a confirmed sales invoice to be edited and deleted with its journal', function () {
    $data = verifiedSalesOrderFixture();
    $this->actingAs($data['user']);
    $data['order']->forceFill(['status' => 'verified', 'verified_at' => now(), 'verified_by' => $data['user']->id])->save();
    createSalesInvoiceAccounts();

    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $data['order']->id)
        ->call('save')
        ->assertHasNoErrors();

    $invoice = SalesInvoice::sole();
    Livewire::test(SalesInvoiceComponent::class)
        ->call('openConfirmInvoice', $invoice->id)
        ->call('confirmInvoice');

    Livewire::test(SalesInvoiceComponent::class)
        ->call('openEdit', $invoice->id)
        ->assertSet('editingId', $invoice->id)
        ->set('invoiceDate', '2026-08-20')
        ->set('dueDate', '2026-09-20')
        ->set('notes', 'Diubah setelah konfirmasi')
        ->call('save')
        ->assertHasNoErrors();

    $invoice->refresh();
    $journal = JournalEntry::where('source_type', JournalEntry::SOURCE_SALES_INVOICE)
        ->where('source_id', $invoice->id)
        ->sole();
    expect($invoice->status)->toBe(SalesInvoice::STATUS_CONFIRMED)
        ->and($invoice->invoice_date->toDateString())->toBe('2026-08-20')
        ->and($invoice->notes)->toBe('Diubah setelah konfirmasi')
        ->and($journal->date->toDateString())->toBe('2026-08-20');

    $superAdminRole = Role::create(['name' => 'Super Admin', 'permissions' => ['*']]);
    $superAdmin = User::factory()->for($superAdminRole)->create();
    $this->actingAs($superAdmin);

    Livewire::test(SalesInvoiceComponent::class)
        ->call('confirmDelete', $invoice->id)
        ->assertSet('showDeleteModal', true)
        ->call('delete');

    expect($invoice->fresh()->trashed())->toBeTrue()
        ->and(JournalEntry::withTrashed()->findOrFail($journal->id)->trashed())->toBeTrue();
});

it('creates one sales invoice from multiple shipped delivery orders', function () {
    $data = verifiedSalesOrderFixture();
    $this->actingAs($data['user']);
    $data['order']->forceFill(['status' => 'verified', 'verified_at' => now(), 'verified_by' => $data['user']->id])->save();
    $orderItem = $data['order']->items()->firstOrFail();

    $deliveries = collect([
        ['SJ-MULTI-001', 4],
        ['SJ-MULTI-002', 3],
    ])->map(function (array $source) use ($data, $orderItem) {
        [$number, $qty] = $source;
        $delivery = DeliveryOrder::create([
            'delivery_no' => $number,
            'delivery_date' => '2026-08-20',
            'sales_order_id' => $data['order']->id,
            'customer_id' => $data['customer']->id,
            'status' => DeliveryOrder::STATUS_SHIPPED,
            'created_by' => $data['user']->id,
        ]);
        $delivery->items()->create([
            'sales_order_item_id' => $orderItem->id,
            'product_id' => $orderItem->product_id,
            'warehouse_id' => $orderItem->warehouse_id,
            'unit_id' => $orderItem->unit_id,
            'conversion' => $orderItem->conversion,
            'qty_order' => $orderItem->qty,
            'qty_delivered' => $qty,
            'qty_outstanding' => $orderItem->qty - $qty,
            'qty_base' => $qty * $orderItem->conversion,
        ]);

        return $delivery;
    });

    Livewire::test(SalesInvoiceComponent::class)
        ->call('openCreate')
        ->set('salesOrderId', $data['order']->id)
        ->assertSee('SJ-MULTI-001')
        ->assertSee('SJ-MULTI-002')
        ->set('selectedDeliveryOrderIds', $deliveries->pluck('id')->all())
        ->assertSet('items.0.qty', 7)
        ->call('save')
        ->assertHasNoErrors();

    $invoice = SalesInvoice::with(['deliveryOrders', 'items'])->sole();
    expect($invoice->deliveryOrders)->toHaveCount(2)
        ->and($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->qty)->toBe(7)
        ->and($invoice->grand_total)->toBe(70000)
        ->and($invoice->amount_due)->toBe(70000);
});
