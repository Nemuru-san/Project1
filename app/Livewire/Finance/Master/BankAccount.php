<?php

namespace App\Livewire\Finance\Master;

use App\Models\BankAccount as ModelsBankAccount;
use App\Models\ChartOfAccount;
use Livewire\Component;
use Livewire\WithPagination;

class BankAccount extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public bool $showTrashed = false;

    // Form state
    public ?int $bankAccountId = null;
    public string $name = '';
    public string $bank_name = '';
    public string $account_number = '';
    public string $account_holder = '';
    public ?int $chart_of_account_id = null;
    public bool $is_active = true;

    // Modal state
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $deleteTargetId = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'account_holder' => 'nullable|string|max:255',
            'chart_of_account_id' => 'required|integer|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ];
    }

    protected $messages = [
        'name.required' => 'Nama akun bank/cash wajib diisi.',
        'chart_of_account_id.required' => 'Chart of Account wajib dipilih.',
        'chart_of_account_id.exists' => 'Chart of Account tidak valid.',
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
        if (!in_array($field, ['name', 'bank_name', 'account_number', 'account_holder', 'is_active', 'created_at'], true)) {
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
        $bankAccount = ModelsBankAccount::findOrFail($id);

        $this->bankAccountId = $bankAccount->id;
        $this->name = $bankAccount->name;
        $this->bank_name = $bankAccount->bank_name ?? '';
        $this->account_number = $bankAccount->account_number ?? '';
        $this->account_holder = $bankAccount->account_holder ?? '';
        $this->chart_of_account_id = $bankAccount->chart_of_account_id;
        $this->is_active = (bool) $bankAccount->is_active;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $coa = ChartOfAccount::findOrFail($this->chart_of_account_id);

        if (!$coa->is_active || !$coa->is_postable || $coa->type !== 'Asset') {
            $this->addError('chart_of_account_id', 'CoA harus akun Asset yang aktif dan postable.');
            return;
        }

        $data = [
            'name' => $this->name,
            'bank_name' => $this->bank_name ?: null,
            'account_number' => $this->account_number ?: null,
            'account_holder' => $this->account_holder ?: null,
            'chart_of_account_id' => $this->chart_of_account_id,
            'is_active' => $this->is_active,
        ];

        if ($this->bankAccountId) {
            ModelsBankAccount::findOrFail($this->bankAccountId)->update($data);

            $this->dispatch('toast', message: 'Bank account berhasil diperbarui.', type: 'success');
        } else {
            ModelsBankAccount::create($data);

            $this->dispatch('toast', message: 'Bank account berhasil ditambahkan.', type: 'success');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $bankAccount = ModelsBankAccount::findOrFail($id);

        if ($bankAccount->trashed()) {
            return;
        }

        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (!$this->deleteTargetId) {
            return;
        }

        $bankAccount = ModelsBankAccount::findOrFail($this->deleteTargetId);

        if ($bankAccount->apPayments()->exists()) {
            $this->showDeleteModal = false;
            $this->deleteTargetId = null;

            $this->dispatch('toast', message: 'Bank account tidak bisa dihapus karena sudah dipakai AP Payment.', type: 'error');
            return;
        }

        $bankAccount->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'Bank account berhasil dihapus.', type: 'success');
    }

    private function resetForm(): void
    {
        $this->bankAccountId = null;
        $this->name = '';
        $this->bank_name = '';
        $this->account_number = '';
        $this->account_holder = '';
        $this->chart_of_account_id = null;
        $this->is_active = true;

        $this->resetErrorBag();
    }

    public function render()
    {
        $query = ModelsBankAccount::query()
            ->with('chartOfAccount');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('bank_name', 'like', '%' . $this->search . '%')
                    ->orWhere('account_number', 'like', '%' . $this->search . '%')
                    ->orWhere('account_holder', 'like', '%' . $this->search . '%')
                    ->orWhereHas('chartOfAccount', function ($coa) {
                        $coa->where('code', 'like', '%' . $this->search . '%')
                            ->orWhere('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $bankAccounts = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $chartAccounts = ChartOfAccount::query()
            ->whereIn('code', ['1110', '1120'])
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('livewire.finance.master.bank-account', [
            'bankAccounts' => $bankAccounts,
            'chartAccounts' => $chartAccounts,
        ]);
    }
}
