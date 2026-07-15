<?php

namespace App\Livewire\Inventory\MasterProduct;

use App\Models\ProductCategory as ProductCategoryModel;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ProductCategory extends Component
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

    public string $desc = '';

    protected function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9][A-Z0-9._-]*$/',
                Rule::unique('product_categories', 'code')->ignore($this->editingId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($this->editingId),
            ],
            'desc' => 'nullable|string|max:500',
        ];
    }

    protected array $messages = [
        'code.required' => 'Kode category wajib diisi.',
        'code.regex' => 'Kode hanya boleh berisi huruf kapital, angka, titik, strip, dan underscore.',
        'code.unique' => 'Kode category sudah digunakan.',
        'name.required' => 'Nama category wajib diisi.',
        'name.unique' => 'Nama category sudah digunakan.',
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
        $category = ProductCategoryModel::findOrFail($id);

        $this->editingId = $category->id;
        $this->code = $category->code;
        $this->name = $category->name;
        $this->desc = $category->desc ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->code = mb_strtoupper(trim($this->code));
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
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deleteTargetId) {
            ProductCategoryModel::findOrFail($this->deleteTargetId)->delete();

            $this->dispatch('toast', message: 'Category berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->name = '';
        $this->desc = '';

        $this->resetValidation();
    }

    public function render()
    {
        $query = ProductCategoryModel::query();

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%')
                    ->orWhere('desc', 'like', '%'.$this->search.'%');
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
