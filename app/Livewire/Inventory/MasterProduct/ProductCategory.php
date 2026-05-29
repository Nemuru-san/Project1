<?php

namespace App\Livewire\Inventory\MasterProduct;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Inventory\Product\ProductCategory as ProductCategoryModel;

class ProductCategory extends Component
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
    public string $name = '';
    public string $desc = '';
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'desc' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama category wajib diisi.',
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
        $category        = ProductCategoryModel::findOrFail($id);
        $this->editingId = $category->id;
        $this->name      = $category->name;
        $this->desc      = $category->desc ?? '';
        $this->is_active = (bool) $category->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            ProductCategoryModel::findOrFail($this->editingId)->update($validated);
            $this->dispatch('toast', message: 'Category berhasil diperbarui.', type: 'success');
        } else {
            ProductCategoryModel::create($validated);
            $this->dispatch('toast', message: 'Category berhasil ditambahkan.', type: 'success');
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
            ProductCategoryModel::findOrFail($this->deleteTargetId)->delete();
            $this->dispatch('toast', message: 'Category berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId  = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name      = '';
        $this->desc      = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $query = ProductCategoryModel::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('desc', 'like', '%' . $this->search . '%');
            });
        }

        $categories = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.inventory.master-product.product-category', [
            'categories' => $categories,
        ]);
    }
}
