<?php

namespace App\Livewire\Sales\Transaction;

use Livewire\Component;
use Livewire\WithPagination;

class SalesCanvas extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    public bool $tax = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        // $this->resetForm();
        $this->showModal = true;
    }

    public function render()
    {
        return view('livewire.sales.transaction.sales-canvas', [
            'warehouses' => \App\Models\Warehouse::all(),
        ]);
    }
}
