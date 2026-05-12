<?php

namespace App\Livewire\Purchasing\Transaction;

use Livewire\Component;
use Livewire\WithPagination;

class GoodReceive extends Component
{
        use WithPagination;

    // Table state
    public string $search = '';
    public string $statusFilter = '';
    public int $perPage = 10;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Modal state
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $deleteTargetId = null;
    public bool $showTrashed = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        // $this->resetForm();
        $this->showModal = true;
    }

    public function render()
    {
        return view('livewire.purchasing.transaction.good-receive');
    }
}
