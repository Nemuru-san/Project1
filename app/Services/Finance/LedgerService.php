<?php

namespace App\Services\Finance;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Agregasi jurnal yang sudah diposting menjadi saldo per akun.
 * Semua laporan keuangan (Buku Besar, Neraca Saldo, Laba Rugi, Neraca) membaca dari sini.
 */
class LedgerService
{
    /**
     * Total debit & kredit per akun dari jurnal berstatus Posted dalam rentang tanggal.
     *
     * @return Collection<int, array{debit:int, credit:int}> dikunci chart_of_account_id
     */
    public function totalsByAccount(?string $from = null, ?string $to = null, ?array $accountIds = null): Collection
    {
        return $this->postedLines($from, $to)
            ->when($accountIds !== null, fn (Builder $query) => $query->whereIn('journal_entry_lines.chart_of_account_id', $accountIds))
            ->selectRaw('journal_entry_lines.chart_of_account_id, SUM(journal_entry_lines.debit) AS debit, SUM(journal_entry_lines.credit) AS credit')
            ->groupBy('journal_entry_lines.chart_of_account_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->chart_of_account_id => ['debit' => (int) $row->debit, 'credit' => (int) $row->credit]]);
    }

    /**
     * Saldo bersih satu akun (searah saldo normalnya) sampai tanggal tertentu.
     */
    public function balance(ChartOfAccount $account, ?string $to = null, ?string $from = null): int
    {
        $totals = $this->totalsByAccount($from, $to, [$account->id])->get($account->id, ['debit' => 0, 'credit' => 0]);

        return self::signed($account, $totals['debit'], $totals['credit']);
    }

    /**
     * Saldo bersih gabungan akun-akun bertipe tertentu (mis. seluruh Revenue) dalam periode.
     */
    public function balanceByType(string $type, ?string $from = null, ?string $to = null): int
    {
        $accountIds = ChartOfAccount::query()->where('type', $type)->where('is_postable', true)->pluck('id')->all();

        return $this->totalsByAccount($from, $to, $accountIds)
            ->sum(fn (array $row) => self::signedByType($type, $row['debit'], $row['credit']));
    }

    /**
     * Laba bersih periode = Pendapatan - HPP - Beban.
     */
    public function netIncome(?string $from = null, ?string $to = null): int
    {
        return $this->balanceByType(ChartOfAccount::TYPE_REVENUE, $from, $to)
            - $this->balanceByType(ChartOfAccount::TYPE_COGS, $from, $to)
            - $this->balanceByType(ChartOfAccount::TYPE_EXPENSE, $from, $to);
    }

    /**
     * Baris jurnal Posted satu akun dalam periode, urut tanggal, untuk Buku Besar.
     *
     * @return Collection<int, JournalEntryLine>
     */
    public function ledgerLines(int $accountId, ?string $from, ?string $to): Collection
    {
        return JournalEntryLine::query()
            ->with('journalEntry')
            ->where('chart_of_account_id', $accountId)
            ->whereHas('journalEntry', fn (Builder $query) => $query
                ->where('status', JournalEntry::STATUS_POSTED)
                ->when($from, fn (Builder $q) => $q->whereDate('date', '>=', $from))
                ->when($to, fn (Builder $q) => $q->whereDate('date', '<=', $to)))
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->orderBy('journal_entries.date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_entry_lines.id')
            ->select('journal_entry_lines.*')
            ->get();
    }

    /**
     * Debit menambah akun bersaldo normal Debit; kredit menambah akun bersaldo normal Kredit.
     */
    public static function signed(ChartOfAccount $account, int $debit, int $credit): int
    {
        return $account->normal_balance === ChartOfAccount::NORMAL_DEBIT ? $debit - $credit : $credit - $debit;
    }

    /**
     * Nilai searah tipe laporannya, sehingga akun kontra (mis. Diskon Penjualan bersaldo
     * normal Debit di tipe Revenue) otomatis mengurangi total tipenya.
     * Asset/COGS/Expense bertambah di debit; Liability/Equity/Revenue bertambah di kredit.
     */
    public static function signedByType(string $type, int $debit, int $credit): int
    {
        return in_array($type, [ChartOfAccount::TYPE_ASSET, ChartOfAccount::TYPE_COGS, ChartOfAccount::TYPE_EXPENSE], true)
            ? $debit - $credit
            : $credit - $debit;
    }

    private function postedLines(?string $from, ?string $to): Builder
    {
        return JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.status', JournalEntry::STATUS_POSTED)
            ->whereNull('journal_entries.deleted_at')
            ->when($from, fn (Builder $query) => $query->whereDate('journal_entries.date', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereDate('journal_entries.date', '<=', $to));
    }
}
