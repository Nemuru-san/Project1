<?php

namespace App\Livewire\Finance\Transaction;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Expense as ExpenseModel;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Expense extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'expense_date';

    public string $sortDirection = 'desc';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showTrashed = false;

    public bool $showModal = false;

    public bool $showDetailModal = false;

    public bool $showPostModal = false;

    public bool $showDeleteModal = false;

    public ?int $expenseId = null;

    public ?int $postTargetId = null;

    public ?int $deleteTargetId = null;

    public ?ExpenseModel $selectedExpense = null;

    public string $code = '';

    public string $expense_date = '';

    public ?int $bank_account_id = null;

    public string $payee = '';

    public string $reference = '';

    public string $note = '';

    public int $total_amount = 0;

    public array $detailRows = [];

    protected function rules(): array
    {
        return [
            'expense_date' => 'required|date',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payee' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
            'detailRows' => 'required|array|min:1',
            'detailRows.*.chart_of_account_id' => 'required|exists:chart_of_accounts,id',
            'detailRows.*.description' => 'required|string|max:255',
            'detailRows.*.amount' => 'required|integer|min:1',
        ];
    }

    protected array $messages = [
        'expense_date.required' => 'Tanggal pengeluaran wajib diisi.',
        'bank_account_id.required' => 'Rekening pembayaran wajib dipilih.',
        'detailRows.min' => 'Minimal tambahkan satu detail biaya.',
        'detailRows.*.chart_of_account_id.required' => 'Akun biaya wajib dipilih.',
        'detailRows.*.description.required' => 'Keterangan biaya wajib diisi.',
        'detailRows.*.amount.required' => 'Nominal biaya wajib diisi.',
        'detailRows.*.amount.min' => 'Nominal biaya harus lebih dari 0.',
    ];

    public function mount(): void
    {
        $this->expense_date = now()->format('Y-m-d');
        $this->detailRows = [$this->emptyDetailRow()];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedShowTrashed(): void
    {
        $this->resetPage();
    }

    public function updatedDetailRows(): void
    {
        $this->recalculateTotal();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo', 'showTrashed']);
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['code', 'expense_date', 'total_amount', 'status'], true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->code = $this->generateCode();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $expense = ExpenseModel::with('details')->findOrFail($id);

        if ($expense->status !== ExpenseModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Pengeluaran yang sudah diposting tidak dapat diubah.', type: 'error');

            return;
        }

        $this->resetForm();
        $this->expenseId = $expense->id;
        $this->code = $expense->code;
        $this->expense_date = $expense->expense_date->format('Y-m-d');
        $this->bank_account_id = $expense->bank_account_id;
        $this->payee = $expense->payee ?? '';
        $this->reference = $expense->reference ?? '';
        $this->note = $expense->note ?? '';
        $this->total_amount = (int) $expense->total_amount;
        $this->detailRows = $expense->details->map(fn ($detail) => [
            'chart_of_account_id' => $detail->chart_of_account_id,
            'description' => $detail->description,
            'amount' => (int) $detail->amount,
        ])->toArray();
        $this->showModal = true;
    }

    public function openDetail(int $id): void
    {
        $this->selectedExpense = ExpenseModel::withTrashed()
            ->with(['bankAccount.chartOfAccount', 'details.chartOfAccount', 'creator', 'journalEntry'])
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function addDetailRow(): void
    {
        $this->detailRows[] = $this->emptyDetailRow();
    }

    public function removeDetailRow(int $index): void
    {
        if (count($this->detailRows) <= 1 || ! array_key_exists($index, $this->detailRows)) {
            return;
        }

        unset($this->detailRows[$index]);
        $this->detailRows = array_values($this->detailRows);
        $this->recalculateTotal();
    }

    public function recalculateTotal(): void
    {
        $this->total_amount = collect($this->detailRows)->sum(fn ($row) => (int) ($row['amount'] ?? 0));
    }

    public function save(): void
    {
        $this->recalculateTotal();
        $validated = $this->validate();

        $accountIds = collect($validated['detailRows'])->pluck('chart_of_account_id')->unique();
        $validAccounts = ChartOfAccount::query()
            ->whereIn('id', $accountIds)
            ->where('type', ChartOfAccount::TYPE_EXPENSE)
            ->where('is_active', true)
            ->where('is_postable', true)
            ->count();

        if ($validAccounts !== $accountIds->count()) {
            $this->addError('detailRows', 'Semua akun harus berupa akun biaya yang aktif dan dapat diposting.');

            return;
        }

        $bankAccountIsValid = BankAccount::query()
            ->whereKey($validated['bank_account_id'])
            ->where('is_active', true)
            ->whereHas('chartOfAccount', fn ($query) => $query->where('is_active', true)->where('is_postable', true))
            ->exists();

        if (! $bankAccountIsValid) {
            $this->addError('bank_account_id', 'Rekening pembayaran tidak aktif atau belum terhubung ke COA aktif.');

            return;
        }

        DB::transaction(function () use ($validated) {
            $data = [
                'code' => $this->code ?: $this->generateCode(),
                'expense_date' => $validated['expense_date'],
                'bank_account_id' => $validated['bank_account_id'],
                'payee' => $validated['payee'] ?: null,
                'reference' => $validated['reference'] ?: null,
                'total_amount' => $this->total_amount,
                'note' => $validated['note'] ?: null,
            ];

            if ($this->expenseId) {
                $expense = ExpenseModel::lockForUpdate()->findOrFail($this->expenseId);

                if ($expense->status !== ExpenseModel::STATUS_DRAFT) {
                    throw new \RuntimeException('Pengeluaran yang sudah diposting tidak dapat diubah.');
                }

                $expense->update($data);
                $expense->details()->delete();
            } else {
                $expense = ExpenseModel::create($data + [
                    'status' => ExpenseModel::STATUS_DRAFT,
                    'created_by' => Auth::id(),
                ]);
            }

            $expense->details()->createMany($validated['detailRows']);
        });

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Pengeluaran berhasil disimpan.', type: 'success');
    }

    public function confirmPost(int $id): void
    {
        $expense = ExpenseModel::withCount('details')->findOrFail($id);

        if ($expense->status !== ExpenseModel::STATUS_DRAFT || $expense->details_count === 0) {
            $this->dispatch('toast', message: 'Hanya pengeluaran berstatus Draf yang memiliki rincian yang dapat diposting.', type: 'error');

            return;
        }

        $this->postTargetId = $id;
        $this->showPostModal = true;
    }

    public function postExpense(): void
    {
        if (! $this->postTargetId) {
            return;
        }

        try {
            DB::transaction(function () {
                $expense = ExpenseModel::with(['details.chartOfAccount', 'bankAccount.chartOfAccount'])
                    ->lockForUpdate()
                    ->findOrFail($this->postTargetId);

                if ($expense->status !== ExpenseModel::STATUS_DRAFT) {
                    throw new \RuntimeException('Hanya pengeluaran berstatus Draf yang dapat diposting.');
                }

                $total = $expense->details->sum('amount');

                if ($expense->details->isEmpty() || $total <= 0 || $total !== (int) $expense->total_amount) {
                    throw new \RuntimeException('Rincian dan total pengeluaran tidak valid.');
                }

                $bankCoa = $expense->bankAccount?->chartOfAccount;

                if (! $expense->bankAccount?->is_active || ! $bankCoa?->is_active || ! $bankCoa?->is_postable) {
                    throw new \RuntimeException('Rekening pembayaran atau akun bank tidak aktif/tidak dapat diposting.');
                }

                foreach ($expense->details as $detail) {
                    if ($detail->chartOfAccount?->type !== ChartOfAccount::TYPE_EXPENSE
                        || ! $detail->chartOfAccount?->is_active
                        || ! $detail->chartOfAccount?->is_postable) {
                        throw new \RuntimeException('Terdapat akun biaya yang tidak aktif atau tidak dapat diposting.');
                    }
                }

                $journal = JournalEntry::create([
                    'code' => $this->generateJournalCode(),
                    'date' => $expense->expense_date,
                    'source_type' => JournalEntry::SOURCE_EXPENSE,
                    'source_id' => $expense->id,
                    'description' => 'Pengeluaran '.$expense->code,
                    'status' => JournalEntry::STATUS_POSTED,
                    'created_by' => Auth::id(),
                ]);

                foreach ($expense->details as $detail) {
                    $journal->lines()->create([
                        'chart_of_account_id' => $detail->chart_of_account_id,
                        'debit' => (int) $detail->amount,
                        'credit' => 0,
                        'description' => $detail->description,
                    ]);
                }

                $journal->lines()->create([
                    'chart_of_account_id' => $bankCoa->id,
                    'debit' => 0,
                    'credit' => (int) $expense->total_amount,
                    'description' => 'Pembayaran pengeluaran '.$expense->code,
                ]);

                $expense->update(['status' => ExpenseModel::STATUS_POSTED]);
            });

            $this->dispatch('toast', message: 'Pengeluaran berhasil diposting dan jurnal otomatis dibuat.', type: 'success');
        } catch (\Throwable $exception) {
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');
        } finally {
            $this->showPostModal = false;
            $this->postTargetId = null;
        }
    }

    public function confirmDelete(int $id): void
    {
        $expense = ExpenseModel::findOrFail($id);

        if ($expense->status !== ExpenseModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Pengeluaran yang sudah diposting tidak dapat dihapus.', type: 'error');

            return;
        }

        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! $this->deleteTargetId) {
            return;
        }

        $expense = ExpenseModel::findOrFail($this->deleteTargetId);

        if ($expense->status !== ExpenseModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Pengeluaran yang sudah diposting tidak dapat dihapus.', type: 'error');
        } else {
            $expense->delete();
            $this->dispatch('toast', message: 'Pengeluaran berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
    }

    public function restore(int $id): void
    {
        $expense = ExpenseModel::onlyTrashed()->findOrFail($id);
        $expense->restore();

        $this->dispatch('toast', message: 'Pengeluaran berhasil dipulihkan.', type: 'success');
    }

    private function emptyDetailRow(): array
    {
        return ['chart_of_account_id' => null, 'description' => '', 'amount' => 0];
    }

    private function resetForm(): void
    {
        $this->reset(['expenseId', 'code', 'bank_account_id', 'payee', 'reference', 'note', 'total_amount']);
        $this->expense_date = now()->format('Y-m-d');
        $this->detailRows = [$this->emptyDetailRow()];
        $this->resetValidation();
    }

    private function generateCode(): string
    {
        $prefix = 'EXP-'.now()->format('dmy').'-';
        $lastCode = ExpenseModel::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $sequence = $lastCode ? (int) substr($lastCode, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    private function generateJournalCode(): string
    {
        $prefix = 'JE-'.now()->format('dmy').'-';
        $lastCode = JournalEntry::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $sequence = $lastCode ? (int) substr($lastCode, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $expenses = ExpenseModel::query()
            ->with(['bankAccount', 'details.chartOfAccount'])
            ->when($this->showTrashed, fn ($query) => $query->withTrashed())
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('code', 'like', '%'.$this->search.'%')
                        ->orWhere('payee', 'like', '%'.$this->search.'%')
                        ->orWhere('reference', 'like', '%'.$this->search.'%')
                        ->orWhereHas('details', fn ($detailQuery) => $detailQuery->where('description', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('expense_date', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.finance.transaction.expense', [
            'expenses' => $expenses,
            'bankAccounts' => BankAccount::query()->where('is_active', true)->with('chartOfAccount')->orderBy('name')->get(),
            'expenseAccounts' => ChartOfAccount::query()
                ->where('type', ChartOfAccount::TYPE_EXPENSE)
                ->where('is_active', true)
                ->where('is_postable', true)
                ->orderBy('code')
                ->get(),
        ]);
    }
}
