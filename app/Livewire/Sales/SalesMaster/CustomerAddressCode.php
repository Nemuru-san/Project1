<?php

namespace App\Livewire\Sales\SalesMaster;

use App\Models\AddressCode;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerAddressCode extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'code';

    public string $sortDirection = 'asc';

    public bool $showTrashed = false;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    public ?int $editingId = null;

    public string $code = '';

    public string $description = '';

    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    protected array $messages = [
        'code.required' => 'Kode alamat wajib diisi.',
        'code.max' => 'Kode alamat maksimal 50 karakter.',
        'description.max' => 'Keterangan maksimal 255 karakter.',
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
        if (! in_array($field, ['code', 'description', 'is_active', 'created_at'], true)) {
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
        $addressCode = AddressCode::findOrFail($id);

        $this->editingId = $addressCode->id;
        $this->code = $addressCode->code;
        $this->description = $addressCode->description ?? '';
        $this->is_active = $addressCode->is_active;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $code = mb_strtoupper(trim($validated['code']));

        if ($code === '') {
            throw ValidationException::withMessages(['code' => 'Kode alamat wajib diisi.']);
        }

        $duplicateExists = AddressCode::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower($code)])
            ->when($this->editingId, fn ($query) => $query->whereKeyNot($this->editingId))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages(['code' => 'Kode alamat sudah terdaftar.']);
        }

        DB::transaction(function () use ($validated, $code): void {
            $data = [
                'code' => $code,
                'description' => trim((string) ($validated['description'] ?? '')) ?: null,
                'is_active' => $validated['is_active'],
            ];

            if ($this->editingId) {
                $addressCode = AddressCode::findOrFail($this->editingId);
                $oldCode = $addressCode->code;
                $addressCode->update($data);

                if ($oldCode !== $code) {
                    CustomerAddress::withTrashed()->where('code', $oldCode)->update(['code' => $code]);
                }
            } else {
                AddressCode::create($data);
            }
        });

        $message = $this->editingId
            ? 'Kode alamat berhasil diperbarui.'
            : 'Kode alamat berhasil ditambahkan.';

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        AddressCode::findOrFail($id);
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');

            return;
        }

        if ($this->deleteTargetId !== null) {
            AddressCode::findOrFail($this->deleteTargetId)->delete();
            $this->dispatch('toast', message: 'Kode alamat berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
    }

    public function restore(int $id): void
    {
        AddressCode::onlyTrashed()->findOrFail($id)->restore();
        $this->dispatch('toast', message: 'Kode alamat berhasil dipulihkan.', type: 'success');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->description = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $query = AddressCode::query()
            ->withCount(['customerAddresses as usage_count'])
            ->when($this->showTrashed, fn ($query) => $query->withTrashed());

        if ($this->search !== '') {
            $query->where(function ($inner): void {
                $inner->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        return view('livewire.sales.sales-master.customer-address-code', [
            'addressCodes' => $query
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->perPage),
        ]);
    }
}
