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

    public string $statusFilter = '';

    public int $perPage = 10;

    public bool $showModal = false;

    public bool $showPostModal = false;

    public ?int $postTargetId = null;

    public bool $showDetailModal = false;

    public ?ArPaymentModel $selectedPayment = null;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    public bool $showCancelModal = false;

    public ?int $cancelTargetId = null;

    public ?int $editingId = null;

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

    public function updatingStatusFilter(): void
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

    public function openEdit(int $id): void
    {
        abort_unless(auth()->user()?->hasPermission('finance.transaction.ar-payment'), 403);
        $payment = ArPaymentModel::findOrFail($id);

        if ($payment->status !== ArPaymentModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya pembayaran berstatus Draf yang dapat diubah.', type: 'error');

            return;
        }

        $this->resetForm();
        $this->editingId = $payment->id;
        $this->code = $payment->code;
        $this->paymentDate = $payment->payment_date->toDateString();
        $this->salesInvoiceId = $payment->sales_invoice_id;
        $this->bankAccountId = $payment->bank_account_id;
        $this->amount = (int) $payment->amount;
        $this->paymentMethod = $payment->payment_method;
        $this->notes = $payment->notes ?? '';
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

        $data = [
            'payment_date' => $this->paymentDate,
            'sales_order_id' => $invoice->sales_order_id, 'sales_invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'bank_account_id' => $this->bankAccountId, 'amount' => $this->amount,
            'payment_method' => $this->paymentMethod,
            'notes' => trim($this->notes) ?: null,
        ];

        if ($this->editingId) {
            $payment = ArPaymentModel::findOrFail($this->editingId);
            if ($payment->status !== ArPaymentModel::STATUS_DRAFT) {
                $this->addError('amount', 'Pembayaran yang sudah diposting tidak dapat diubah.');

                return;
            }
            $payment->update($data);
            $message = 'Pembayaran Piutang berhasil diperbarui.';
        } else {
            ArPaymentModel::create($data + [
                'code' => $this->generateCode(), 'status' => ArPaymentModel::STATUS_DRAFT, 'created_by' => Auth::id(),
            ]);
            $message = 'Pembayaran Piutang berhasil disimpan sebagai draf.';
        }

        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function openDetail(int $id): void
    {
        $this->selectedPayment = ArPaymentModel::withTrashed()
            ->with(['salesInvoice' => fn ($query) => $query->withTrashed(), 'salesInvoice.salesOrder.preOrder', 'customer', 'bankAccount', 'creator'])
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedPayment = null;
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
                $invoice = SalesInvoice::lockForUpdate()->find($payment->sales_invoice_id);
                if (! $invoice) {
                    throw new \RuntimeException('Faktur Penjualan untuk pembayaran ini sudah dibatalkan.');
                }
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

    public function confirmCancel(int $id): void
    {
        if (! auth()->user()?->canCancelTransactions()) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk membatalkan Pembayaran Piutang.', type: 'error');

            return;
        }

        $payment = ArPaymentModel::findOrFail($id);
        if ($payment->status === ArPaymentModel::STATUS_CANCELLED) {
            $this->dispatch('toast', message: 'Pembayaran Piutang sudah dibatalkan.', type: 'error');

            return;
        }

        $this->cancelTargetId = $id;
        $this->showCancelModal = true;
    }

    public function closeCancel(): void
    {
        $this->showCancelModal = false;
        $this->cancelTargetId = null;
    }

    /**
     * Batalkan pembayaran: draf cukup ditandai batal; yang sudah diposting dikembalikan ke sisa
     * tagihan faktur & SO dan jurnalnya dibatalkan, sehingga faktur bisa dibatalkan/diubah lagi.
     */
    public function cancelPayment(): void
    {
        if (! auth()->user()?->canCancelTransactions()) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk membatalkan Pembayaran Piutang.', type: 'error');

            return;
        }
        if (! $this->cancelTargetId) {
            return;
        }

        try {
            DB::transaction(function () {
                $payment = ArPaymentModel::lockForUpdate()->findOrFail($this->cancelTargetId);
                if ($payment->status === ArPaymentModel::STATUS_CANCELLED) {
                    throw new \RuntimeException('Pembayaran Piutang sudah dibatalkan.');
                }

                if ($payment->status === ArPaymentModel::STATUS_POSTED) {
                    $amount = (int) $payment->amount;
                    $invoice = SalesInvoice::withTrashed()->lockForUpdate()->find($payment->sales_invoice_id);
                    if ($invoice) {
                        $invoice->update([
                            'paid_amount' => max(0, (int) $invoice->paid_amount - $amount),
                            'amount_due' => (int) $invoice->amount_due + $amount,
                        ]);
                    }
                    SalesOrder::whereKey($payment->sales_order_id)->increment('amount_due', $amount);

                    JournalEntry::where('source_type', JournalEntry::SOURCE_AR_PAYMENT)
                        ->where('source_id', $payment->id)
                        ->update(['status' => JournalEntry::STATUS_CANCELLED]);
                }

                $payment->update(['status' => ArPaymentModel::STATUS_CANCELLED]);
            });

            if ($this->selectedPayment?->id === $this->cancelTargetId) {
                $this->openDetail($this->cancelTargetId);
            }
            $this->dispatch('toast', message: 'Pembayaran Piutang berhasil dibatalkan.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }

        $this->closeCancel();
    }

    public function confirmDelete(int $id): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');

            return;
        }

        if (ArPaymentModel::findOrFail($id)->status !== ArPaymentModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya pembayaran berstatus Draf yang dapat dihapus.', type: 'error');

            return;
        }

        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');

            return;
        }
        if (! $this->deleteTargetId) {
            return;
        }

        $payment = ArPaymentModel::findOrFail($this->deleteTargetId);
        if ($payment->status !== ArPaymentModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya pembayaran berstatus Draf yang dapat dihapus.', type: 'error');
        } else {
            $payment->delete();
            $this->dispatch('toast', message: 'Draf Pembayaran Piutang berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
    }

    private function resetForm(): void
    {
        $this->reset(['showModal', 'editingId', 'salesInvoiceId', 'bankAccountId', 'amount', 'notes']);
        $this->paymentDate = now()->toDateString();
        $this->paymentMethod = 'Transfer';
        $this->resetErrorBag();
    }

    private function generateCode(): string
    {
        $prefix = 'ARP-'.now()->format('ym').'-';
        $last = ArPaymentModel::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');

        return $prefix.str_pad((string) ($last ? (int) substr($last, strlen($prefix)) + 1 : 1), 3, '0', STR_PAD_LEFT);
    }

    private function generateJournalCode(): string
    {
        $prefix = 'JE-'.now()->format('ym').'-';
        $last = JournalEntry::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');

        return $prefix.str_pad((string) ($last ? (int) substr($last, strlen($prefix)) + 1 : 1), 3, '0', STR_PAD_LEFT);
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $salesInvoices = SalesInvoice::with(['customer', 'salesOrder'])
            ->where('status', SalesInvoice::STATUS_CONFIRMED)->where('amount_due', '>', 0)
            ->latest('invoice_date')->get();

        // Saat mengubah draf, faktur yang sedang dipakai tetap tampil walau sisa tagihannya sudah 0.
        if ($this->editingId && $this->salesInvoiceId && ! $salesInvoices->contains('id', $this->salesInvoiceId)) {
            if ($current = SalesInvoice::with(['customer', 'salesOrder'])->find($this->salesInvoiceId)) {
                $salesInvoices->prepend($current);
            }
        }

        return view('livewire.finance.transaction.ar-payment', [
            'payments' => ArPaymentModel::with(['salesInvoice' => fn ($query) => $query->withTrashed(), 'salesOrder', 'customer', 'bankAccount'])
                ->when($this->statusFilter, fn (Builder $q) => $q->where('status', $this->statusFilter))
                ->when($this->search, fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('code', 'like', '%'.$this->search.'%')->orWhereHas('salesInvoice', fn (Builder $invoice) => $invoice->where('invoice_no', 'like', '%'.$this->search.'%'))))
                ->latest('payment_date')->latest('id')->paginate($this->perPage),
            'salesInvoices' => $salesInvoices,
            'bankAccounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
            // Ringkasan tagihan faktur terpilih, termasuk DP dari Pre Order yang sudah memotong tagihan.
            'selectedInvoice' => $this->salesInvoiceId
                ? SalesInvoice::with('salesOrder.preOrder')->find($this->salesInvoiceId)
                : null,
        ]);
    }
}
