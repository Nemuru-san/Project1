<?php

namespace App\Livewire\Finance\Transaction;

use App\Models\ArPayment as ArPaymentModel;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ArPayment extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public bool $showModal = false;

    public bool $showPostModal = false;

    public ?int $postTargetId = null;

    public string $code = '';

    public string $paymentDate = '';

    public ?int $salesInvoiceId = null;

    public ?int $bankAccountId = null;

    public int $amount = 0;

    public string $paymentMethod = 'Transfer';

    public string $notes = '';

    protected function rules(): array
    {
        return [
            'paymentDate' => ['required', 'date'],
            'salesInvoiceId' => ['required', 'integer', Rule::exists('sales_invoices', 'id')->where('status', SalesInvoice::STATUS_CONFIRMED)->whereNull('deleted_at')],
            'bankAccountId' => ['required', 'integer', Rule::exists('bank_accounts', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'amount' => ['required', 'integer', 'min:1'],
            'paymentMethod' => ['required', Rule::in(['Transfer', 'Tunai', 'Giro'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mount(): void
    {
        $this->paymentDate = now()->toDateString();

        $invoiceId = request()->integer('invoice');
        if ($invoiceId && auth()->user()?->hasPermission('finance.transaction.ar-payment')) {
            $invoice = SalesInvoice::where('status', SalesInvoice::STATUS_CONFIRMED)
                ->where('amount_due', '>', 0)->find($invoiceId);
            if ($invoice) {
                $this->code = $this->generateCode();
                $this->salesInvoiceId = $invoice->id;
                $this->amount = $invoice->amount_due;
                $this->showModal = true;
            }
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()?->hasPermission('finance.transaction.ar-payment'), 403);
        $this->resetForm();
        $this->code = $this->generateCode();
        $this->showModal = true;
    }

    public function updatedSalesInvoiceId(mixed $value): void
    {
        $this->amount = $value ? (int) SalesInvoice::whereKey($value)
            ->where('status', SalesInvoice::STATUS_CONFIRMED)->value('amount_due') : 0;
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->hasPermission('finance.transaction.ar-payment'), 403);
        $this->validate();
        $invoice = SalesInvoice::with('salesOrder')->where('status', SalesInvoice::STATUS_CONFIRMED)->findOrFail($this->salesInvoiceId);
        if ($this->amount > $invoice->amount_due) {
            $this->addError('amount', 'Nominal melebihi sisa tagihan Faktur Penjualan.');

            return;
        }

        ArPaymentModel::create([
            'code' => $this->generateCode(), 'payment_date' => $this->paymentDate,
            'sales_order_id' => $invoice->sales_order_id, 'sales_invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'bank_account_id' => $this->bankAccountId, 'amount' => $this->amount,
            'payment_method' => $this->paymentMethod, 'status' => ArPaymentModel::STATUS_DRAFT,
            'notes' => trim($this->notes) ?: null, 'created_by' => Auth::id(),
        ]);
        $this->resetForm();
        $this->dispatch('toast', message: 'Pembayaran Piutang berhasil disimpan sebagai draf.', type: 'success');
    }

    public function confirmPost(int $id): void
    {
        $payment = ArPaymentModel::findOrFail($id);
        abort_unless($payment->status === ArPaymentModel::STATUS_DRAFT, 403);
        $this->postTargetId = $id;
        $this->showPostModal = true;
    }

    public function post(): void
    {
        if (! $this->postTargetId) {
            return;
        }
        try {
            DB::transaction(function () {
                $payment = ArPaymentModel::with('bankAccount')->lockForUpdate()->findOrFail($this->postTargetId);
                if (! $payment->sales_invoice_id) {
                    throw new \RuntimeException('Pembayaran lama belum terhubung ke Faktur Penjualan. Buat pembayaran baru dari faktur yang sudah dikonfirmasi.');
                }
                $invoice = SalesInvoice::lockForUpdate()->findOrFail($payment->sales_invoice_id);
                $order = SalesOrder::lockForUpdate()->findOrFail($payment->sales_order_id);
                if ($invoice->status !== SalesInvoice::STATUS_CONFIRMED) {
                    throw new \RuntimeException('Faktur Penjualan belum dikonfirmasi.');
                }
                if ($payment->status !== ArPaymentModel::STATUS_DRAFT) {
                    throw new \RuntimeException('Pembayaran sudah diposting.');
                }
                if ($payment->amount > $invoice->amount_due) {
                    throw new \RuntimeException('Nominal melebihi sisa tagihan.');
                }
                if (! $payment->bankAccount?->chart_of_account_id) {
                    throw new \RuntimeException('Rekening belum terhubung ke Daftar Akun.');
                }
                $receivableId = ChartOfAccount::where('code', '1300')->where('is_active', true)->where('is_postable', true)->value('id');
                if (! $receivableId) {
                    throw new \RuntimeException('Akun 1300 Piutang Usaha tidak tersedia.');
                }

                $payment->update(['status' => ArPaymentModel::STATUS_POSTED]);
                $invoice->increment('paid_amount', $payment->amount);
                $invoice->decrement('amount_due', $payment->amount);
                $order->decrement('amount_due', $payment->amount);
                $journal = JournalEntry::create([
                    'code' => $this->generateJournalCode(), 'date' => $payment->payment_date,
                    'source_type' => JournalEntry::SOURCE_AR_PAYMENT, 'source_id' => $payment->id,
                    'description' => 'Pembayaran Piutang '.$payment->code,
                    'status' => JournalEntry::STATUS_POSTED, 'created_by' => Auth::id(),
                ]);
                $journal->lines()->create(['chart_of_account_id' => $payment->bankAccount->chart_of_account_id, 'debit' => $payment->amount, 'credit' => 0, 'description' => 'Penerimaan pelanggan']);
                $journal->lines()->create(['chart_of_account_id' => $receivableId, 'debit' => 0, 'credit' => $payment->amount, 'description' => 'Pelunasan '.$invoice->invoice_no]);
            });
            $this->dispatch('toast', message: 'Pembayaran Piutang berhasil diposting.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
        $this->showPostModal = false;
        $this->postTargetId = null;
    }

    private function resetForm(): void
    {
        $this->reset(['showModal', 'salesInvoiceId', 'bankAccountId', 'amount', 'notes']);
        $this->paymentDate = now()->toDateString();
        $this->paymentMethod = 'Transfer';
        $this->resetErrorBag();
    }

    private function generateCode(): string
    {
        $prefix = 'ARP-'.now()->format('dmy').'-';
        $last = ArPaymentModel::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');

        return $prefix.str_pad((string) ($last ? (int) substr($last, strlen($prefix)) + 1 : 1), 3, '0', STR_PAD_LEFT);
    }

    private function generateJournalCode(): string
    {
        $prefix = 'JE-'.now()->format('dmy').'-';
        $last = JournalEntry::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');

        return $prefix.str_pad((string) ($last ? (int) substr($last, strlen($prefix)) + 1 : 1), 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        return view('livewire.finance.transaction.ar-payment', [
            'payments' => ArPaymentModel::with(['salesInvoice', 'salesOrder', 'customer', 'bankAccount'])
                ->when($this->search, fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('code', 'like', '%'.$this->search.'%')->orWhereHas('salesInvoice', fn (Builder $invoice) => $invoice->where('invoice_no', 'like', '%'.$this->search.'%'))))
                ->latest('payment_date')->paginate($this->perPage),
            'salesInvoices' => SalesInvoice::with(['customer', 'salesOrder'])
                ->where('status', SalesInvoice::STATUS_CONFIRMED)->where('amount_due', '>', 0)
                ->latest('invoice_date')->get(),
            'bankAccounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
