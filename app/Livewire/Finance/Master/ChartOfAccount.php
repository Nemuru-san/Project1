<?php

namespace App\Livewire\Finance\Master;

use App\Models\ChartOfAccount as ModelsChartOfAccount;
use Livewire\Component;
use Livewire\WithPagination;

class ChartOfAccount extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'code';

    public string $sortDirection = 'asc';

    public bool $showTrashed = false;

    // Form state
    public ?int $accountId = null;

    public string $code = '';

    public string $name = '';

    public string $type = '';

    public string $normal_balance = '';

    public ?int $parent_id = null;

    public bool $is_postable = true;

    public bool $is_active = true;

    // Modal state
    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    public array $typeOptions = [
        'Asset',
        'Liability',
        'Equity',
        'Revenue',
        'Expense',
        'COGS',
    ];

    public array $typeLabels = [
        'Asset' => 'Aset',
        'Liability' => 'Liabilitas',
        'Equity' => 'Ekuitas',
        'Revenue' => 'Pendapatan',
        'Expense' => 'Biaya',
        'COGS' => 'Harga Pokok Penjualan',
    ];

    public array $normalBalanceOptions = [
        'Debit',
        'Credit',
    ];

    protected function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:chart_of_accounts,code,'.($this->accountId ?? 'NULL').',id,deleted_at,NULL',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:Asset,Liability,Equity,Revenue,Expense,COGS',
            'normal_balance' => 'required|string|in:Debit,Credit',
            'parent_id' => 'nullable|integer|exists:chart_of_accounts,id',
            'is_postable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'code.required' => 'Kode akun wajib diisi.',
        'code.unique' => 'Kode akun sudah digunakan.',
        'name.required' => 'Nama akun wajib diisi.',
        'type.required' => 'Tipe akun wajib dipilih.',
        'type.in' => 'Tipe akun tidak valid.',
        'normal_balance.required' => 'Saldo normal wajib dipilih.',
        'normal_balance.in' => 'Saldo normal tidak valid.',
        'parent_id.exists' => 'Parent account tidak valid.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['code', 'name', 'type', 'normal_balance', 'is_active', 'created_at'], true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $field
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';

        $this->sortField = $field;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $account = ModelsChartOfAccount::findOrFail($id);

        if (! $account->is_postable) {
            $this->dispatch('toast', message: 'Akun header tidak bisa diedit.', type: 'error');

            return;
        }

        $this->accountId = $account->id;
        $this->code = $account->code;
        $this->name = $account->name;
        $this->type = $account->type;
        $this->normal_balance = $account->normal_balance;
        $this->parent_id = $account->parent_id;
        $this->is_postable = (bool) $account->is_postable;
        $this->is_active = (bool) $account->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->accountId && $this->parent_id === $this->accountId) {
            $this->addError('parent_id', 'Parent account tidak boleh dirinya sendiri.');

            return;
        }

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'normal_balance' => $this->normal_balance,
            'parent_id' => $this->parent_id ?: null,
            'is_postable' => $this->is_postable,
            'is_active' => $this->is_active,
        ];

        if ($this->accountId) {
            ModelsChartOfAccount::findOrFail($this->accountId)->update($data);

            $this->dispatch('toast', message: 'Akun berhasil diperbarui.', type: 'success');
        } else {
            ModelsChartOfAccount::create($data);

            $this->dispatch('toast', message: 'Akun berhasil ditambahkan.', type: 'success');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $account = ModelsChartOfAccount::findOrFail($id);

        if ($account->trashed()) {
            return;
        }

        if (! $account->is_postable) {
            $this->dispatch('toast', message: 'Akun header tidak bisa dihapus.', type: 'error');

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

        $account = ModelsChartOfAccount::findOrFail($this->deleteTargetId);

        if (! $account->is_postable) {
            $this->showDeleteModal = false;
            $this->deleteTargetId = null;

            $this->dispatch('toast', message: 'Akun header tidak bisa dihapus.', type: 'error');

            return;
        }

        if ($account->children()->exists()) {
            $this->showDeleteModal = false;
            $this->deleteTargetId = null;

            $this->dispatch('toast', message: 'Akun tidak bisa dihapus karena masih punya child account.', type: 'error');

            return;
        }

        if ($account->journalEntryLines()->exists()) {
            $this->showDeleteModal = false;
            $this->deleteTargetId = null;

            $this->dispatch('toast', message: 'Akun tidak bisa dihapus karena sudah dipakai di jurnal.', type: 'error');

            return;
        }

        $account->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'Akun berhasil dihapus.', type: 'success');
    }

    private function resetForm(): void
    {
        $this->accountId = null;
        $this->code = '';
        $this->name = '';
        $this->type = '';
        $this->normal_balance = '';
        $this->parent_id = null;
        $this->is_postable = true;
        $this->is_active = true;

        $this->resetErrorBag();
    }

    public function render()
    {
        $query = ModelsChartOfAccount::query()
            ->with('parent');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%')
                    ->orWhere('type', 'like', '%'.$this->search.'%');
            });
        }

        $accounts = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $parentAccounts = ModelsChartOfAccount::query()
            ->when($this->accountId, fn ($q) => $q->where('id', '!=', $this->accountId))
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('livewire.finance.master.chart-of-account', [
            'accounts' => $accounts,
            'parentAccounts' => $parentAccounts,
        ]);
    }
}
