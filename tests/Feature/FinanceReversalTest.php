<?php

use App\Livewire\Finance\Transaction\ArDpPayment as ArDpPaymentComponent;
use App\Livewire\Finance\Transaction\ArPayment as ArPaymentComponent;
use App\Livewire\Finance\Transaction\Expense as ExpenseComponent;
use App\Livewire\Sales\ReturnTransaction\SalesReturnInvoice as SalesReturnInvoiceComponent;
use App\Livewire\Sales\Transaction\SalesInvoice as SalesInvoiceComponent;
use App\Models\ArDpPayment;
use App\Models\ArPayment;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DeliveryOrder;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\PreOrder;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\SalesReturnInvoice;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->for(Role::create(['name' => 'Owner', 'permissions' => ['*']]))->create();
    $this->actingAs($this->user);

    $this->bankCoa = ChartOfAccount::updateOrCreate(['code' => '1120'], ['name' => 'Bank', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);
    ChartOfAccount::updateOrCreate(['code' => '1300'], ['name' => 'Piutang Usaha', 'type' => 'Asset', 'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true]);
    ChartOfAccount::updateOrCreate(['code' => '2300'], ['name' => 'Uang Muka Pelanggan', 'type' => 'Liability', 'normal_balance' => 'Credit', 'is_postable' => true, 'is_active' => true]);
    $this->bank = BankAccount::create(['name' => 'Bank Utama', 'bank_name' => 'Bank Test', 'account_number' => '123', 'account_holder' => 'ERP', 'chart_of_account_id' => $this->bankCoa->id, 'is_active' => true]);

    $this->customer = Customer::factory()->create();
    $this->address = CustomerAddress::factory()->for($this->customer)->create();
    $this->order = SalesOrder::create([
        'order_no' => 'SO-REV-1', 'date' => now()->toDateString(), 'customer_id' => $this->customer->id, 'customer_address_id' => $this->address->id,
        'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 0, 'grand_total' => 100000, 'dp_amount' => 0, 'amount_due' => 100000,
        'status' => 'completed', 'verified_at' => now(), 'created_by' => $this->user->id,
    ]);
    $this->invoice = SalesInvoice::create([
        'invoice_no' => 'FP-REV-1', 'invoice_date' => now()->toDateString(), 'sales_order_id' => $this->order->id, 'customer_id' => $this->customer->id,
        'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 0, 'grand_total' => 100000, 'dp_amount' => 0, 'paid_amount' => 0,
        'amount_due' => 100000, 'status' => SalesInvoice::STATUS_CONFIRMED, 'created_by' => $this->user->id,
    ]);
});

it('edits a draft ar payment, then cancels the posted payment and restores the invoice balance', function () {
    Livewire::test(ArPaymentComponent::class)
        ->call('openCreate')
        ->set('salesInvoiceId', $this->invoice->id)
        ->set('bankAccountId', $this->bank->id)
        ->set('amount', 30000)
        ->call('save')
        ->assertHasNoErrors();

    $payment = ArPayment::firstOrFail();

    Livewire::test(ArPaymentComponent::class)
        ->call('openEdit', $payment->id)
        ->assertSet('editingId', $payment->id)
        ->set('amount', 40000)
        ->call('save');
    expect($payment->fresh()->amount)->toBe(40000);

    Livewire::test(ArPaymentComponent::class)->call('confirmPost', $payment->id)->call('post');
    expect($this->invoice->fresh()->amount_due)->toBe(60000)
        ->and($this->invoice->fresh()->cancelLockReason())->not->toBeNull();

    Livewire::test(ArPaymentComponent::class)
        ->call('openDetail', $payment->id)
        ->assertSee('FP-REV-1')
        ->call('confirmCancel', $payment->id)
        ->call('cancelPayment');

    $invoice = $this->invoice->fresh();
    expect($payment->fresh()->status)->toBe(ArPayment::STATUS_CANCELLED)
        ->and($invoice->paid_amount)->toBe(0)
        ->and($invoice->amount_due)->toBe(100000)
        ->and($this->order->fresh()->amount_due)->toBe(100000)
        ->and(JournalEntry::where('source_type', JournalEntry::SOURCE_AR_PAYMENT)->where('source_id', $payment->id)->value('status'))->toBe(JournalEntry::STATUS_CANCELLED)
        ->and($invoice->cancelLockReason())->toBeNull();

    // Setelah pembayaran dibatalkan, faktur bisa dibatalkan.
    Livewire::test(SalesInvoiceComponent::class)->call('confirmCancelInvoice', $invoice->id)->call('cancelInvoice');
    expect(SalesInvoice::withTrashed()->find($invoice->id)->status)->toBe(SalesInvoice::STATUS_CANCELLED);
});

it('refunds a posted dp only while its pre order has not become a sales order', function () {
    $preOrder = PreOrder::create([
        'pre_order_no' => 'PRE-REV-1', 'date' => now()->toDateString(), 'customer_id' => $this->customer->id, 'customer_address_id' => $this->address->id,
        'subtotal' => 100000, 'discount_total' => 0, 'tax_amount' => 0, 'grand_total' => 100000, 'dp_amount' => 50000,
        'dp_payment_status' => PreOrder::DP_STATUS_UNPAID, 'status' => PreOrder::STATUS_CONFIRMED, 'created_by' => $this->user->id,
    ]);
    $payment = ArDpPayment::create([
        'code' => 'ARDP-REV-1', 'payment_date' => now()->toDateString(), 'pre_order_id' => $preOrder->id, 'customer_id' => $this->customer->id,
        'bank_account_id' => $this->bank->id, 'amount' => 50000, 'payment_method' => 'Transfer', 'status' => ArDpPayment::STATUS_DRAFT, 'created_by' => $this->user->id,
    ]);
    $payment->allocations()->create(['pre_order_id' => $preOrder->id, 'amount' => 50000]);

    Livewire::test(ArDpPaymentComponent::class)->call('confirmPost', $payment->id)->call('post');
    expect($preOrder->fresh()->dp_payment_status)->toBe(PreOrder::DP_STATUS_PAID);

    // Sudah jadi SO: DP tidak boleh dikembalikan.
    $preOrder->update(['status' => PreOrder::STATUS_SALES_ORDER]);
    Livewire::test(ArDpPaymentComponent::class)->call('confirmCancel', $payment->id)->assertSet('showCancelModal', false);

    $preOrder->update(['status' => PreOrder::STATUS_CONFIRMED]);
    Livewire::test(ArDpPaymentComponent::class)->call('confirmCancel', $payment->id)->call('cancelPayment');

    expect($payment->fresh()->status)->toBe(ArDpPayment::STATUS_CANCELLED)
        ->and($preOrder->fresh()->dp_payment_status)->toBe(PreOrder::DP_STATUS_UNPAID)
        ->and(JournalEntry::where('source_type', JournalEntry::SOURCE_AR_DP_PAYMENT)->where('source_id', $payment->id)->value('status'))->toBe(JournalEntry::STATUS_CANCELLED);
});

it('cancels a posted expense together with its journal', function () {
    $expense = Expense::create([
        'code' => 'EXP-REV-1', 'expense_date' => now()->toDateString(), 'bank_account_id' => $this->bank->id,
        'payee' => 'PLN', 'total_amount' => 25000, 'status' => Expense::STATUS_POSTED, 'created_by' => $this->user->id,
    ]);
    $journal = JournalEntry::create([
        'code' => 'JE-EXP-REV', 'date' => now()->toDateString(), 'source_type' => JournalEntry::SOURCE_EXPENSE, 'source_id' => $expense->id,
        'description' => 'Pengeluaran', 'status' => JournalEntry::STATUS_POSTED, 'created_by' => $this->user->id,
    ]);

    Livewire::test(ExpenseComponent::class)->call('confirmCancel', $expense->id)->call('cancelExpense');

    expect($expense->fresh()->status)->toBe(Expense::STATUS_CANCELLED)
        ->and($journal->fresh()->status)->toBe(JournalEntry::STATUS_CANCELLED);
});

it('cancels a posted sales return invoice and gives the credit back to the original invoice', function () {
    $this->invoice->update(['amount_due' => 80000]);
    $delivery = DeliveryOrder::create([
        'delivery_no' => 'SJ-REV-1', 'delivery_date' => now()->toDateString(), 'sales_order_id' => $this->order->id,
        'customer_id' => $this->customer->id, 'customer_address_id' => $this->address->id,
        'status' => DeliveryOrder::STATUS_INVOICED, 'created_by' => $this->user->id,
    ]);
    $return = SalesReturn::create([
        'return_no' => 'RJ-REV-1', 'return_date' => now()->toDateString(), 'customer_id' => $this->customer->id,
        'delivery_order_id' => $delivery->id, 'sales_order_id' => $this->order->id,
        'status' => SalesReturn::STATUS_CONFIRMED, 'created_by' => $this->user->id,
    ]);
    $returnInvoice = SalesReturnInvoice::create([
        'credit_note_no' => 'NK-REV-1', 'invoice_date' => now()->toDateString(), 'sales_return_id' => $return->id,
        'sales_invoice_id' => $this->invoice->id, 'customer_id' => $this->customer->id, 'subtotal' => 20000, 'tax_amount' => 0,
        'grand_total' => 20000, 'status' => SalesReturnInvoice::STATUS_POSTED, 'created_by' => $this->user->id,
    ]);

    Livewire::test(SalesReturnInvoiceComponent::class)->call('confirmCancel', $returnInvoice->id)->call('cancelInvoice');

    expect(SalesReturnInvoice::withTrashed()->find($returnInvoice->id)->status)->toBe(SalesReturnInvoice::STATUS_CANCELLED)
        ->and($this->invoice->fresh()->amount_due)->toBe(100000)
        ->and($return->fresh()->returnInvoice)->toBeNull();
});
