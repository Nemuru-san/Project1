<?php

namespace App\Livewire\Inventory;

use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class ProductMaster extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $perPage = 10;
    public $showTrashed = false;
    public $sortField = 'prd_code';
    public $sortDirection = 'asc';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatingShowTrashed()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openCreate()
    {
        // Redirect to create page or open modal
        return redirect()->route('inventory.product.add');
    }

    public function openEdit($id)
    {
        // Redirect to edit page or open modal
        return redirect()->route('inventory.product.edit', $id);
    }

    public function confirmDelete($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        session()->flash('message', 'Product berhasil dihapus.');
        session()->flash('type', 'success');
    }

    public function restore($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        session()->flash('message', 'Product berhasil dipulihkan.');
        session()->flash('type', 'success');
    }

    public function forceDelete($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->forceDelete();

        session()->flash('message', 'Product berhasil dihapus permanen.');
        session()->flash('type', 'success');
    }

    public function render()
    {
        $query = Product::query();

        // Show trashed
        if ($this->showTrashed) {
            $query->withTrashed();
        }

        // Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('prd_code', 'like', '%' . $this->search . '%')
                    ->orWhere('prd_name', 'like', '%' . $this->search . '%')
                    ->orWhere('prd_category', 'like', '%' . $this->search . '%')
                    ->orWhere('prd_brand', 'like', '%' . $this->search . '%');
            });
        }

        // Status filter
        if ($this->statusFilter !== '') {
            $query->where('status', $this->statusFilter);
        }

        // Sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $products = $query->paginate($this->perPage);

        return view('livewire.inventory.product-master', [
            'products' => $products,
        ]);
    }
}
