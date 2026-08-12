<?php

namespace App\Livewire\Sales\ReturnTransaction;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SalesReturnInvoice as ReturnInvoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class SalesReturnInvoice extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public bool $showModal = false;

    public bool $showDetail = false;

    public bool $showPostModal = false;

    public ?int $postTargetId = null;

    public ?ReturnInvoice $selectedInvoice = null;

    public string $invoiceDate = '';

    public ?int $salesReturnId = null;

    public ?int $salesInvoiceId = null;

    public string $customerReferenceNo = '';

    public string $notes = '';

    public int $subtotal = 0;

    public int $taxAmount = 0;

    public int $grandTotal = 0;

    protected function rules(): array
    {
        return ['invoiceDate' => ['required', 'date', 'before_or_equal:today'], 'salesReturnId' => ['required', 'integer', 'exists:sales_returns,id'], 'salesInvoiceId' => ['required', 'integer', 'exists:sales_invoices,id'], 'customerReferenceNo' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:1000']];
    }

    public function mount(): void
    {
        $this->invoiceDate = now()->toDateString();
        if ($id = request()->integer('invoice')) {
            $this->openDetail($id);
        }
        if ($returnId = request()->integer('return')) {
            $this->openCreate();
            $this->salesReturnId = $returnId;
            $this->updatedSalesReturnId();
        }
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function openCreate(): void
    {
        $this->authorizeModule();
        $this->resetForm();
        $this->showModal = true;
    }

    public function updatedSalesReturnId(): void
    {
        $this->salesInvoiceId = null;
        $this->subtotal = $this->taxAmount = $this->grandTotal = 0;
        $return = SalesReturn::with('items')->where('status', SalesReturn::STATUS_CONFIRMED)->whereDoesntHave('returnInvoice')->find($this->salesReturnId);
        if (! $return) {
            $this->salesReturnId = null;

            return;
        }
        $this->subtotal = (int) $return->items->sum('subtotal');
        $invoice = SalesInvoice::where('sales_order_id', $return->sales_order_id)->where('status', SalesInvoice::STATUS_CONFIRMED)->first();
        if ($invoice) {
            $this->salesInvoiceId = $invoice->id;
            $this->taxAmount = $invoice->tax_amount > 0 ? (int) round($this->subtotal * 0.11) : 0;
            $this->grandTotal = $this->subtotal + $this->taxAmount;
        }
    }

    public function save(): void
    {
        $this->authorizeModule();
        $this->validate();
        $invoice = DB::transaction(function () {
            $return = SalesReturn::with('items')->lockForUpdate()->where('status', SalesReturn::STATUS_CONFIRMED)->findOrFail($this->salesReturnId);
            if ($return->returnInvoice()->exists()) {
                throw ValidationException::withMessages(['salesReturnId' => 'Retur Penjualan ini sudah memiliki Faktur Retur.']);
            }
            $salesInvoice = SalesInvoice::lockForUpdate()->where('status', SalesInvoice::STATUS_CONFIRMED)->findOrFail($this->salesInvoiceId);
            if ($salesInvoice->sales_order_id !== $return->sales_order_id || $salesInvoice->customer_id !== $return->customer_id) {
                throw ValidationException::withMessages(['salesInvoiceId' => 'Faktur Penjualan tidak sesuai dengan Retur Penjualan.']);
            }
            $subtotal = (int) $return->items->sum('subtotal');
            $tax = $salesInvoice->tax_amount > 0 ? (int) round($subtotal * 0.11) : 0;
            $total = $subtotal + $tax;
            if ($total <= 0 || $total > (int) $salesInvoice->amount_due) {
                throw ValidationException::withMessages(['salesInvoiceId' => 'Nilai Faktur Retur melebihi sisa piutang Faktur Penjualan.']);
            }

            return ReturnInvoice::create(['credit_note_no' => $this->generateCode(), 'customer_reference_no' => trim($this->customerReferenceNo) ?: null, 'invoice_date' => $this->invoiceDate, 'sales_return_id' => $return->id, 'sales_invoice_id' => $salesInvoice->id, 'customer_id' => $return->customer_id, 'subtotal' => $subtotal, 'tax_amount' => $tax, 'grand_total' => $total, 'status' => ReturnInvoice::STATUS_DRAFT, 'notes' => trim($this->notes) ?: null, 'created_by' => Auth::id()]);
        });
        $this->resetForm();
        $this->dispatch('toast', message: "Faktur Retur {$invoice->credit_note_no} disimpan sebagai Draf.", type: 'success');
    }

    public function confirmPost(int $id): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.return.sales-return-invoice.post'), 403);
        if (ReturnInvoice::whereKey($id)->where('status', ReturnInvoice::STATUS_DRAFT)->exists()) {
            $this->postTargetId = $id;
            $this->showPostModal = true;
        }
    }

    public function post(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.return.sales-return-invoice.post'), 403);
        DB::transaction(function () {
            $returnInvoice = ReturnInvoice::with('customer')->lockForUpdate()->findOrFail($this->postTargetId);
            if ($returnInvoice->status !== ReturnInvoice::STATUS_DRAFT) {
                throw ValidationException::withMessages(['invoice' => 'Hanya Faktur Retur Draf yang dapat diposting.']);
            }
            $salesInvoice = SalesInvoice::lockForUpdate()->findOrFail($returnInvoice->sales_invoice_id);
            if ($returnInvoice->grand_total > $salesInvoice->amount_due) {
                throw ValidationException::withMessages(['invoice' => 'Sisa piutang tidak mencukupi untuk nota kredit ini.']);
            }
            $salesInvoice->decrement('amount_due', $returnInvoice->grand_total);
            $returnInvoice->update(['status' => ReturnInvoice::STATUS_POSTED, 'posted_at' => now(), 'posted_by' => Auth::id()]);
            $this->createJournal($returnInvoice);
        });
        $this->showPostModal = false;
        $this->postTargetId = null;
        $this->dispatch('toast', message: 'Faktur Retur diposting, sisa piutang dikurangi, dan jurnal dibuat.', type: 'success');
    }

    private function createJournal(ReturnInvoice $invoice): void
    {
        if (JournalEntry::where('source_type', JournalEntry::SOURCE_SALES_RETURN_INVOICE)->where('source_id', $invoice->id)->exists()) {
            return;
        }
        $account = fn (string $code) => ChartOfAccount::where('code', $code)->where('is_active', true)->where('is_postable', true)->firstOrFail()->id;
        $journal = JournalEntry::create(['code' => $this->generateJournalCode(), 'date' => $invoice->invoice_date, 'source_type' => JournalEntry::SOURCE_SALES_RETURN_INVOICE, 'source_id' => $invoice->id, 'description' => 'Faktur Retur Penjualan '.$invoice->credit_note_no, 'status' => JournalEntry::STATUS_POSTED, 'created_by' => Auth::id()]);
        $journal->lines()->create(['chart_of_account_id' => $account('4100'), 'debit' => $invoice->subtotal, 'credit' => 0, 'description' => 'Pembalik pendapatan penjualan']);
        if ($invoice->tax_amount > 0) {
            $journal->lines()->create(['chart_of_account_id' => $account('2200'), 'debit' => $invoice->tax_amount, 'credit' => 0, 'description' => 'Pembalik PPN keluaran']);
        }
        $journal->lines()->create(['chart_of_account_id' => $account('1300'), 'debit' => 0, 'credit' => $invoice->grand_total, 'description' => 'Pengurangan piutang pelanggan']);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $invoice = ReturnInvoice::findOrFail($id);
        abort_unless($invoice->status === ReturnInvoice::STATUS_DRAFT, 422);
        $invoice->delete();
    }

    public function openDetail(int $id): void
    {
        $this->selectedInvoice = ReturnInvoice::withTrashed()->with(['customer', 'salesInvoice', 'salesReturn.items.product', 'salesReturn.items.unit'])->findOrFail($id);
        $this->showDetail = true;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function authorizeModule(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.return.sales-return-invoice'), 403);
    }

    private function generateCode(): string
    {
        $prefix = 'SRI/'.now()->format('ym').'/';
        $last = ReturnInvoice::withTrashed()->where('credit_note_no', 'like', $prefix.'%')->orderByDesc('id')->value('credit_note_no');

        return $prefix.str_pad($last ? (int) str($last)->afterLast('/') + 1 : 1, 4, '0', STR_PAD_LEFT);
    }

    private function generateJournalCode(): string
    {
        $prefix = 'JE/'.now()->format('ym').'/';
        $last = JournalEntry::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('id')->value('code');

        return $prefix.str_pad($last ? (int) str($last)->afterLast('/') + 1 : 1, 4, '0', STR_PAD_LEFT);
    }

    private function resetForm(): void
    {
        $this->reset(['showModal', 'salesReturnId', 'salesInvoiceId', 'customerReferenceNo', 'notes', 'subtotal', 'taxAmount', 'grandTotal']);
        $this->invoiceDate = now()->toDateString();
        $this->resetErrorBag();
    }

    public function render()
    {
        $invoices = ReturnInvoice::with(['customer', 'salesReturn', 'salesInvoice'])->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('credit_note_no', 'like', '%'.$this->search.'%')->orWhere('customer_reference_no', 'like', '%'.$this->search.'%')->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', '%'.$this->search.'%'))))->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))->when($this->dateFrom, fn ($query) => $query->whereDate('invoice_date', '>=', $this->dateFrom))->when($this->dateTo, fn ($query) => $query->whereDate('invoice_date', '<=', $this->dateTo))->latest('invoice_date')->latest('id')->paginate($this->perPage);
        $return = $this->salesReturnId ? SalesReturn::find($this->salesReturnId) : null;

        return view('livewire.sales.return-transaction.sales-return-invoice', ['invoices' => $invoices, 'salesReturns' => SalesReturn::with(['customer', 'salesOrder'])->where('status', SalesReturn::STATUS_CONFIRMED)->whereDoesntHave('returnInvoice')->latest('return_date')->get(), 'salesInvoices' => $return ? SalesInvoice::where('sales_order_id', $return->sales_order_id)->where('status', SalesInvoice::STATUS_CONFIRMED)->get() : collect()]);
    }
}
