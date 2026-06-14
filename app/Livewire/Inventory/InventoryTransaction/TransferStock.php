<?php

namespace App\Livewire\Inventory\InventoryTransaction;

use Livewire\Component;
use Livewire\WithPagination;

class TransferStock extends Component

{
        use WithPagination;

        // Table state
        public string $search = '';
        public int $perPage = 10;
        public string $sortField = 'code';
        public string $sortDirection = 'asc';
        public bool $showTrashed = false;

        // Modal state
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
        // $this->resetForm();
        $this->showModal = true;
    }
    public function render()
    {
        return view('livewire.inventory.inventory-transaction.transfer--stock');
    }
}
