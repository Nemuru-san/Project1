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
use App\Models\Role;
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
        'tax_amount' => 0, 'grand_total' => 100000, 'dp_amount' => 60000,
        'dp_payment_status' => PreOrder::DP_STATUS_UNPAID, 'status' => PreOrder::STATUS_DRAFT, 'created_by' => $user->id,
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
        ->assertSee('Target DP')
        ->assertSee('DP Dibayar')
        ->assertSee('Jadikan Sales Order')
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->assertSeeHtml('max-w-full')
        ->assertSee('Detail Harga');
});

it('rejects a target dp greater than the pre order net amount', function () {
    $data = preOrderDpFixture();
    $this->actingAs($data['user']);

    Livewire::test(PreOrderComponent::class)
        ->call('openEdit', $data['preOrder']->id)
        ->set('dpAmount', 100001)
        ->call('save')
        ->assertHasErrors(['dpAmount']);

    expect($data['preOrder']->fresh()->dp_amount)->toBe(60000);
});
it('uses only posted dp when converting a pre order to a sales order', function () {
    $data = preOrderDpFixture();
    $this->actingAs($data['user']);
    $bankCoa = ChartOfAccount::updateOrCreate(['code' => '1120'], ['name' => 'Bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);

    foreach ([[25000, ArDpPayment::STATUS_POSTED], [15000, ArDpPayment::STATUS_DRAFT]] as [$amount, $status]) {
        $payment = ArDpPayment::create([
            'code' => 'ARDP-'.$status, 'payment_date' => '2026-07-22', 'pre_order_id' => $data['preOrder']->id,
            'customer_id' => $data['customer']->id, 'bank_account_id' => BankAccount::create([
                'name' => 'Bank '.$status, 'bank_name' => 'Bank Test', 'account_number' => fake()->unique()->numerify('########'),
                'account_holder' => 'Test', 'chart_of_account_id' => $bankCoa->id, 'is_active' => true,
            ])->id,
            'amount' => $amount, 'status' => $status, 'created_by' => $data['user']->id,
        ]);
        $payment->allocations()->create(['pre_order_id' => $data['preOrder']->id, 'amount' => $amount]);
    }

    $data['preOrder']->update(['status' => PreOrder::STATUS_CONFIRMED, 'dp_amount' => 25000]);
    expect($data['preOrder']->syncDpPaymentStatus())->toBe(PreOrder::DP_STATUS_PAID);
    $order = app(ConvertPreOrderToSalesOrder::class)->handle($data['preOrder']->id);

    expect($order->pre_order_id)->toBe($data['preOrder']->id)
        ->and($order->grand_total)->toBe(100000)
        ->and($order->dp_amount)->toBe(25000)
        ->and($order->amount_due)->toBe(75000)
        ->and($order->items)->toHaveCount(1)
        ->and($data['preOrder']->fresh()->status)->toBe(PreOrder::STATUS_SALES_ORDER);
});

it('rejects converting an unconfirmed pre order', function () {
    $data = preOrderDpFixture();

    expect(fn () => app(ConvertPreOrderToSalesOrder::class)->handle($data['preOrder']->id))
        ->toThrow(RuntimeException::class, 'Pesanan Awal harus dikonfirmasi sebelum dijadikan Pesanan Penjualan.');
});

it('posts a customer dp and creates a balanced journal', function () {
    $data = preOrderDpFixture();
    $this->actingAs($data['user']);
    $bankCoa = ChartOfAccount::updateOrCreate(['code' => '1120'], ['name' => 'Bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);
    ChartOfAccount::where('code', '2300')->update(['is_active' => true, 'is_postable' => true]);
    $bank = BankAccount::create(['name' => 'Bank Utama', 'bank_name' => 'Bank Test', 'account_number' => '123456', 'account_holder' => 'ERP', 'chart_of_account_id' => $bankCoa->id, 'is_active' => true]);
    $data['preOrder']->update(['status' => PreOrder::STATUS_CONFIRMED]);
    $payment = ArDpPayment::create([
        'code' => 'ARDP-TEST-POST', 'payment_date' => '2026-07-22', 'pre_order_id' => $data['preOrder']->id,
        'customer_id' => $data['customer']->id, 'bank_account_id' => $bank->id, 'amount' => 30000,
        'payment_method' => 'Transfer', 'status' => ArDpPayment::STATUS_DRAFT, 'created_by' => $data['user']->id,
    ]);
    $payment->allocations()->create(['pre_order_id' => $data['preOrder']->id, 'amount' => 30000]);

    Livewire::test(ArDpPaymentComponent::class)
        ->call('confirmPost', $payment->id)
        ->assertSet('showPostModal', true)
        ->call('post')
        ->assertDispatched('toast', type: 'success');

    $journal = JournalEntry::with('lines')->where('source_type', JournalEntry::SOURCE_AR_DP_PAYMENT)->sole();
    expect($payment->fresh()->status)->toBe(ArDpPayment::STATUS_POSTED)
        ->and($data['preOrder']->fresh()->dp_payment_status)->toBe(PreOrder::DP_STATUS_PARTIAL_PAID)
        ->and($data['preOrder']->fresh()->remaining_dp_amount)->toBe(30000)
        ->and($journal->lines->sum('debit'))->toBe(30000)
        ->and($journal->lines->sum('credit'))->toBe(30000)
        ->and(SalesOrder::count())->toBe(0);

    expect(fn () => app(ConvertPreOrderToSalesOrder::class)->handle($data['preOrder']->id))
        ->toThrow(RuntimeException::class, 'Target DP Pesanan Awal harus dibayar lunas sebelum dijadikan Pesanan Penjualan.');

    $finalPayment = ArDpPayment::create([
        'code' => 'ARDP-TEST-FINAL', 'payment_date' => '2026-07-23', 'pre_order_id' => $data['preOrder']->id,
        'customer_id' => $data['customer']->id, 'bank_account_id' => $bank->id, 'amount' => 30000,
        'payment_method' => 'Transfer', 'status' => ArDpPayment::STATUS_DRAFT, 'created_by' => $data['user']->id,
    ]);
    $finalPayment->allocations()->create(['pre_order_id' => $data['preOrder']->id, 'amount' => 30000]);

    Livewire::test(ArDpPaymentComponent::class)
        ->call('confirmPost', $finalPayment->id)
        ->call('post')
        ->assertDispatched('toast', type: 'success');

    expect($data['preOrder']->fresh()->dp_payment_status)->toBe(PreOrder::DP_STATUS_PAID)
        ->and($data['preOrder']->fresh()->remaining_dp_amount)->toBe(0);

    $order = app(ConvertPreOrderToSalesOrder::class)->handle($data['preOrder']->id);
    expect($order->dp_amount)->toBe(60000)
        ->and($order->amount_due)->toBe(40000)
        ->and($data['preOrder']->fresh()->status)->toBe(PreOrder::STATUS_SALES_ORDER);
});

it('loads customer pre orders and their remaining dp after the customer is selected', function () {
    $data = preOrderDpFixture();
    $this->actingAs($data['user']);
    $data['preOrder']->update(['status' => PreOrder::STATUS_CONFIRMED]);
    $customerWithoutPreOrder = Customer::factory()->create();

    Livewire::test(ArDpPaymentComponent::class)
        ->call('openCreate')
        ->assertSee('Pilih Customer Terlebih Dahulu')
        ->assertSee($customerWithoutPreOrder->name)
        ->set('customerId', $data['customer']->id)
        ->assertSee($data['preOrder']->pre_order_no)
        ->call('togglePreOrderSelection', $data['preOrder']->id)
        ->assertSet('selectedPreOrderIds', [$data['preOrder']->id])
        ->assertSet("allocations.{$data['preOrder']->id}", 60000)
        ->call('togglePreOrderSelection', $data['preOrder']->id)
        ->assertSet('selectedPreOrderIds', [])
        ->assertSet('amount', 0)
        ->call('togglePreOrderSelection', $data['preOrder']->id)
        ->assertSet('selectedPreOrderIds', [$data['preOrder']->id])
        ->assertSet("allocations.{$data['preOrder']->id}", 60000)
        ->assertSet('amount', 60000)
        ->assertSee('Daftar Pesanan Awal')
        ->assertSee('Total Penerimaan DP');
});

it('allocates one customer payment to multiple checked pre orders', function () {
    $data = preOrderDpFixture();
    $this->actingAs($data['user']);
    $bankCoa = ChartOfAccount::updateOrCreate(['code' => '1120'], ['name' => 'Bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);
    ChartOfAccount::where('code', '2300')->update(['is_active' => true, 'is_postable' => true]);
    $bank = BankAccount::create(['name' => 'Bank Multi DP', 'bank_name' => 'Bank Test', 'account_number' => '654321', 'account_holder' => 'ERP', 'chart_of_account_id' => $bankCoa->id, 'is_active' => true]);

    $data['preOrder']->update(['status' => PreOrder::STATUS_CONFIRMED]);
    $secondPreOrder = $data['preOrder']->replicate();
    $secondPreOrder->pre_order_no = 'PRE-TEST-002';
    $secondPreOrder->dp_amount = 40000;
    $secondPreOrder->dp_payment_status = PreOrder::DP_STATUS_UNPAID;
    $secondPreOrder->status = PreOrder::STATUS_CONFIRMED;
    $secondPreOrder->save();

    Livewire::test(ArDpPaymentComponent::class)
        ->call('openCreate')
        ->set('customerId', $data['customer']->id)
        ->assertSee($data['preOrder']->pre_order_no)
        ->assertSee($secondPreOrder->pre_order_no)
        ->set('selectedPreOrderIds', [$data['preOrder']->id, $secondPreOrder->id])
        ->assertSet('amount', 100000)
        ->set("allocations.{$data['preOrder']->id}", 25000)
        ->assertSet('amount', 65000)
        ->set('bankAccountId', $bank->id)
        ->call('save')
        ->assertDispatched('toast', type: 'success');

    $payment = ArDpPayment::with('allocations')->sole();
    expect($payment->customer_id)->toBe($data['customer']->id)
        ->and($payment->amount)->toBe(65000)
        ->and($payment->allocations)->toHaveCount(2)
        ->and($payment->allocations->sum('amount'))->toBe(65000);

    Livewire::test(ArDpPaymentComponent::class)
        ->call('confirmPost', $payment->id)
        ->call('post')
        ->assertDispatched('toast', type: 'success');

    expect($data['preOrder']->fresh()->dp_payment_status)->toBe(PreOrder::DP_STATUS_PARTIAL_PAID)
        ->and($data['preOrder']->fresh()->remaining_dp_amount)->toBe(35000)
        ->and($secondPreOrder->fresh()->dp_payment_status)->toBe(PreOrder::DP_STATUS_PAID)
        ->and($secondPreOrder->fresh()->remaining_dp_amount)->toBe(0);
});
it('exposes the authenticated ar dp route', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('finance.transaction.ar-dp-payment'))
        ->assertOk()
        ->assertSee('Penerimaan DP Pelanggan');
});
it('requires action permissions to confirm and delete a pre order', function () {
    $data = preOrderDpFixture();
    $this->actingAs($data['user']);

    Livewire::test(PreOrderComponent::class)
        ->call('confirmPreOrder', $data['preOrder']->id)
        ->assertDispatched('toast', message: 'Anda tidak memiliki izin untuk mengonfirmasi Pesanan Awal.', type: 'error');
    expect($data['preOrder']->fresh()->status)->toBe(PreOrder::STATUS_DRAFT);

    $ownerRole = Role::create([
        'name' => 'Owner Pre Order',
        'permissions' => [
            'sales.transaction.salesPreOrder',
            'sales.transaction.salesPreOrder.confirm',
            'sales.transaction.salesPreOrder.delete',
        ],
    ]);
    $data['user']->update(['role_id' => $ownerRole->id]);
    $data['user']->unsetRelation('role');

    Livewire::test(PreOrderComponent::class)
        ->call('openConfirmPreOrder', $data['preOrder']->id)
        ->assertSet('showConfirmModal', true)
        ->assertSet('confirmTargetId', $data['preOrder']->id)
        ->assertSee('Konfirmasi Pesanan Awal?')
        ->call('confirmPreOrder', $data['preOrder']->id)
        ->assertSet('showConfirmModal', false)
        ->assertDispatched('toast', message: 'Pesanan Awal berhasil dikonfirmasi.', type: 'success');
    expect($data['preOrder']->fresh()->status)->toBe(PreOrder::STATUS_CONFIRMED);

    $deletable = $data['preOrder']->replicate();
    $deletable->pre_order_no = 'PRE-DELETE-001';
    $deletable->status = PreOrder::STATUS_DRAFT;
    $deletable->save();

    Livewire::test(PreOrderComponent::class)
        ->call('confirmDelete', $deletable->id)
        ->call('delete')
        ->assertDispatched('toast', message: 'Pesanan Awal berhasil dihapus.', type: 'success');

    expect($deletable->fresh()->trashed())->toBeTrue();
});
