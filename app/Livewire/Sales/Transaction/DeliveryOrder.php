<?php

namespace App\Livewire\Sales\Transaction;

use Livewire\Component;
use Livewire\WithPagination;

class DeliveryOrder extends Component
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
        return view('livewire.sales.transaction.delivery-order');
    }
}
