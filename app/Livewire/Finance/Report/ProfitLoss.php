<?php

namespace App\Livewire\Finance\Report;

use App\Models\ChartOfAccount;
use App\Services\Finance\LedgerService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ProfitLoss extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function resetFilters(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function render(LedgerService $ledger)
    {
        $totals = $ledger->totalsByAccount($this->dateFrom ?: null, $this->dateTo ?: null);
        $section = fn (string $type) => $this->section($type, $totals);

        $revenue = $section(ChartOfAccount::TYPE_REVENUE);
        $cogs = $section(ChartOfAccount::TYPE_COGS);
        $expense = $section(ChartOfAccount::TYPE_EXPENSE);
        $grossProfit = $revenue['total'] - $cogs['total'];

        return view('livewire.finance.report.profit-loss', [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'expense' => $expense,
            'grossProfit' => $grossProfit,
            'netIncome' => $grossProfit - $expense['total'],
        ]);
    }

    /**
     * Baris akun postable bertipe $type beserta saldonya (searah saldo normal) dan total tipe.
     * Akun kontra (mis. Diskon/Retur Penjualan bersaldo normal Debit dalam tipe Revenue)
     * otomatis mengurangi total karena disajikan negatif.
     *
     * @return array{rows: Collection, total: int}
     */
    private function section(string $type, Collection $totals): array
    {
        $rows = ChartOfAccount::query()
            ->where('type', $type)
            ->where('is_postable', true)
            ->orderBy('code')
            ->get()
            ->map(function (ChartOfAccount $account) use ($type, $totals) {
                $row = $totals->get($account->id, ['debit' => 0, 'credit' => 0]);
                $amount = LedgerService::signedByType($type, $row['debit'], $row['credit']);

                return ['code' => $account->code, 'name' => $account->name, 'amount' => $amount];
            })
            ->filter(fn (array $row) => $row['amount'] !== 0)
            ->values();

        return ['rows' => $rows, 'total' => $rows->sum('amount')];
    }
}
