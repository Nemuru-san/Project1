<?php

use App\Livewire\Finance\Report\BalanceSheet;
use App\Livewire\Finance\Report\GeneralLedger;
use App\Livewire\Finance\Report\ProfitLoss;
use App\Livewire\Finance\Report\TrialBalance;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Finance\LedgerService;
use Livewire\Livewire;

function reportAccount(string $code, string $name, string $type, string $normal): ChartOfAccount
{
    return ChartOfAccount::create([
        'code' => $code, 'name' => $name, 'type' => $type, 'normal_balance' => $normal,
        'is_postable' => true, 'is_active' => true,
    ]);
}

function postJournal(string $date, array $lines, string $status = JournalEntry::STATUS_POSTED): JournalEntry
{
    $journal = JournalEntry::create(['code' => 'JR-'.uniqid(), 'date' => $date, 'status' => $status, 'description' => 'Test']);
    foreach ($lines as [$account, $debit, $credit]) {
        $journal->lines()->create(['chart_of_account_id' => $account->id, 'debit' => $debit, 'credit' => $credit]);
    }

    return $journal;
}

beforeEach(function () {
    $this->actingAs(User::factory()->superAdmin()->create());
    $this->cash = reportAccount('1110', 'Kas', ChartOfAccount::TYPE_ASSET, 'Debit');
    $this->receivable = reportAccount('1300', 'Piutang', ChartOfAccount::TYPE_ASSET, 'Debit');
    $this->payable = reportAccount('2100', 'Utang', ChartOfAccount::TYPE_LIABILITY, 'Credit');
    $this->capital = reportAccount('3100', 'Modal', ChartOfAccount::TYPE_EQUITY, 'Credit');
    $this->sales = reportAccount('4100', 'Penjualan', ChartOfAccount::TYPE_REVENUE, 'Credit');
    $this->discount = reportAccount('4200', 'Diskon Penjualan', ChartOfAccount::TYPE_REVENUE, 'Debit');
    $this->cogs = reportAccount('5100', 'HPP', ChartOfAccount::TYPE_COGS, 'Debit');
    $this->expense = reportAccount('6200', 'Beban Operasional', ChartOfAccount::TYPE_EXPENSE, 'Debit');

    // Bulan lalu: setoran modal 10jt.
    postJournal('2026-08-15', [[$this->cash, 10_000_000, 0], [$this->capital, 0, 10_000_000]]);
    // Bulan ini: penjualan kredit 5jt dengan diskon 500rb, HPP 3jt, beban 1jt dibayar tunai.
    postJournal('2026-09-05', [[$this->receivable, 4_500_000, 0], [$this->discount, 500_000, 0], [$this->sales, 0, 5_000_000]]);
    postJournal('2026-09-06', [[$this->cogs, 3_000_000, 0], [$this->cash, 0, 3_000_000]]);
    postJournal('2026-09-07', [[$this->expense, 1_000_000, 0], [$this->cash, 0, 1_000_000]]);
    // Jurnal draf tidak boleh ikut dihitung.
    postJournal('2026-09-08', [[$this->expense, 9_000_000, 0], [$this->cash, 0, 9_000_000]], JournalEntry::STATUS_DRAFT);
});

it('computes net income and account balances only from posted journals', function () {
    $ledger = app(LedgerService::class);

    expect($ledger->netIncome('2026-09-01', '2026-09-30'))->toBe(500_000)
        ->and($ledger->balance($this->cash, to: '2026-09-30'))->toBe(6_000_000)
        ->and($ledger->balance($this->cash, to: '2026-08-31'))->toBe(10_000_000);
});

it('shows the general ledger with opening balance and running balance', function () {
    Livewire::test(GeneralLedger::class)
        ->set('dateFrom', '2026-09-01')
        ->set('dateTo', '2026-09-30')
        ->set('accountFilter', (string) $this->cash->id)
        ->assertViewHas('openingBalance', 10_000_000)
        ->assertViewHas('closingBalance', 6_000_000)
        ->assertSee('7.000.000')
        ->assertSee('6.000.000');
});

it('produces a balanced trial balance', function () {
    Livewire::test(TrialBalance::class)
        ->set('dateFrom', '2026-09-01')
        ->set('dateTo', '2026-09-30')
        ->assertViewHas('totals', fn (array $totals) => $totals['closing_debit'] === $totals['closing_credit']
            && $totals['debit'] === 9_000_000 && $totals['credit'] === 9_000_000)
        ->assertDontSee('tidak seimbang');
});

it('reports gross and net profit with contra revenue accounts', function () {
    Livewire::test(ProfitLoss::class)
        ->set('dateFrom', '2026-09-01')
        ->set('dateTo', '2026-09-30')
        ->assertViewHas('grossProfit', 1_500_000)
        ->assertViewHas('netIncome', 500_000)
        ->assertSee('Diskon Penjualan')
        ->assertSee('(Rp 500.000)');
});

it('produces a balanced balance sheet including current earnings', function () {
    Livewire::test(BalanceSheet::class)
        ->set('asOf', '2026-09-30')
        ->assertViewHas('currentEarnings', 500_000)
        ->assertViewHas('totalLiabilitiesEquity', 10_500_000)
        ->assertViewHas('assets', fn (array $assets) => $assets['total'] === 10_500_000)
        ->assertSee('Seimbang');
});

it('exposes the four financial report routes', function () {
    foreach (['general-ledger', 'trial-balance', 'profit-loss', 'balance-sheet'] as $report) {
        $this->get(route("finance.report.{$report}"))->assertOk();
    }
});
