<?php

namespace App\Livewire\Inventory\MasterProduct;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Inventory\Product\ProductUnit;

class Uom extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Modal state
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $deleteTargetId = null;

    // Form fields
    public ?int $editingId = null;
    public string $code = '';
    public string $name = '';
    public bool $is_active = true;

    protected function rules(): array
    {
        $uniqueCode = 'required|string|max:50|unique:product_units,code';
        if ($this->editingId) {
            $uniqueCode .= ',' . $this->editingId;
        }

        return [
            'code'      => $uniqueCode,
            'name'      => 'required|string|max:255',
            'is_active' => 'boolean',
        ];
    }

    protected array $messages = [
        'code.required' => 'UOM code wajib diisi.',
        'code.unique'   => 'UOM code sudah digunakan.',
        'name.required' => 'UOM description wajib diisi.',
    ];

    public function updatingSearch(): void
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
        $unit            = ProductUnit::findOrFail($id);
        $this->editingId = $unit->id;
        $this->code      = $unit->code;
        $this->name      = $unit->name;
        $this->is_active = (bool) $unit->is_active;
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
        $this->deleteTargetId  = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deleteTargetId) {
            ProductUnit::findOrFail($this->deleteTargetId)->delete();
            $this->dispatch('toast', message: 'UOM berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId  = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code      = '';
        $this->name      = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $query = ProductUnit::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                    ->orWhere('name', 'like', '%' . $this->search . '%');
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
