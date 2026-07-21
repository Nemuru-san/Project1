<?php

namespace App\Livewire\Sales\SalesMaster;

use Livewire\Component;
use Livewire\WithPagination;

class SalesMan extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

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

    public function updatingShowTrashed(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.sales.sales-master.sales-man');
    }
}
