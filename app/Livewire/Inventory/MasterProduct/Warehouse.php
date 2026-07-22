<?php

namespace App\Livewire\Inventory\MasterProduct;

use App\Models\Warehouse as WarehouseModel;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Warehouse extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';

    public string $statusFilter = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showTrashed = false;

    // Modal state
    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    // Form state
    public ?int $editingId = null;

    public string $name = '';

    public string $desc = '';

    public string $address = '';

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($this->editingId),
            ],
            'desc' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama warehouse wajib diisi.',
        'name.unique' => 'Nama warehouse sudah digunakan.',
        'name.max' => 'Nama warehouse maksimal 255 karakter.',
        'desc.max' => 'Deskripsi maksimal 255 karakter.',
        'address.max' => 'Alamat maksimal 255 karakter.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        $allowedFields = ['name', 'desc', 'address', 'created_at'];

        if (! in_array($field, $allowedFields, true)) {
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
        $warehouse = WarehouseModel::findOrFail($id);

        $this->editingId = $warehouse->id;
        $this->name = $warehouse->name;
        $this->desc = $warehouse->desc ?? '';
        $this->address = $warehouse->address ?? '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            WarehouseModel::findOrFail($this->editingId)->update($validated);

            $this->dispatch('toast', message: 'Gudang berhasil diperbarui.', type: 'success');
        } else {
            WarehouseModel::create($validated);

            $this->dispatch('toast', message: 'Gudang berhasil ditambahkan.', type: 'success');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');

            return;
        }

        if ($this->deleteTargetId) {
            WarehouseModel::findOrFail($this->deleteTargetId)->delete();

            $this->dispatch('toast', message: 'Gudang berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->desc = '';
        $this->address = '';

        $this->resetValidation();
    }

    public function render()
    {
        $query = WarehouseModel::query();

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->statusFilter === 'active') {
            $query->whereNull('deleted_at');
        }

        if ($this->statusFilter === 'deleted') {
            $query->onlyTrashed();
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('desc', 'like', '%'.$this->search.'%')
                    ->orWhere('address', 'like', '%'.$this->search.'%');
            });
        }

        $warehouses = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.inventory.master-product.warehouse', [
            'warehouses' => $warehouses,
        ]);
    }
}
