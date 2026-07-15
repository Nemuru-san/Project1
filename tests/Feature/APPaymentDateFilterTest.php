<?php

use App\Livewire\Finance\Transaction\APPayment as APPaymentComponent;
use App\Models\APPayment;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

it('filters ap payments by date and can clear all filters', function () {
    $user = User::factory()->create();
    $supplier = Supplier::create([
        'code' => 'SUP-AP-FILTER',
        'name' => 'Supplier AP Filter',
        'address' => 'Test Address',
        'contact' => 'Test Contact',
        'created_by' => (string) $user->id,
    ]);
    $account = ChartOfAccount::create([
        'code' => '110099',
        'name' => 'Test Bank Account',
        'type' => ChartOfAccount::TYPE_ASSET,
        'normal_balance' => ChartOfAccount::NORMAL_DEBIT,
        'is_postable' => true,
        'is_active' => true,
    ]);
    $bankAccount = BankAccount::create([
        'name' => 'Test Bank',
        'chart_of_account_id' => $account->id,
        'is_active' => true,
    ]);

    foreach ([
        ['AP-DATE-BEFORE', '2026-07-01'],
        ['AP-DATE-IN-RANGE', '2026-07-10'],
        ['AP-DATE-AFTER', '2026-07-20'],
    ] as [$code, $date]) {
        APPayment::create([
            'code' => $code,
            'payment_date' => $date,
            'supplier_id' => $supplier->id,
            'bank_account_id' => $bankAccount->id,
            'total_amount' => 100000,
            'payment_method' => 'Transfer',
            'status' => APPayment::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);
    }

    Livewire::test(APPaymentComponent::class)
        ->assertSee('Tambah Pembayaran')
        ->assertSee('Bersihkan Filter')
        ->set('dateFrom', '2026-07-05')
        ->set('dateTo', '2026-07-15')
        ->assertSee('AP-DATE-IN-RANGE')
        ->assertDontSee('AP-DATE-BEFORE')
        ->assertDontSee('AP-DATE-AFTER')
        ->call('resetFilters')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '')
        ->assertSee('AP-DATE-BEFORE')
        ->assertSee('AP-DATE-AFTER');
});
