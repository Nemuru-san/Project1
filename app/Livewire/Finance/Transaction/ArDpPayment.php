<?php

namespace App\Livewire\Finance\Transaction;

use App\Models\ArDpPayment as ArDpPaymentModel;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PreOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class ArDpPayment extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public bool $showTrashed = false;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public bool $showPostModal = false;

    public bool $showDetailModal = false;

    public ?int $editingId = null;

    public ?int $deleteTargetId = null;

    public ?int $postTargetId = null;

    public ?ArDpPaymentModel $selectedPayment = null;

    public string $code = '';

    public string $paymentDate = '';

    public ?int $preOrderId = null;

    public ?int $bankAccountId = null;

    public int $amount = 0;

    public string $paymentMethod = 'Transfer';

    public string $notes = '';

    protected function rules(): array
    {
        return [
            'paymentDate' => ['required', 'date'],
            'preOrderId' => ['required', 'integer', Rule::exists('pre_orders', 'id')->where(fn ($query) => $query->where('status', PreOrder::STATUS_DRAFT)->whereNull('deleted_at'))],
            'bankAccountId' => ['required', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'amount' => ['required', 'integer', 'min:1'],
            'paymentMethod' => ['required', 'string', Rule::in(['Transfer', 'Tunai', 'Giro'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected $messages = [
        'preOrderId.required' => 'Pesanan Awal wajib dipilih.',
        'bankAccountId.required' => 'Rekening penerimaan wajib dipilih.',
        'amount.min' => 'Nilai DP harus lebih dari 0.',
    ];

    public function mount(): void
    {
        $this->paymentDate = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedShowTrashed(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo', 'showTrashed']);
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->code = $this->generateCode();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $payment = ArDpPaymentModel::findOrFail($id);
        abort_unless($payment->status === ArDpPaymentModel::STATUS_DRAFT, 403);
        $this->resetForm();
        $this->editingId = $payment->id;
        $this->code = $payment->code;
        $this->paymentDate = $payment->payment_date->format('Y-m-d');
        $this->preOrderId = $payment->pre_order_id;
        $this->bankAccountId = $payment->bank_account_id;
        $this->amount = $payment->amount;
        $this->paymentMethod = $payment->payment_method;
        $this->notes = $payment->notes ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();
        $preOrder = PreOrder::findOrFail($this->preOrderId);
        $posted = (int) $preOrder->dpPayments()->where('status', ArDpPaymentModel::STATUS_POSTED)->sum('amount');
        if ($this->amount > max(0, (int) $preOrder->grand_total - $posted)) {
            $this->addError('amount', 'DP melebihi sisa nilai Pesanan Awal.');

            return;
        }

        DB::transaction(function () use ($preOrder) {
            $payment = $this->editingId ? ArDpPaymentModel::findOrFail($this->editingId) : new ArDpPaymentModel;
            if ($payment->exists && $payment->status !== ArDpPaymentModel::STATUS_DRAFT) {
                throw new \RuntimeException('DP yang sudah posted tidak dapat diubah.');
            }
            $payment->fill([
                'code' => $payment->exists ? $payment->code : $this->generateCode(),
                'payment_date' => $this->paymentDate,
                'pre_order_id' => $preOrder->id,
                'customer_id' => $preOrder->customer_id,
                'bank_account_id' => $this->bankAccountId,
                'amount' => $this->amount,
                'payment_method' => $this->paymentMethod,
                'status' => ArDpPaymentModel::STATUS_DRAFT,
                'notes' => trim($this->notes) ?: null,
                'created_by' => $payment->exists ? $payment->created_by : Auth::id(),
            ])->save();
        });

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Penerimaan DP berhasil disimpan sebagai draft.', type: 'success');
    }

    public function confirmPost(int $id): void
    {
        $payment = ArDpPaymentModel::findOrFail($id);
        if ($payment->status !== ArDpPaymentModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya DP draft yang dapat diposting.', type: 'error');

            return;
        }
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
                $payment = ArDpPaymentModel::with(['preOrder', 'bankAccount'])->lockForUpdate()->findOrFail($this->postTargetId);
                $preOrder = PreOrder::lockForUpdate()->findOrFail($payment->pre_order_id);
                if ($payment->status !== ArDpPaymentModel::STATUS_DRAFT) {
                    throw new \RuntimeException('DP sudah diposting.');
                }
                if ($preOrder->status !== PreOrder::STATUS_DRAFT) {
                    throw new \RuntimeException('Pesanan Awal sudah dikonversi ke Sales Order.');
                }

                $posted = (int) ArDpPaymentModel::where('pre_order_id', $preOrder->id)->where('status', ArDpPaymentModel::STATUS_POSTED)->sum('amount');
                if ($posted + $payment->amount > $preOrder->grand_total) {
                    throw new \RuntimeException('Total DP posted melebihi nilai Pesanan Awal.');
                }
                if (! $payment->bankAccount?->chart_of_account_id) {
                    throw new \RuntimeException('Rekening bank belum terhubung ke Daftar Akun.');
                }

                $advanceAccountId = ChartOfAccount::where('code', '2300')->where('is_active', true)->where('is_postable', true)->value('id');
                if (! $advanceAccountId) {
                    throw new \RuntimeException('Akun 2300 Uang Muka Pelanggan tidak tersedia.');
                }

                $payment->update(['status' => ArDpPaymentModel::STATUS_POSTED]);
                $journal = JournalEntry::create([
                    'code' => $this->generateJournalCode(), 'date' => $payment->payment_date,
                    'source_type' => JournalEntry::SOURCE_AR_DP_PAYMENT, 'source_id' => $payment->id,
                    'description' => 'Penerimaan DP '.$payment->code, 'status' => JournalEntry::STATUS_POSTED, 'created_by' => Auth::id(),
                ]);
                $journal->lines()->create(['chart_of_account_id' => $payment->bankAccount->chart_of_account_id, 'debit' => $payment->amount, 'credit' => 0, 'description' => 'Penerimaan DP '.$preOrder->pre_order_no]);
                $journal->lines()->create(['chart_of_account_id' => $advanceAccountId, 'debit' => 0, 'credit' => $payment->amount, 'description' => 'Uang muka pelanggan '.$payment->customer?->name]);
            });

            $this->showPostModal = false;
            $this->postTargetId = null;
            $this->dispatch('toast', message: 'DP berhasil diposting dan jurnal berhasil dibuat.', type: 'success');
        } catch (\Throwable $exception) {
            $this->showPostModal = false;
            $this->postTargetId = null;
            $this->dispatch('toast', message: $exception->getMessage(), type: 'error');
        }
    }

    public function confirmDelete(int $id): void
    {
        $payment = ArDpPaymentModel::findOrFail($id);
        if ($payment->status !== ArDpPaymentModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'DP posted tidak dapat dihapus.', type: 'error');

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
        ArDpPaymentModel::findOrFail($this->deleteTargetId)->delete();
        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Draft DP berhasil dihapus.', type: 'success');
    }

    public function restore(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        ArDpPaymentModel::onlyTrashed()->findOrFail($id)->restore();
        $this->dispatch('toast', message: 'Penerimaan DP berhasil dipulihkan.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $this->selectedPayment = ArDpPaymentModel::withTrashed()->with(['preOrder.salesOrder', 'customer', 'bankAccount', 'creator'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'preOrderId', 'bankAccountId', 'amount', 'notes']);
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentMethod = 'Transfer';
        $this->resetErrorBag();
    }

    private function generateCode(): string
    {
        $prefix = 'ARDP-'.now()->format('dmy').'-';
        $last = ArDpPaymentModel::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    private function generateJournalCode(): string
    {
        $prefix = 'JE-'.now()->format('dmy').'-';
        $last = JournalEntry::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        return view('livewire.finance.transaction.ar-dp-payment', [
            'payments' => ArDpPaymentModel::query()->with(['preOrder', 'customer', 'bankAccount'])
                ->when($this->showTrashed, fn (Builder $query) => $query->withTrashed())
                ->when($this->statusFilter, fn (Builder $query) => $query->where('status', $this->statusFilter))
                ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('payment_date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn (Builder $query) => $query->whereDate('payment_date', '<=', $this->dateTo))
                ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('code', 'like', '%'.$this->search.'%')->orWhereHas('preOrder', fn (Builder $preOrder) => $preOrder->where('pre_order_no', 'like', '%'.$this->search.'%'))->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%'.$this->search.'%'))))
                ->latest('payment_date')->latest('id')->paginate($this->perPage),
            'preOrders' => PreOrder::with('customer')->withSum(['dpPayments as posted_dp_amount' => fn ($query) => $query->where('status', ArDpPaymentModel::STATUS_POSTED)], 'amount')->where('status', PreOrder::STATUS_DRAFT)->orderByDesc('date')->get(),
            'selectedPreOrder' => $this->preOrderId ? PreOrder::withSum(['dpPayments as posted_dp_amount' => fn ($query) => $query->where('status', ArDpPaymentModel::STATUS_POSTED)], 'amount')->find($this->preOrderId) : null,
            'bankAccounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
