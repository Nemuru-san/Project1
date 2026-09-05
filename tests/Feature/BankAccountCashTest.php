<?php

use App\Livewire\Finance\Master\BankAccount as BankAccountComponent;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

it('creates a cash account without bank and account number details', function () {
    $role = Role::create(['name' => 'Finance', 'permissions' => ['finance.master.bank-accounts']]);
    $this->actingAs(User::factory()->for($role)->create());

    $coa = ChartOfAccount::create([
        'code' => '1110',
        'name' => 'Kas',
        'type' => 'Asset',
        'normal_balance' => 'Debit',
        'is_postable' => true,
        'is_active' => true,
    ]);

    Livewire::test(BankAccountComponent::class)
        ->call('openCreate')
        ->set('name', 'Cash Toko')
        ->set('account_type', 'cash')
        ->set('bank_name', 'Tidak disimpan')
        ->set('account_number', '123456')
        ->set('account_holder', 'Tidak disimpan')
        ->set('chart_of_account_id', $coa->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    $account = BankAccount::sole();

    expect($account->account_type)->toBe('cash')
        ->and($account->bank_name)->toBeNull()
        ->and($account->account_number)->toBeNull()
        ->and($account->account_holder)->toBeNull()
        ->and($account->display_label)->toBe('Cash Toko (Cash)');
});
