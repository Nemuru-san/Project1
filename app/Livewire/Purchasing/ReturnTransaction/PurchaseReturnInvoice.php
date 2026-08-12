<?php

namespace App\Livewire\Purchasing\ReturnTransaction;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnInvoice as ReturnInvoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseReturnInvoice extends Component
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

    public ?int $purchaseReturnId = null;

    public ?int $purchaseInvoiceId = null;

    public string $supplierCreditNo = '';

    public string $notes = '';

    public int $subtotal = 0;

    public int $taxAmount = 0;

    public int $grandTotal = 0;

    protected function rules(): array
    {
        return [
            'invoiceDate' => ['required', 'date', 'before_or_equal:today'],
            'purchaseReturnId' => ['required', 'integer', 'exists:purchase_returns,id'],
            'purchaseInvoiceId' => ['required', 'integer', 'exists:purchase_invoices,id'],
            'supplierCreditNo' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(): void
    {
        $this->invoiceDate = now()->toDateString();
        if ($id = request()->integer('invoice')) {
            $this->openDetail($id);
        }
        if ($returnId = request()->integer('return')) {
            $this->openCreate();
            $this->purchaseReturnId = $returnId;
            $this->updatedPurchaseReturnId();
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

    public function updatedPurchaseReturnId(): void
    {
        $this->purchaseInvoiceId = null;
        $this->subtotal = $this->taxAmount = $this->grandTotal = 0;
        if (! $this->purchaseReturnId) {
            return;
        }

        $return = PurchaseReturn::with('items')->where('status', PurchaseReturn::STATUS_CONFIRMED)->whereDoesntHave('returnInvoice')->find($this->purchaseReturnId);
        if (! $return) {
            $this->purchaseReturnId = null;

            return;
        }

        $this->subtotal = (int) $return->items->sum('subtotal');
        $invoice = PurchaseInvoice::where('purchase_order_id', $return->purchase_order_id)->where('status', PurchaseInvoice::STATUS_POSTED)->first();
        if ($invoice) {
            $this->purchaseInvoiceId = $invoice->id;
            $this->taxAmount = $invoice->tax ? (int) round($this->subtotal * 0.11) : 0;
            $this->grandTotal = $this->subtotal + $this->taxAmount;
        }
    }

    public function save(): void
    {
        $this->authorizeModule();
        $this->validate();

        $returnInvoice = DB::transaction(function () {
            $return = PurchaseReturn::with('items')->lockForUpdate()->where('status', PurchaseReturn::STATUS_CONFIRMED)->findOrFail($this->purchaseReturnId);
            if ($return->returnInvoice()->exists()) {
                throw ValidationException::withMessages(['purchaseReturnId' => 'Retur Pembelian ini sudah memiliki Faktur Retur.']);
            }
            $invoice = PurchaseInvoice::lockForUpdate()->where('status', PurchaseInvoice::STATUS_POSTED)->findOrFail($this->purchaseInvoiceId);
            if ($invoice->purchase_order_id !== $return->purchase_order_id || $invoice->supplier_id !== $return->supplier_id) {
                throw ValidationException::withMessages(['purchaseInvoiceId' => 'Faktur Pembelian tidak sesuai dengan Retur Pembelian.']);
            }

            $subtotal = (int) $return->items->sum('subtotal');
            $tax = $invoice->tax ? (int) round($subtotal * 0.11) : 0;
            $total = $subtotal + $tax;
            if ($total <= 0 || $total > (int) $invoice->remaining_amount) {
                throw ValidationException::withMessages(['purchaseInvoiceId' => 'Nilai Faktur Retur melebihi sisa utang Faktur Pembelian.']);
            }

            return ReturnInvoice::create([
                'credit_note_no' => $this->generateCode(),
                'supplier_credit_no' => trim($this->supplierCreditNo) ?: null,
                'invoice_date' => $this->invoiceDate,
                'purchase_return_id' => $return->id,
                'purchase_invoice_id' => $invoice->id,
                'supplier_id' => $return->supplier_id,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'grand_total' => $total,
                'status' => ReturnInvoice::STATUS_DRAFT,
                'notes' => trim($this->notes) ?: null,
                'created_by' => Auth::id(),
            ]);
        });

        $this->resetForm();
        $this->dispatch('toast', message: "Faktur Retur {$returnInvoice->credit_note_no} disimpan sebagai Draf.", type: 'success');
    }

    public function confirmPost(int $id): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.return.purchase-return-invoice.post'), 403);
        $invoice = ReturnInvoice::findOrFail($id);
        if ($invoice->status !== ReturnInvoice::STATUS_DRAFT) {
            return;
        }
        $this->postTargetId = $id;
        $this->showPostModal = true;
    }

    public function post(): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.return.purchase-return-invoice.post'), 403);
        DB::transaction(function () {
            $returnInvoice = ReturnInvoice::with(['supplier', 'purchaseInvoice.purchaseOrder'])->lockForUpdate()->findOrFail($this->postTargetId);
            if ($returnInvoice->status !== ReturnInvoice::STATUS_DRAFT) {
                throw ValidationException::withMessages(['invoice' => 'Hanya Faktur Retur Draf yang dapat diposting.']);
            }
            $purchaseInvoice = PurchaseInvoice::lockForUpdate()->findOrFail($returnInvoice->purchase_invoice_id);
            if ((int) $returnInvoice->grand_total > (int) $purchaseInvoice->remaining_amount) {
                throw ValidationException::withMessages(['invoice' => 'Sisa utang Faktur Pembelian tidak mencukupi untuk kredit retur ini.']);
            }

            $remaining = (int) $purchaseInvoice->remaining_amount - (int) $returnInvoice->grand_total;
            $purchaseInvoice->update([
                'remaining_amount' => $remaining,
                'payment_status' => $remaining === 0 ? PurchaseInvoice::PAYMENT_PAID : ((int) $purchaseInvoice->paid_amount > 0 ? PurchaseInvoice::PAYMENT_PARTIAL_PAID : PurchaseInvoice::PAYMENT_UNPAID),
            ]);
            $returnInvoice->update(['status' => ReturnInvoice::STATUS_POSTED, 'posted_at' => now(), 'posted_by' => Auth::id()]);
            $this->createJournal($returnInvoice);
        });

        $this->showPostModal = false;
        $this->postTargetId = null;
        $this->dispatch('toast', message: 'Faktur Retur diposting, sisa utang dikurangi, dan jurnal dibuat.', type: 'success');
    }

    private function createJournal(ReturnInvoice $invoice): void
    {
        if (JournalEntry::where('source_type', JournalEntry::SOURCE_PURCHASE_RETURN_INVOICE)->where('source_id', $invoice->id)->exists()) {
            return;
        }
        $account = fn (string $code) => ChartOfAccount::where('code', $code)->where('is_active', true)->where('is_postable', true)->firstOrFail()->id;
        $journal = JournalEntry::create([
            'code' => $this->generateJournalCode(),
            'date' => $invoice->invoice_date,
            'source_type' => JournalEntry::SOURCE_PURCHASE_RETURN_INVOICE,
            'source_id' => $invoice->id,
            'description' => 'Faktur Retur Pembelian '.$invoice->credit_note_no,
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => Auth::id(),
        ]);
        $journal->lines()->create(['chart_of_account_id' => $account('2100'), 'debit' => $invoice->grand_total, 'credit' => 0, 'description' => 'Pengurangan utang supplier']);
        $journal->lines()->create(['chart_of_account_id' => $account('1200'), 'debit' => 0, 'credit' => $invoice->subtotal, 'description' => 'Persediaan diretur ke supplier']);
        if ($invoice->tax_amount > 0) {
            $journal->lines()->create(['chart_of_account_id' => $account('1400'), 'debit' => 0, 'credit' => $invoice->tax_amount, 'description' => 'Pembalik Pajak Masukan']);
        }
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $invoice = ReturnInvoice::findOrFail($id);
        abort_unless($invoice->status === ReturnInvoice::STATUS_DRAFT, 422);
        $invoice->delete();
        $this->dispatch('toast', message: 'Faktur Retur Draf dihapus.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $this->selectedInvoice = ReturnInvoice::withTrashed()->with(['supplier', 'purchaseInvoice', 'purchaseReturn.items.product', 'purchaseReturn.items.unit'])->findOrFail($id);
        $this->showDetail = true;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    private function generateCode(): string
    {
        $prefix = 'PRI/'.now()->format('ym').'/';
        $last = ReturnInvoice::withTrashed()->where('credit_note_no', 'like', $prefix.'%')->orderByDesc('id')->value('credit_note_no');

        return $prefix.str_pad($last ? (int) str($last)->afterLast('/') + 1 : 1, 4, '0', STR_PAD_LEFT);
    }

    private function generateJournalCode(): string
    {
        $prefix = 'JE/'.now()->format('ym').'/';
        $last = JournalEntry::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('id')->value('code');

        return $prefix.str_pad($last ? (int) str($last)->afterLast('/') + 1 : 1, 4, '0', STR_PAD_LEFT);
    }

    private function authorizeModule(): void
    {
        abort_unless(auth()->user()?->hasPermission('purchases.return.purchase-return-invoice'), 403);
    }

    private function resetForm(): void
    {
        $this->reset(['showModal', 'purchaseReturnId', 'purchaseInvoiceId', 'supplierCreditNo', 'notes', 'subtotal', 'taxAmount', 'grandTotal']);
        $this->invoiceDate = now()->toDateString();
        $this->resetErrorBag();
    }

    public function render()
    {
        $invoices = ReturnInvoice::with(['supplier', 'purchaseReturn', 'purchaseInvoice'])
            ->when($this->search, fn ($query) => $query->where(fn ($query) => $query->where('credit_note_no', 'like', '%'.$this->search.'%')->orWhere('supplier_credit_no', 'like', '%'.$this->search.'%')->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', '%'.$this->search.'%'))))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('invoice_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('invoice_date', '<=', $this->dateTo))
            ->latest('invoice_date')->latest('id')->paginate($this->perPage);

        return view('livewire.purchasing.return-transaction.purchase-return-invoice', [
            'invoices' => $invoices,
            'purchaseReturns' => PurchaseReturn::with(['supplier', 'purchaseOrder'])->where('status', PurchaseReturn::STATUS_CONFIRMED)->whereDoesntHave('returnInvoice')->latest('return_date')->get(),
            'purchaseInvoices' => $this->purchaseReturnId ? PurchaseInvoice::where('purchase_order_id', PurchaseReturn::find($this->purchaseReturnId)?->purchase_order_id)->where('status', PurchaseInvoice::STATUS_POSTED)->get() : collect(),
        ]);
    }
}
