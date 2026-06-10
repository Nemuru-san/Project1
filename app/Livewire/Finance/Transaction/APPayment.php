<?php

namespace App\Livewire\Finance\Transaction;

use App\Models\APPayment as ModelsAPPayment;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;


class APPayment extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'payment_date';
    public string $sortDirection = 'desc';
    public string $statusFilter = '';
    public bool $showTrashed = false;

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public bool $showPostModal = false;
    public bool $showDetail = false;

    public ?int $paymentId = null;
    public ?int $deleteTargetId = null;
    public ?int $postTargetId = null;
    public ?ModelsAPPayment $selectedPayment = null;

    public string $code = '';
    public string $payment_date = '';
    public ?int $supplier_id = null;
    public ?int $bank_account_id = null;
    public string $payment_method = 'Transfer';
    public int $total_amount = 0;
    public string $note = '';

    public array $detailRows = [];

    protected function rules(): array
    {
        return [
            'payment_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'payment_method' => 'required|string|max:100',
            'note' => 'nullable|string|max:1000',

            'detailRows' => 'required|array|min:1',
            'detailRows.*.purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'detailRows.*.amount' => 'nullable|integer|min:0',
        ];
    }

    public function mount(): void
    {
        $this->payment_date = now()->format('Y-m-d');
    }

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

    public function updatedShowTrashed(): void
    {
        $this->resetPage();
    }

    public function updatedSupplierId(): void
    {
        if (!$this->paymentId) {
            $this->loadSupplierInvoices();
        }
    }

    public function updatedDetailRows(): void
    {
        $this->recalculateTotal();
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

    public function openCreate(): void
    {
        $this->resetForm();

        $this->code = $this->generateCode();
        $this->payment_date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $payment = ModelsAPPayment::with([
            'details.purchaseInvoice.supplier',
        ])->findOrFail($id);

        if ($payment->status !== ModelsAPPayment::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Payment yang sudah posted tidak bisa diedit.', type: 'error');
            return;
        }

        $this->resetForm();

        $this->paymentId = $payment->id;
        $this->code = $payment->code;
        $this->payment_date = $payment->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->supplier_id = $payment->supplier_id;
        $this->bank_account_id = $payment->bank_account_id;
        $this->payment_method = $payment->payment_method;
        $this->total_amount = (int) $payment->total_amount;
        $this->note = $payment->note ?? '';

        $this->detailRows = $payment->details->map(function ($detail) {
            $invoice = $detail->purchaseInvoice;

            $remaining = (int) ($invoice?->remaining_amount ?? 0);
            $amount = (int) $detail->amount;

            return [
                'purchase_invoice_id' => $detail->purchase_invoice_id,
                'invoice_code' => $invoice?->code ?? '-',
                'supplier_invoice_number' => $invoice?->supplier_invoice_number ?? '-',
                'date' => $invoice?->date?->format('d/m/Y') ?? '-',
                'due_date' => $invoice?->due_date?->format('d/m/Y') ?? '-',
                'grand_total' => (int) ($invoice?->grand_total ?? 0),
                'paid_amount' => (int) ($invoice?->paid_amount ?? 0),
                'remaining_amount' => $remaining + $amount,
                'amount' => $amount,
                'note' => $detail->note ?? null,
            ];
        })->toArray();

        $this->showModal = true;
    }

    public function openDetail(int $id): void
    {
        $this->selectedPayment = ModelsAPPayment::withTrashed()
            ->with([
                'supplier',
                'bankAccount',
                'creator',
                'details.purchaseInvoice',
            ])
            ->findOrFail($id);

        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->selectedPayment = null;
    }

    public function loadSupplierInvoices(): void
    {
        $this->detailRows = [];

        if (!$this->supplier_id) {
            $this->recalculateTotal();
            return;
        }

        $invoices = PurchaseInvoice::query()
            ->where('supplier_id', $this->supplier_id)
            ->where('status', PurchaseInvoice::STATUS_POSTED)
            ->where('payment_status', '!=', PurchaseInvoice::PAYMENT_PAID)
            ->where('remaining_amount', '>', 0)
            ->orderBy('due_date')
            ->orderBy('date')
            ->get();

        $this->detailRows = $invoices->map(function ($invoice) {
            return [
                'purchase_invoice_id' => $invoice->id,
                'invoice_code' => $invoice->code,
                'supplier_invoice_number' => $invoice->supplier_invoice_number ?: '-',
                'date' => $invoice->date?->format('d/m/Y') ?? '-',
                'due_date' => $invoice->due_date?->format('d/m/Y') ?? '-',
                'grand_total' => (int) $invoice->grand_total,
                'paid_amount' => (int) $invoice->paid_amount,
                'remaining_amount' => (int) $invoice->remaining_amount,
                'amount' => 0,
                'note' => null,
            ];
        })->toArray();

        $this->recalculateTotal();
    }

    public function payFull(int $index): void
    {
        if (!isset($this->detailRows[$index])) {
            return;
        }

        $this->detailRows[$index]['amount'] = (int) ($this->detailRows[$index]['remaining_amount'] ?? 0);

        $this->recalculateTotal();
    }

    public function clearAmount(int $index): void
    {
        if (!isset($this->detailRows[$index])) {
            return;
        }

        $this->detailRows[$index]['amount'] = 0;

        $this->recalculateTotal();
    }

    public function recalculateTotal(): void
    {
        $this->total_amount = collect($this->detailRows)
            ->sum(fn($row) => (int) ($row['amount'] ?? 0));
    }

    public function save(): void
    {
        $this->recalculateTotal();

        $this->validate();

        if ($this->total_amount <= 0) {
            $this->addError('total_amount', 'Total pembayaran harus lebih dari 0.');
            return;
        }

        foreach ($this->detailRows as $index => $row) {
            $amount = (int) ($row['amount'] ?? 0);
            $remaining = (int) ($row['remaining_amount'] ?? 0);

            if ($amount < 0) {
                $this->addError("detailRows.$index.amount", 'Amount tidak boleh minus.');
                return;
            }

            if ($amount > $remaining) {
                $this->addError("detailRows.$index.amount", 'Amount tidak boleh melebihi sisa tagihan.');
                return;
            }
        }

        $payingRows = collect($this->detailRows)
            ->filter(fn($row) => (int) ($row['amount'] ?? 0) > 0)
            ->values();

        if ($payingRows->isEmpty()) {
            $this->addError('total_amount', 'Minimal isi satu amount pembayaran.');
            return;
        }

        if ($this->paymentId) {
            $payment = ModelsAPPayment::findOrFail($this->paymentId);

            if ($payment->status !== ModelsAPPayment::STATUS_DRAFT) {
                $this->dispatch('toast', message: 'Payment yang sudah posted tidak bisa diedit.', type: 'error');
                return;
            }
        }

        DB::transaction(function () use ($payingRows) {
            $data = [
                'code' => $this->code ?: $this->generateCode(),
                'payment_date' => $this->payment_date,
                'supplier_id' => $this->supplier_id,
                'bank_account_id' => $this->bank_account_id,
                'total_amount' => $this->total_amount,
                'payment_method' => $this->payment_method,
                'note' => $this->note ?: null,
            ];

            if ($this->paymentId) {
                $payment = ModelsAPPayment::findOrFail($this->paymentId);

                $payment->update($data);
                $payment->details()->delete();
            } else {
                $data['status'] = ModelsAPPayment::STATUS_DRAFT;
                $data['created_by'] = Auth::id();

                $payment = ModelsAPPayment::create($data);
            }

            foreach ($payingRows as $row) {
                $payment->details()->create([
                    'purchase_invoice_id' => $row['purchase_invoice_id'],
                    'amount' => (int) $row['amount'],
                    'note' => $row['note'] ?? null,
                ]);
            }
        });

        $this->showModal = false;
        $this->resetForm();

        $this->dispatch('toast', message: 'AP Payment berhasil disimpan.', type: 'success');
    }

    public function confirmPost(int $id): void
    {
        $payment = ModelsAPPayment::with('details')->findOrFail($id);

        if ($payment->status !== ModelsAPPayment::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya payment Draft yang bisa di-post.', type: 'error');
            return;
        }

        if ($payment->details->isEmpty()) {
            $this->dispatch('toast', message: 'Payment tidak bisa di-post karena detail masih kosong.', type: 'error');
            return;
        }

        $this->postTargetId = $id;
        $this->showPostModal = true;
    }

    public function cancelPost(): void
    {
        $this->showPostModal = false;
        $this->postTargetId = null;
    }

    public function postPayment(): void
    {
        if (!$this->postTargetId) {
            return;
        }

        try {
            DB::transaction(function () {
                $payment = ModelsAPPayment::with([
                    'details.purchaseInvoice',
                    'supplier',
                    'bankAccount',
                ])
                    ->lockForUpdate()
                    ->findOrFail($this->postTargetId);

                if ($payment->status !== ModelsAPPayment::STATUS_DRAFT) {
                    throw new \Exception('Hanya payment Draft yang bisa di-post.');
                }

                if ($payment->details->isEmpty()) {
                    throw new \Exception('Payment tidak bisa di-post karena detail masih kosong.');
                }

                if ((int) $payment->total_amount <= 0) {
                    throw new \Exception('Payment tidak bisa di-post karena total amount masih 0.');
                }

                if (!$payment->bankAccount) {
                    throw new \Exception('Bank account tidak ditemukan.');
                }

                if (!$payment->bankAccount->chart_of_account_id) {
                    throw new \Exception('Bank account belum terhubung ke Chart of Account.');
                }

                foreach ($payment->details as $detail) {
                    $invoice = PurchaseInvoice::lockForUpdate()
                        ->findOrFail($detail->purchase_invoice_id);

                    if ($invoice->status !== PurchaseInvoice::STATUS_POSTED) {
                        throw new \Exception("Invoice {$invoice->code} belum Posted.");
                    }

                    if ($invoice->payment_status === PurchaseInvoice::PAYMENT_PAID) {
                        throw new \Exception("Invoice {$invoice->code} sudah lunas.");
                    }

                    $amount = (int) $detail->amount;
                    $remaining = (int) $invoice->remaining_amount;

                    if ($amount <= 0) {
                        throw new \Exception("Amount invoice {$invoice->code} harus lebih dari 0.");
                    }

                    if ($amount > $remaining) {
                        throw new \Exception("Amount invoice {$invoice->code} melebihi sisa tagihan.");
                    }

                    $newPaid = (int) $invoice->paid_amount + $amount;
                    $newRemaining = max(0, (int) $invoice->grand_total - $newPaid);

                    $invoice->update([
                        'paid_amount' => $newPaid,
                        'remaining_amount' => $newRemaining,
                        'payment_status' => $newRemaining <= 0
                            ? PurchaseInvoice::PAYMENT_PAID
                            : PurchaseInvoice::PAYMENT_PARTIAL,
                    ]);
                }

                $payment->update([
                    'status' => ModelsAPPayment::STATUS_POSTED,
                ]);

                $this->createAPPaymentJournal($payment);
            });

            $this->showPostModal = false;
            $this->postTargetId = null;

            if ($this->selectedPayment) {
                $this->selectedPayment = ModelsAPPayment::with([
                    'supplier',
                    'bankAccount',
                    'creator',
                    'details.purchaseInvoice',
                ])->find($this->selectedPayment->id);
            }

            $this->dispatch('toast', message: 'AP Payment berhasil di-post dan journal entry berhasil dibuat.', type: 'success');
        } catch (\Throwable $e) {
            $this->showPostModal = false;
            $this->postTargetId = null;

            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    private function createAPPaymentJournal(ModelsAPPayment $payment): void
    {
        $existingJournal = JournalEntry::where('source_type', JournalEntry::SOURCE_AP_PAYMENT)
            ->where('source_id', $payment->id)
            ->first();

        if ($existingJournal) {
            return;
        }

        $accountPayableAccountId = $this->getChartOfAccountId('2100');
        $bankAccountCoaId = (int) $payment->bankAccount->chart_of_account_id;

        $journal = JournalEntry::create([
            'code' => $this->generateJournalCode(),
            'date' => $payment->payment_date,
            'source_type' => JournalEntry::SOURCE_AP_PAYMENT,
            'source_id' => $payment->id,
            'description' => 'AP Payment ' . $payment->code,
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => Auth::id(),
        ]);

        $journal->lines()->create([
            'chart_of_account_id' => $accountPayableAccountId,
            'debit' => (int) $payment->total_amount,
            'credit' => 0,
            'description' => 'Pembayaran hutang supplier ' . ($payment->supplier?->name ?? '-'),
        ]);

        $journal->lines()->create([
            'chart_of_account_id' => $bankAccountCoaId,
            'debit' => 0,
            'credit' => (int) $payment->total_amount,
            'description' => 'Pembayaran via ' . (
                $payment->bankAccount?->name
                ?? $payment->bankAccount?->bank_name
                ?? '-'
            ),
        ]);
    }

    private function getChartOfAccountId(string $code): int
    {
        $account = ChartOfAccount::where('code', $code)
            ->where('is_active', true)
            ->where('is_postable', true)
            ->first();

        if (!$account) {
            throw new \Exception("Chart of Account {$code} tidak ditemukan / tidak aktif / tidak postable.");
        }

        return $account->id;
    }

    private function generateJournalCode(): string
    {
        $date   = now()->format('dmy');
        $prefix = "JE-{$date}-";

        $last = JournalEntry::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function confirmDelete(int $id): void
    {
        $payment = ModelsAPPayment::findOrFail($id);

        if ($payment->status !== ModelsAPPayment::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Payment yang sudah posted tidak bisa dihapus.', type: 'error');
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

        $payment = ModelsAPPayment::findOrFail($this->deleteTargetId);

        if ($payment->status !== ModelsAPPayment::STATUS_DRAFT) {
            $this->showDeleteModal = false;
            $this->deleteTargetId = null;

            $this->dispatch('toast', message: 'Payment yang sudah posted tidak bisa dihapus.', type: 'error');
            return;
        }

        $payment->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'AP Payment berhasil dihapus.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset([
            'paymentId',
            'deleteTargetId',
            'postTargetId',
            'code',
            'supplier_id',
            'bank_account_id',
            'total_amount',
            'note',
            'detailRows',
        ]);

        $this->payment_date = now()->format('Y-m-d');
        $this->payment_method = 'Transfer';

        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function generateCode(): string
    {
        $date   = now()->format('dmy');
        $prefix = "APP-{$date}-";

        $last = ModelsAPPayment::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $payments = ModelsAPPayment::query()
            ->with(['supplier', 'bankAccount'])
            ->when($this->showTrashed, fn($query) => $query->withTrashed())
            ->when($this->search, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('code', 'like', '%' . $this->search . '%')
                        ->orWhere('payment_method', 'like', '%' . $this->search . '%')
                        ->orWhereHas('supplier', function ($supplierQuery) {
                            $supplierQuery->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, fn($query) => $query->where('status', $this->statusFilter))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.finance.transaction.ap-payment', [
            'payments' => $payments,
            'suppliers' => Supplier::orderBy('name')->get(),
            'bankAccounts' => BankAccount::orderBy('name')->get(),
        ]);
    }
}
