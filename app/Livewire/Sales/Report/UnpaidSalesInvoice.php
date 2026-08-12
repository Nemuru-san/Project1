<?php

namespace App\Livewire\Sales\Report;

use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class UnpaidSalesInvoice extends Component
{
    use WithPagination;

    public string $search = '';

    public string $customerFilter = '';

    public string $paymentStatusFilter = '';

    public string $dueFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public string $sortField = 'due_date';

    public string $sortDirection = 'asc';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'customerFilter', 'paymentStatusFilter', 'dueFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['invoice_no', 'invoice_date', 'due_date', 'grand_total', 'amount_due'], true)) {
            return;
        }
        $this->sortDirection = $this->sortField === $field ? ($this->sortDirection === 'asc' ? 'desc' : 'asc') : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'customerFilter', 'paymentStatusFilter', 'dueFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function query(): Builder
    {
        return SalesInvoice::query()
            ->with(['customer', 'salesOrder'])
            ->where('status', SalesInvoice::STATUS_CONFIRMED)
            ->where('amount_due', '>', 0)
            ->when($this->search, function (Builder $query) {
                $search = '%'.$this->search.'%';
                $query->where(fn (Builder $query) => $query->where('invoice_no', 'like', $search)
                    ->orWhereHas('salesOrder', fn (Builder $order) => $order->where('order_no', 'like', $search))
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $search)));
            })
            ->when($this->customerFilter, fn (Builder $query) => $query->where('customer_id', $this->customerFilter))
            ->when($this->paymentStatusFilter === 'unpaid', fn (Builder $query) => $query->where('paid_amount', 0))
            ->when($this->paymentStatusFilter === 'partial', fn (Builder $query) => $query->where('paid_amount', '>', 0))
            ->when($this->dueFilter === 'overdue', fn (Builder $query) => $query->whereNotNull('due_date')->whereDate('due_date', '<', today()))
            ->when($this->dueFilter === 'not_due', fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('due_date')->orWhereDate('due_date', '>=', today())))
            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('invoice_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('invoice_date', '<=', $this->dateTo));
    }

    public function render()
    {
        $summaryRows = (clone $this->query())->get();
        $query = $this->query();
        if ($this->sortField === 'due_date') {
            $query->orderByRaw('due_date IS NULL');
        }
        $invoices = $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);
        $invoices->setCollection($invoices->getCollection()->map(function (SalesInvoice $invoice) {
            $invoice->setAttribute('age_days', max(0, $invoice->invoice_date?->startOfDay()->diffInDays(today(), false) ?? 0));
            $invoice->setAttribute('overdue_days', $invoice->due_date && $invoice->due_date->isBefore(today()) ? $invoice->due_date->startOfDay()->diffInDays(today()) : 0);
            $invoice->setAttribute('payment_label', $invoice->paid_amount > 0 ? 'Dibayar Sebagian' : 'Belum Dibayar');

            return $invoice;
        }));

        return view('livewire.sales.report.unpaid-sales-invoice', [
            'invoices' => $invoices,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name']),
            'summary' => [
                'count' => $summaryRows->count(),
                'total' => (int) $summaryRows->sum('grand_total'),
                'paid' => (int) $summaryRows->sum('paid_amount'),
                'outstanding' => (int) $summaryRows->sum('amount_due'),
                'overdue' => (int) $summaryRows->filter(fn ($invoice) => $invoice->due_date && $invoice->due_date->isBefore(today()))->sum('amount_due'),
            ],
        ]);
    }
}
