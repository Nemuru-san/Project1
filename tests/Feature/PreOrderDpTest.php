<?php

use App\Livewire\Finance\Transaction\ArDpPayment as ArDpPaymentComponent;
use App\Livewire\Sales\Transaction\PreOrder as PreOrderComponent;
use App\Models\ArDpPayment;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\JournalEntry;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ConvertPreOrderToSalesOrder;
use Livewire\Livewire;

function preOrderDpFixture(): array
{
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $address = CustomerAddress::factory()->for($customer)->create();
    $category = ProductCategory::create(['code' => fake()->unique()->bothify('PC-###'), 'name' => 'Pre Order']);
    $unit = ProductUnit::create(['code' => fake()->unique()->bothify('U-###'), 'name' => 'Pcs']);
    $warehouse = Warehouse::create(['name' => 'Gudang Pre Order', 'desc' => '-', 'address' => 'Jakarta']);
    $product = Product::create(['name' => 'Produk Pre Order', 'sku' => fake()->unique()->bothify('PRE-###'), 'category_id' => $category->id, 'base_unit_id' => $unit->id, 'created_by' => 'test']);
    ProductPrice::create(['product_id' => $product->id, 'unit_id' => $unit->id, 'conversion' => 1, 'price' => 100000]);

    $preOrder = PreOrder::create([
        'pre_order_no' => 'PRE-TEST-001', 'date' => '2026-07-22', 'customer_id' => $customer->id,
        'customer_address_id' => $address->id, 'subtotal' => 100000, 'discount_total' => 0,
        'tax_amount' => 0, 'grand_total' => 100000, 'status' => PreOrder::STATUS_DRAFT, 'created_by' => $user->id,
    ]);
    $preOrder->items()->create([
        'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'unit_id' => $unit->id,
        'qty' => 1, 'conversion' => 1, 'unit_price' => 100000, 'discount_amount' => 0, 'line_total' => 100000,
    ]);

    return compact('user', 'customer', 'address', 'category', 'unit', 'warehouse', 'product', 'preOrder');
}

it('creates a functional pre order and renders the transaction design', function () {
    $data = preOrderDpFixture();
    $this->actingAs($data['user']);

    Livewire::test(PreOrderComponent::class)
        ->assertSee($data['preOrder']->pre_order_no)
        ->assertSee('DP Posted')
        ->assertSee('Jadikan Sales Order')
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->assertSeeHtml('max-w-full')
        ->assertSee('Detail Harga');
});

it('uses only posted dp when converting a pre order to a sales order', function () {
    $data = preOrderDpFixture();
    $this->actingAs($data['user']);
    $bankCoa = ChartOfAccount::updateOrCreate(['code' => '1120'], ['name' => 'Bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);

    foreach ([[25000, ArDpPayment::STATUS_POSTED], [15000, ArDpPayment::STATUS_DRAFT]] as [$amount, $status]) {
        ArDpPayment::create([
            'code' => 'ARDP-'.$status, 'payment_date' => '2026-07-22', 'pre_order_id' => $data['preOrder']->id,
            'customer_id' => $data['customer']->id, 'bank_account_id' => BankAccount::create([
                'name' => 'Bank '.$status, 'bank_name' => 'Bank Test', 'account_number' => fake()->unique()->numerify('########'),
                'account_holder' => 'Test', 'chart_of_account_id' => $bankCoa->id, 'is_active' => true,
            ])->id,
            'amount' => $amount, 'status' => $status, 'created_by' => $data['user']->id,
        ]);
    }

    $order = app(ConvertPreOrderToSalesOrder::class)->handle($data['preOrder']->id);

    expect($order->pre_order_id)->toBe($data['preOrder']->id)
        ->and($order->grand_total)->toBe(100000)
        ->and($order->dp_amount)->toBe(25000)
        ->and($order->amount_due)->toBe(75000)
        ->and($order->items)->toHaveCount(1)
        ->and($data['preOrder']->fresh()->status)->toBe(PreOrder::STATUS_SALES_ORDER);
});

it('posts a customer dp and creates a balanced journal', function () {
    $data = preOrderDpFixture();
    $this->actingAs($data['user']);
    $bankCoa = ChartOfAccount::updateOrCreate(['code' => '1120'], ['name' => 'Bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);
    ChartOfAccount::where('code', '2300')->update(['is_active' => true, 'is_postable' => true]);
    $bank = BankAccount::create(['name' => 'Bank Utama', 'bank_name' => 'Bank Test', 'account_number' => '123456', 'account_holder' => 'ERP', 'chart_of_account_id' => $bankCoa->id, 'is_active' => true]);
    $payment = ArDpPayment::create([
        'code' => 'ARDP-TEST-POST', 'payment_date' => '2026-07-22', 'pre_order_id' => $data['preOrder']->id,
        'customer_id' => $data['customer']->id, 'bank_account_id' => $bank->id, 'amount' => 30000,
        'payment_method' => 'Transfer', 'status' => ArDpPayment::STATUS_DRAFT, 'created_by' => $data['user']->id,
    ]);

    Livewire::test(ArDpPaymentComponent::class)
        ->call('confirmPost', $payment->id)
        ->assertSet('showPostModal', true)
        ->call('post')
        ->assertDispatched('toast', type: 'success');

    $journal = JournalEntry::with('lines')->where('source_type', JournalEntry::SOURCE_AR_DP_PAYMENT)->sole();
    expect($payment->fresh()->status)->toBe(ArDpPayment::STATUS_POSTED)
        ->and($journal->lines->sum('debit'))->toBe(30000)
        ->and($journal->lines->sum('credit'))->toBe(30000)
        ->and(SalesOrder::count())->toBe(0);
});

it('exposes the authenticated ar dp route', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('finance.transaction.ar-dp-payment'))
        ->assertOk()
        ->assertSee('Penerimaan DP Pelanggan');
});
