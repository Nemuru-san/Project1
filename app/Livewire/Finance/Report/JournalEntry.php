<?php

namespace App\Livewire\Finance\Report;

use App\Models\JournalEntry as ModelsJournalEntry;
use Livewire\Component;
use Livewire\WithPagination;

class JournalEntry extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'date';
    public string $sortDirection = 'desc';

    public string $statusFilter = '';
    public string $sourceFilter = '';
    public bool $showTrashed = false;

    public bool $showDetail = false;
    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;
    public ?ModelsJournalEntry $selectedJournal = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatedShowTrashed(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            return;
        }

        $this->sortField = $field;
        $this->sortDirection = 'asc';
    }

    public function openDetail(int $id): void
    {
        $this->selectedJournal = ModelsJournalEntry::withTrashed()
            ->with([
                'creator',
                'lines.chartOfAccount',
            ])
            ->findOrFail($id);

        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->selectedJournal = null;
    }

    public function confirmDelete(int $id): void
    {
        $journal = ModelsJournalEntry::findOrFail($id);

        if ($journal->status !== ModelsJournalEntry::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Journal yang sudah posted tidak bisa dihapus.', type: 'error');
            return;
        }

        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (!$this->deleteTargetId) {
            return;
        }

        $journal = ModelsJournalEntry::findOrFail($this->deleteTargetId);

        if ($journal->status !== ModelsJournalEntry::STATUS_DRAFT) {
            $this->showDeleteModal = false;
            $this->deleteTargetId = null;

            $this->dispatch('toast', message: 'Journal yang sudah posted tidak bisa dihapus.', type: 'error');
            return;
        }

        $journal->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'Journal berhasil dihapus.', type: 'success');
    }

    public function getSelectedJournalDebitTotalProperty(): int
    {
        if (!$this->selectedJournal) {
            return 0;
        }

        return (int) $this->selectedJournal->lines->sum('debit');
    }

    public function getSelectedJournalCreditTotalProperty(): int
    {
        if (!$this->selectedJournal) {
            return 0;
        }

        return (int) $this->selectedJournal->lines->sum('credit');
    }

    public function getSelectedJournalIsBalanceProperty(): bool
    {
        return $this->selectedJournalDebitTotal === $this->selectedJournalCreditTotal;
    }

    public function render()
    {
        $journals = ModelsJournalEntry::query()
            ->with(['creator'])
            ->withCount('lines')
            ->when($this->showTrashed, fn($query) => $query->withTrashed())
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('code', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhere('source_type', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, fn($query) => $query->where('status', $this->statusFilter))
            ->when($this->sourceFilter, fn($query) => $query->where('source_type', $this->sourceFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.finance.report.journal-entry', [
            'journals' => $journals,
            'statusOptions' => ModelsJournalEntry::statusOptions(),
            'sourceOptions' => [
                ModelsJournalEntry::SOURCE_PURCHASE_INVOICE,
                ModelsJournalEntry::SOURCE_AP_PAYMENT,
                ModelsJournalEntry::SOURCE_MANUAL_JOURNAL,
            ],
        ]);
    }
}
