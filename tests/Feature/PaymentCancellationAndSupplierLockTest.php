<?php

use App\Livewire\Finance\Transaction\APPayment as APPaymentComponent;
use App\Livewire\Purchasing\Transaction\PurchaseInvoice as PurchaseInvoiceComponent;
use App\Livewire\Supplier\SupplierManager;
use App\Models\APPayment;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

function paymentCancellationUser(): User
{
    $role = Role::create(['name' => 'Super Admin', 'permissions' => ['*']]);

    return User::factory()->for($role)->create();
}

it('membatalkan pembayaran utang sehingga faktur pembelian bisa diubah lagi', function () {
    $user = paymentCancellationUser();
    $this->actingAs($user);

    $supplier = Supplier::create(['code' => 'SUP-C1', 'name' => 'Pemasok', 'address' => 'A', 'contact' => 'C', 'created_by' => (string) $user->id]);
    ChartOfAccount::create([
        'code' => '2100', 'name' => 'Utang Usaha', 'type' => ChartOfAccount::TYPE_LIABILITY,
        'normal_balance' => 'Credit', 'is_postable' => true, 'is_active' => true,
    ]);
    $cashAccount = ChartOfAccount::create([
        'code' => '1100', 'name' => 'Kas', 'type' => ChartOfAccount::TYPE_ASSET,
        'normal_balance' => 'Debit', 'is_postable' => true, 'is_active' => true,
    ]);
    $bankAccount = BankAccount::create([
        'name' => 'Kas Besar', 'account_type' => 'cash', 'bank_name' => 'Kas', 'account_number' => '-',
        'account_holder' => 'PT A', 'chart_of_account_id' => $cashAccount->id, 'is_active' => true,
    ]);

    $purchaseOrder = PurchaseOrder::create([
        'code' => 'PO-C1', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'user_id' => $user->id, 'total_price' => 400000, 'tax' => false, 'ppn' => 0,
        'gross' => 400000, 'nett' => 400000, 'status' => PurchaseOrder::STATUS_APPROVED,
    ]);
    $invoice = PurchaseInvoice::create([
        'code' => 'PIV-C1', 'date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'purchase_order_id' => $purchaseOrder->id, 'sub_total' => 400000, 'grand_total' => 400000,
        'paid_amount' => 0, 'remaining_amount' => 400000, 'status' => PurchaseInvoice::STATUS_POSTED,
        'payment_status' => PurchaseInvoice::PAYMENT_UNPAID, 'created_by' => $user->id,
    ]);

    // Faktur Posted yang belum dibayar masih bisa diubah.
    expect($invoice->isEditable())->toBeTrue();

    $payment = APPayment::create([
        'code' => 'APP-C1', 'payment_date' => now()->toDateString(), 'supplier_id' => $supplier->id,
        'bank_account_id' => $bankAccount->id, 'total_amount' => 400000, 'payment_method' => 'transfer',
        'status' => APPayment::STATUS_DRAFT, 'created_by' => $user->id,
    ]);
    $payment->details()->create(['purchase_invoice_id' => $invoice->id, 'amount' => 400000]);

    Livewire::test(APPaymentComponent::class)
        ->call('confirmPost', $payment->id)
        ->call('postPayment');

    expect($invoice->fresh()->isEditable())->toBeFalse()
        ->and($purchaseOrder->fresh()->status)->toBe(PurchaseOrder::STATUS_PAID);

    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openEdit', $invoice->id)
        ->assertSet('showModal', false);

    Livewire::test(APPaymentComponent::class)
        ->call('confirmCancelPayment', $payment->id)
        ->assertSet('showCancelPaymentModal', true)
        ->call('cancelPayment');

    $invoice->refresh();

    expect($payment->fresh()->status)->toBe(APPayment::STATUS_CANCELLED)
        ->and($invoice->paid_amount)->toBe(0)
        ->and($invoice->remaining_amount)->toBe(400000)
        ->and($invoice->payment_status)->toBe(PurchaseInvoice::PAYMENT_UNPAID)
        ->and($invoice->isEditable())->toBeTrue()
        ->and($purchaseOrder->fresh()->status)->toBe(PurchaseOrder::STATUS_APPROVED)
        ->and(JournalEntry::where('source_type', JournalEntry::SOURCE_AP_PAYMENT)->where('source_id', $payment->id)->value('status'))
        ->toBe(JournalEntry::STATUS_CANCELLED);

    Livewire::test(PurchaseInvoiceComponent::class)
        ->call('openEdit', $invoice->id)
        ->assertSet('invoiceId', $invoice->id)
        ->assertSet('showModal', true);
});

it('hanya mengizinkan pemasok yang sudah punya PO untuk dinonaktifkan, bukan dihapus', function () {
    $user = paymentCancellationUser();
    $this->actingAs($user);

    $used = Supplier::create(['code' => 'SUP-U', 'name' => 'Dipakai', 'address' => 'A', 'contact' => 'C', 'created_by' => (string) $user->id]);
    $unused = Supplier::create(['code' => 'SUP-N', 'name' => 'Baru', 'address' => 'A', 'contact' => 'C', 'created_by' => (string) $user->id]);

    PurchaseOrder::create([
        'code' => 'PO-S1', 'date' => now()->toDateString(), 'supplier_id' => $used->id,
        'user_id' => $user->id, 'total_price' => 0, 'tax' => false, 'ppn' => 0,
        'gross' => 0, 'nett' => 0, 'status' => PurchaseOrder::STATUS_DRAFT,
    ]);

    Livewire::test(SupplierManager::class)
        ->call('confirmDelete', $used->id)
        ->assertSet('showDeleteModal', false)
        ->call('toggleActive', $used->id);

    expect($used->fresh()->trashed())->toBeFalse()
        ->and($used->fresh()->is_active)->toBeFalse();

    Livewire::test(SupplierManager::class)
        ->call('confirmDelete', $unused->id)
        ->assertSet('showDeleteModal', true)
        ->call('delete');

    expect($unused->fresh()->trashed())->toBeTrue();
});
