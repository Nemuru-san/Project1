<?php

namespace App\Livewire\Finance\Report;

use App\Models\ChartOfAccount;
use App\Services\Finance\LedgerService;
use Illuminate\Support\Collection;
use Livewire\Component;

class BalanceSheet extends Component
{
    public string $asOf = '';

    public function mount(): void
    {
        $this->asOf = now()->toDateString();
    }

    public function resetFilters(): void
    {
        $this->asOf = now()->toDateString();
    }

    public function render(LedgerService $ledger)
    {
        $asOf = $this->asOf ?: null;
        $totals = $ledger->totalsByAccount(to: $asOf);
        $section = fn (string $type) => $this->section($type, $totals);

        $assets = $section(ChartOfAccount::TYPE_ASSET);
        $liabilities = $section(ChartOfAccount::TYPE_LIABILITY);
        $equity = $section(ChartOfAccount::TYPE_EQUITY);
        // Laba yang belum ditutup ke Laba Ditahan: akumulasi pendapatan - HPP - beban s.d. tanggal neraca.
        $currentEarnings = $ledger->netIncome(to: $asOf);
        $totalEquity = $equity['total'] + $currentEarnings;

        return view('livewire.finance.report.balance-sheet', [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'currentEarnings' => $currentEarnings,
            'totalEquity' => $totalEquity,
            'totalLiabilitiesEquity' => $liabilities['total'] + $totalEquity,
        ]);
    }

    /**
     * @return array{rows: Collection, total: int}
     */
    private function section(string $type, Collection $totals): array
    {
        $rows = ChartOfAccount::query()
            ->where('type', $type)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get()
            ->map(function (ChartOfAccount $account) use ($totals) {
                $row = $totals->get($account->id, ['debit' => 0, 'credit' => 0]);

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'amount' => LedgerService::signedByType($account->type, $row['debit'], $row['credit']),
                ];
            })
            ->filter(fn (array $row) => $row['amount'] !== 0)
            ->values();

        return ['rows' => $rows, 'total' => $rows->sum('amount')];
    }
}
