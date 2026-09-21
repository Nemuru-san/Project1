<?php

namespace App\Livewire\Finance\Report;

use App\Models\ChartOfAccount;
use App\Services\Finance\LedgerService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class GeneralLedger extends Component
{
    use WithPagination;

    public string $accountFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 25;

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['accountFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('accountFilter');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->resetPage();
    }

    public function render(LedgerService $ledger)
    {
        $account = $this->accountFilter ? ChartOfAccount::find($this->accountFilter) : null;
        $openingBalance = 0;
        $rows = collect();

        if ($account) {
            // Saldo awal = seluruh mutasi sebelum tanggal mulai; saldo berjalan mengikuti saldo normal akun.
            $openingBalance = $this->dateFrom
                ? $ledger->balance($account, to: now()->parse($this->dateFrom)->subDay()->toDateString())
                : 0;

            $running = $openingBalance;
            $rows = $ledger->ledgerLines($account->id, $this->dateFrom ?: null, $this->dateTo ?: null)
                ->map(function ($line) use ($account, &$running) {
                    $running += LedgerService::signed($account, (int) $line->debit, (int) $line->credit);

                    return [
                        'date' => $line->journalEntry->date,
                        'code' => $line->journalEntry->code,
                        'source' => $line->journalEntry->source_type,
                        'description' => $line->description ?: $line->journalEntry->description,
                        'debit' => (int) $line->debit,
                        'credit' => (int) $line->credit,
                        'balance' => $running,
                    ];
                });
        }

        $page = $this->getPage();
        $lines = new LengthAwarePaginator(
            $rows->forPage($page, $this->perPage)->values(),
            $rows->count(),
            $this->perPage,
            $page,
            ['path' => request()->url()],
        );

        return view('livewire.finance.report.general-ledger', [
            'accounts' => ChartOfAccount::query()->where('is_postable', true)->orderBy('code')->get(),
            'account' => $account,
            'openingBalance' => $openingBalance,
            'totalDebit' => $rows->sum('debit'),
            'totalCredit' => $rows->sum('credit'),
            'closingBalance' => $rows->last()['balance'] ?? $openingBalance,
            'lines' => $lines,
        ]);
    }
}
