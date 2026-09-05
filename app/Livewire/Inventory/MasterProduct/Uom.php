<?php

namespace App\Livewire\Inventory\MasterProduct;

use App\Models\ProductUnit;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Uom extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showTrashed = false;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    protected function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('product_units', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($this->editingId),
            ],
            'name' => 'required|string|max:255',
        ];
    }

    protected array $messages = [
        'code.required' => 'Kode satuan wajib diisi.',
        'code.unique' => 'Kode satuan sudah digunakan.',
        'name.required' => 'Deskripsi satuan wajib diisi.',
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
        $unit = ProductUnit::findOrFail($id);

        $this->editingId = $unit->id;
        $this->code = $unit->code;
        $this->name = $unit->name;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            ProductUnit::findOrFail($this->editingId)->update($validated);

            $this->dispatch('toast', message: 'UOM berhasil diperbarui.', type: 'success');
        } else {
            ProductUnit::create($validated);

            $this->dispatch('toast', message: 'UOM berhasil ditambahkan.', type: 'success');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $unit = ProductUnit::findOrFail($id);

        if ($unit->trashed()) {
            return;
        }

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
            ProductUnit::findOrFail($this->deleteTargetId)->delete();

            $this->dispatch('toast', message: 'UOM berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->name = '';

        $this->resetValidation();
    }

    public function render()
    {
        $query = ProductUnit::query();

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%');
            });
        }

        $units = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.inventory.master-product.uom', [
            'units' => $units,
        ]);
    }
}
