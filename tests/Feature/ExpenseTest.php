<?php

use App\Livewire\Finance\Transaction\Expense as ExpenseComponent;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\User;
use Livewire\Livewire;

function expenseAccount(string $code, string $name, string $type = ChartOfAccount::TYPE_EXPENSE): ChartOfAccount
{
    return ChartOfAccount::create([
        'code' => $code,
        'name' => $name,
        'type' => $type,
        'normal_balance' => ChartOfAccount::NORMAL_DEBIT,
        'is_postable' => true,
        'is_active' => true,
    ]);
}

function expenseBankAccount(): BankAccount
{
    $coa = expenseAccount('110099', 'Bank Expense Test', ChartOfAccount::TYPE_ASSET);

    return BankAccount::create([
        'name' => 'Bank Operasional',
        'bank_name' => 'Bank Test',
        'chart_of_account_id' => $coa->id,
        'is_active' => true,
    ]);
}

it('creates a draft expense with multiple expense accounts', function () {
    $user = User::factory()->create();
    $bank = expenseBankAccount();
    $electricity = expenseAccount('6201', 'Biaya Listrik');
    $internet = expenseAccount('6202', 'Biaya Internet');

    $this->actingAs($user);

    Livewire::test(ExpenseComponent::class)
        ->call('openCreate')
        ->set('expense_date', '2026-07-17')
        ->set('bank_account_id', $bank->id)
        ->set('payee', 'PT Utilitas')
        ->set('reference', 'INV-UTIL-001')
        ->set('detailRows', [
            ['chart_of_account_id' => $electricity->id, 'description' => 'Listrik Juli', 'amount' => 750000],
            ['chart_of_account_id' => $internet->id, 'description' => 'Internet Juli', 'amount' => 250000],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $expense = Expense::with('details')->sole();

    expect($expense->status)->toBe(Expense::STATUS_DRAFT)
        ->and($expense->total_amount)->toBe(1000000)
        ->and($expense->details)->toHaveCount(2)
        ->and($expense->code)->toStartWith('EXP-');
});

it('posts an expense and creates a balanced journal entry', function () {
    $user = User::factory()->create();
    $bank = expenseBankAccount();
    $electricity = expenseAccount('6201', 'Biaya Listrik');
    $internet = expenseAccount('6202', 'Biaya Internet');

    $expense = Expense::create([
        'code' => 'EXP-170726-001',
        'expense_date' => '2026-07-17',
        'bank_account_id' => $bank->id,
        'total_amount' => 1000000,
        'status' => Expense::STATUS_DRAFT,
        'created_by' => $user->id,
    ]);
    $expense->details()->createMany([
        ['chart_of_account_id' => $electricity->id, 'description' => 'Listrik', 'amount' => 750000],
        ['chart_of_account_id' => $internet->id, 'description' => 'Internet', 'amount' => 250000],
    ]);

    $this->actingAs($user);

    Livewire::test(ExpenseComponent::class)
        ->call('confirmPost', $expense->id)
        ->assertSet('showPostModal', true)
        ->call('postExpense')
        ->assertSet('showPostModal', false);

    expect($expense->fresh()->status)->toBe(Expense::STATUS_POSTED);

    $journal = JournalEntry::with('lines')->where('source_type', JournalEntry::SOURCE_EXPENSE)->sole();

    expect($journal->source_id)->toBe($expense->id)
        ->and($journal->status)->toBe(JournalEntry::STATUS_POSTED)
        ->and($journal->lines->sum('debit'))->toBe(1000000)
        ->and($journal->lines->sum('credit'))->toBe(1000000)
        ->and($journal->lines)->toHaveCount(3);
});

it('rejects non expense accounts from expense details', function () {
    $user = User::factory()->create();
    $bank = expenseBankAccount();
    $asset = expenseAccount('120099', 'Aset Tidak Valid', ChartOfAccount::TYPE_ASSET);

    $this->actingAs($user);

    Livewire::test(ExpenseComponent::class)
        ->call('openCreate')
        ->set('bank_account_id', $bank->id)
        ->set('detailRows', [
            ['chart_of_account_id' => $asset->id, 'description' => 'Tidak valid', 'amount' => 100000],
        ])
        ->call('save')
        ->assertHasErrors(['detailRows']);

    expect(Expense::count())->toBe(0);
});

it('filters expenses by date and exposes the authenticated route', function () {
    $user = User::factory()->create();
    $bank = expenseBankAccount();

    foreach ([
        ['EXP-BEFORE', '2026-07-01'],
        ['EXP-IN-RANGE', '2026-07-10'],
        ['EXP-AFTER', '2026-07-20'],
    ] as [$code, $date]) {
        Expense::create([
            'code' => $code,
            'expense_date' => $date,
            'bank_account_id' => $bank->id,
            'total_amount' => 100000,
            'status' => Expense::STATUS_DRAFT,
            'created_by' => $user->id,
        ]);
    }

    $this->actingAs($user)
        ->get(route('finance.transaction.expense'))
        ->assertOk()
        ->assertSee('Pengeluaran');

    Livewire::test(ExpenseComponent::class)
        ->set('dateFrom', '2026-07-05')
        ->set('dateTo', '2026-07-15')
        ->assertSee('EXP-IN-RANGE')
        ->assertDontSee('EXP-BEFORE')
        ->assertDontSee('EXP-AFTER')
        ->call('resetFilters')
        ->assertSee('EXP-BEFORE')
        ->assertSee('EXP-AFTER');
});

it('soft deletes and restores a draft expense without losing its details', function () {
    $user = User::factory()->create();
    $bank = expenseBankAccount();
    $account = expenseAccount('6201', 'Biaya Operasional');
    $expense = Expense::create([
        'code' => 'EXP-RESTORE-001',
        'expense_date' => '2026-07-17',
        'bank_account_id' => $bank->id,
        'total_amount' => 100000,
        'status' => Expense::STATUS_DRAFT,
        'created_by' => $user->id,
    ]);
    $expense->details()->create([
        'chart_of_account_id' => $account->id,
        'description' => 'Biaya yang dipulihkan',
        'amount' => 100000,
    ]);

    $this->actingAs($user);

    Livewire::test(ExpenseComponent::class)
        ->call('confirmDelete', $expense->id)
        ->call('delete');

    expect(Expense::find($expense->id))->toBeNull()
        ->and(Expense::withTrashed()->findOrFail($expense->id)->trashed())->toBeTrue()
        ->and($expense->details()->count())->toBe(1);

    Livewire::test(ExpenseComponent::class)
        ->call('restore', $expense->id);

    expect(Expense::find($expense->id))->not->toBeNull()
        ->and($expense->details()->count())->toBe(1);
});
