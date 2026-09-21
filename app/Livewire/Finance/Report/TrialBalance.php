<?php

namespace App\Livewire\Finance\Report;

use App\Models\ChartOfAccount;
use App\Services\Finance\LedgerService;
use Livewire\Component;

class TrialBalance extends Component
{
    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $hideZero = true;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function resetFilters(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->hideZero = true;
    }

    public function render(LedgerService $ledger)
    {
        $accounts = ChartOfAccount::query()->where('is_postable', true)->orderBy('code')->get();
        $openingTo = $this->dateFrom ? now()->parse($this->dateFrom)->subDay()->toDateString() : null;
        $opening = $this->dateFrom ? $ledger->totalsByAccount(to: $openingTo) : collect();
        $mutation = $ledger->totalsByAccount($this->dateFrom ?: null, $this->dateTo ?: null);

        $rows = $accounts->map(function (ChartOfAccount $account) use ($opening, $mutation) {
            $open = $opening->get($account->id, ['debit' => 0, 'credit' => 0]);
            $move = $mutation->get($account->id, ['debit' => 0, 'credit' => 0]);
            // Saldo awal & akhir disajikan sebagai debit/kredit positif sesuai sisi saldonya.
            $openingNet = $open['debit'] - $open['credit'];
            $closingNet = $openingNet + $move['debit'] - $move['credit'];

            return [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'opening_debit' => max(0, $openingNet),
                'opening_credit' => max(0, -$openingNet),
                'debit' => $move['debit'],
                'credit' => $move['credit'],
                'closing_debit' => max(0, $closingNet),
                'closing_credit' => max(0, -$closingNet),
            ];
        })->when($this->hideZero, fn ($rows) => $rows->filter(fn (array $row) => $row['opening_debit'] || $row['opening_credit'] || $row['debit'] || $row['credit']))
            ->values();

        return view('livewire.finance.report.trial-balance', [
            'rows' => $rows,
            'totals' => [
                'opening_debit' => $rows->sum('opening_debit'),
                'opening_credit' => $rows->sum('opening_credit'),
                'debit' => $rows->sum('debit'),
                'credit' => $rows->sum('credit'),
                'closing_debit' => $rows->sum('closing_debit'),
                'closing_credit' => $rows->sum('closing_credit'),
            ],
        ]);
    }
}
