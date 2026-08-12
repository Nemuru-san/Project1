<?php

namespace App\Livewire\Purchasing\Report;

use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class UnfinishedPurchaseInvoice extends Component
{
    use WithPagination;

    public string $search = '';

    public string $supplierFilter = '';

    public string $paymentStatusFilter = '';

    public string $dueFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public string $sortField = 'due_date';

    public string $sortDirection = 'asc';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'supplierFilter', 'paymentStatusFilter', 'dueFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['code', 'date', 'due_date', 'grand_total', 'remaining_amount'], true)) {
            return;
        }
        $this->sortDirection = $this->sortField === $field ? ($this->sortDirection === 'asc' ? 'desc' : 'asc') : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'supplierFilter', 'paymentStatusFilter', 'dueFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function query(): Builder
    {
        return PurchaseInvoice::query()
            ->with(['supplier', 'purchaseOrder'])
            ->where('status', PurchaseInvoice::STATUS_POSTED)
            ->where('remaining_amount', '>', 0)
            ->when($this->search, function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(fn ($query) => $query->where('code', 'like', $search)
                    ->orWhere('supplier_invoice_number', 'like', $search)
                    ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', $search))
                    ->orWhereHas('purchaseOrder', fn ($query) => $query->where('code', 'like', $search)));
            })
            ->when($this->supplierFilter, fn ($query) => $query->where('supplier_id', $this->supplierFilter))
            ->when($this->paymentStatusFilter, fn ($query) => $query->where('payment_status', $this->paymentStatusFilter))
            ->when($this->dueFilter === 'overdue', fn ($query) => $query->whereNotNull('due_date')->whereDate('due_date', '<', today()))
            ->when($this->dueFilter === 'not_due', fn ($query) => $query->where(fn ($query) => $query->whereNull('due_date')->orWhereDate('due_date', '>=', today())))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('date', '<=', $this->dateTo));
    }

    public function render()
    {
        $summaryRows = (clone $this->query())->get();
        $query = $this->query();

        if ($this->sortField === 'due_date') {
            $query->orderByRaw('due_date IS NULL');
        }

        $invoices = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);
        $invoices->setCollection($invoices->getCollection()->map(function (PurchaseInvoice $invoice) {
            $invoice->setAttribute('age_days', max(0, $invoice->date?->startOfDay()->diffInDays(today(), false) ?? 0));
            $invoice->setAttribute('overdue_days', $invoice->due_date && $invoice->due_date->isBefore(today()) ? $invoice->due_date->startOfDay()->diffInDays(today()) : 0);
            $invoice->setAttribute('payment_label', (int) $invoice->paid_amount > 0 ? 'Dibayar Sebagian' : 'Belum Dibayar');

            return $invoice;
        }));

        return view('livewire.purchasing.report.unfinished-purchase-invoice', [
            'invoices' => $invoices,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'summary' => [
                'count' => $summaryRows->count(),
                'total' => (int) $summaryRows->sum('grand_total'),
                'paid' => (int) $summaryRows->sum('paid_amount'),
                'outstanding' => (int) $summaryRows->sum('remaining_amount'),
                'overdue' => (int) $summaryRows->filter(fn ($invoice) => $invoice->due_date && $invoice->due_date->isBefore(today()))->sum('remaining_amount'),
            ],
        ]);
    }
}
