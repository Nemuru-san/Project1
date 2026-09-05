<?php

namespace App\Livewire\Finance\Transaction;

use App\Models\ArDpPayment as ArDpPaymentModel;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\PreOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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

    public ?int $customerId = null;

    public array $selectedPreOrderIds = [];

    public array $allocations = [];

    public ?int $bankAccountId = null;

    public int $amount = 0;

    public string $paymentMethod = 'Transfer';

    public string $notes = '';

    protected function rules(): array
    {
        return [
            'paymentDate' => ['required', 'date'],
            'customerId' => ['required', 'integer', Rule::exists('customers', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'selectedPreOrderIds' => ['required', 'array', 'min:1'],
            'selectedPreOrderIds.*' => ['integer', 'distinct', Rule::exists('pre_orders', 'id')],
            'allocations' => ['required', 'array'],
            'allocations.*' => ['required', 'integer', 'min:1'],
            'bankAccountId' => ['required', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'paymentMethod' => ['required', 'string', Rule::in(['Transfer', 'Tunai', 'Giro'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected $messages = [
        'customerId.required' => 'Pelanggan wajib dipilih terlebih dahulu.',
        'selectedPreOrderIds.required' => 'Pilih minimal satu Pesanan Awal.',
        'selectedPreOrderIds.min' => 'Pilih minimal satu Pesanan Awal.',
        'allocations.*.min' => 'Nominal alokasi DP harus lebih dari 0.',
        'bankAccountId.required' => 'Rekening penerimaan wajib dipilih.',
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

    public function updatedCustomerId(): void
    {
        $this->selectedPreOrderIds = [];
        $this->allocations = [];
        $this->amount = 0;
        $this->resetErrorBag(['customerId', 'selectedPreOrderIds', 'allocations']);
    }

    public function updatedSelectedPreOrderIds(): void
    {
        if (! $this->customerId) {
            $this->selectedPreOrderIds = [];
            $this->allocations = [];
            $this->amount = 0;

            return;
        }

        $ids = array_values(array_unique(array_map('intval', $this->selectedPreOrderIds)));
        $preOrders = $this->eligiblePreOrdersQuery()
            ->where('customer_id', $this->customerId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $this->selectedPreOrderIds = array_values(array_filter($ids, fn (int $id) => $preOrders->has($id)));

        foreach ($this->selectedPreOrderIds as $id) {
            if (! isset($this->allocations[$id]) || (int) $this->allocations[$id] <= 0) {
                $this->allocations[$id] = $preOrders->get($id)->remaining_dp_amount;
            }
        }

        foreach (array_keys($this->allocations) as $id) {
            if (! in_array((int) $id, $this->selectedPreOrderIds, true)) {
                unset($this->allocations[$id]);
            }
        }

        $this->recalculateAmount();
        $this->resetErrorBag(['selectedPreOrderIds', 'allocations']);
    }

    public function togglePreOrderSelection(int $id): void
    {
        $selectedIds = array_map('intval', $this->selectedPreOrderIds);

        if (in_array($id, $selectedIds, true)) {
            $this->selectedPreOrderIds = array_values(array_filter($selectedIds, fn (int $selectedId) => $selectedId !== $id));
        } else {
            $this->selectedPreOrderIds = [...$selectedIds, $id];
        }

        $this->updatedSelectedPreOrderIds();
    }

    public function updatedAllocations(mixed $value, string $key): void
    {
        $this->allocations[(int) $key] = max(0, (int) $value);
        $this->recalculateAmount();
        $this->resetErrorBag("allocations.$key");
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->code = $this->generateCode();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $payment = ArDpPaymentModel::with('allocations')->findOrFail($id);
        abort_unless($payment->status === ArDpPaymentModel::STATUS_DRAFT, 403);

        $this->resetForm();
        $this->editingId = $payment->id;
        $this->code = $payment->code;
        $this->paymentDate = $payment->payment_date->format('Y-m-d');
        $this->customerId = $payment->customer_id;
        $this->bankAccountId = $payment->bank_account_id;
        $this->paymentMethod = $payment->payment_method;
        $this->notes = $payment->notes ?? '';

        $allocations = $payment->allocations;
        if ($allocations->isEmpty() && $payment->pre_order_id) {
            $this->selectedPreOrderIds = [(int) $payment->pre_order_id];
            $this->allocations = [(int) $payment->pre_order_id => (int) $payment->amount];
        } else {
            $this->selectedPreOrderIds = $allocations->pluck('pre_order_id')->map(fn ($id) => (int) $id)->all();
            $this->allocations = $allocations->mapWithKeys(fn ($allocation) => [(int) $allocation->pre_order_id => (int) $allocation->amount])->all();
        }

        $this->recalculateAmount();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $ids = array_values(array_unique(array_map('intval', $this->selectedPreOrderIds)));
        $preOrders = $this->eligiblePreOrdersQuery()
            ->where('customer_id', $this->customerId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        if ($preOrders->count() !== count($ids)) {
            $this->addError('selectedPreOrderIds', 'Pilihan Pesanan Awal tidak valid atau sudah lunas.');

            return;
        }

        foreach ($ids as $id) {
            $allocation = (int) ($this->allocations[$id] ?? 0);
            if ($allocation <= 0) {
                $this->addError("allocations.$id", 'Nominal alokasi DP harus lebih dari 0.');
            } elseif ($allocation > $preOrders->get($id)->remaining_dp_amount) {
                $this->addError("allocations.$id", 'Nominal melebihi sisa target DP Pesanan Awal.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $total = array_sum(array_map(fn ($id) => (int) $this->allocations[$id], $ids));

        DB::transaction(function () use ($ids, $total) {
            $payment = $this->editingId ? ArDpPaymentModel::lockForUpdate()->findOrFail($this->editingId) : new ArDpPaymentModel;
            if ($payment->exists && $payment->status !== ArDpPaymentModel::STATUS_DRAFT) {
                throw new \RuntimeException('DP yang sudah diposting tidak dapat diubah.');
            }

            $payment->fill([
                'code' => $payment->exists ? $payment->code : $this->generateCode(),
                'payment_date' => $this->paymentDate,
                'pre_order_id' => $ids[0],
                'customer_id' => $this->customerId,
                'bank_account_id' => $this->bankAccountId,
                'amount' => $total,
                'payment_method' => $this->paymentMethod,
                'status' => ArDpPaymentModel::STATUS_DRAFT,
                'notes' => trim($this->notes) ?: null,
                'created_by' => $payment->exists ? $payment->created_by : Auth::id(),
            ])->save();

            $payment->allocations()->delete();
            foreach ($ids as $id) {
                $payment->allocations()->create([
                    'pre_order_id' => $id,
                    'amount' => (int) $this->allocations[$id],
                ]);
            }
        });

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: 'Penerimaan DP berhasil disimpan sebagai draft.', type: 'success');
    }

    public function confirmPost(int $id): void
    {
        $payment = ArDpPaymentModel::findOrFail($id);
        if ($payment->status !== ArDpPaymentModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya DP berstatus Draf yang dapat diposting.', type: 'error');

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
                $payment = ArDpPaymentModel::with(['allocations.preOrder', 'bankAccount', 'customer'])->lockForUpdate()->findOrFail($this->postTargetId);
                if ($payment->status !== ArDpPaymentModel::STATUS_DRAFT) {
                    throw new \RuntimeException('DP sudah diposting.');
                }
                if ($payment->allocations->isEmpty()) {
                    throw new \RuntimeException('Penerimaan DP belum memiliki alokasi Pesanan Awal.');
                }
                if (! $payment->bankAccount?->chart_of_account_id) {
                    throw new \RuntimeException('Rekening bank belum terhubung ke Daftar Akun.');
                }

                $preOrders = PreOrder::whereIn('id', $payment->allocations->pluck('pre_order_id'))->lockForUpdate()->get()->keyBy('id');
                foreach ($payment->allocations as $allocation) {
                    $preOrder = $preOrders->get($allocation->pre_order_id);
                    if (! $preOrder || $preOrder->customer_id !== $payment->customer_id || $preOrder->status !== PreOrder::STATUS_CONFIRMED) {
                        throw new \RuntimeException('Semua Pesanan Awal harus milik pelanggan yang dipilih dan berstatus Dikonfirmasi.');
                    }

                    $posted = (int) $preOrder->dpAllocations()
                        ->whereHas('payment', fn ($query) => $query->where('status', ArDpPaymentModel::STATUS_POSTED))
                        ->sum('amount');
                    if ($posted + $allocation->amount > $preOrder->dp_amount) {
                        throw new \RuntimeException("Pembayaran {$preOrder->pre_order_no} melebihi sisa target DP.");
                    }
                }

                $advanceAccountId = ChartOfAccount::where('code', '2300')->where('is_active', true)->where('is_postable', true)->value('id');
                if (! $advanceAccountId) {
                    throw new \RuntimeException('Akun 2300 Uang Muka Pelanggan tidak tersedia.');
                }

                $payment->update(['status' => ArDpPaymentModel::STATUS_POSTED]);
                foreach ($preOrders as $preOrder) {
                    $preOrder->syncDpPaymentStatus();
                }

                $preOrderNumbers = $payment->allocations->map(fn ($allocation) => $allocation->preOrder?->pre_order_no)->filter()->implode(', ');
                $journal = JournalEntry::create([
                    'code' => $this->generateJournalCode(),
                    'date' => $payment->payment_date,
                    'source_type' => JournalEntry::SOURCE_AR_DP_PAYMENT,
                    'source_id' => $payment->id,
                    'description' => 'Penerimaan DP '.$payment->code,
                    'status' => JournalEntry::STATUS_POSTED,
                    'created_by' => Auth::id(),
                ]);
                $journal->lines()->create([
                    'chart_of_account_id' => $payment->bankAccount->chart_of_account_id,
                    'debit' => $payment->amount,
                    'credit' => 0,
                    'description' => 'Penerimaan DP '.$preOrderNumbers,
                ]);
                $journal->lines()->create([
                    'chart_of_account_id' => $advanceAccountId,
                    'debit' => 0,
                    'credit' => $payment->amount,
                    'description' => 'Uang muka pelanggan '.$payment->customer?->name,
                ]);
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
            $this->dispatch('toast', message: 'DP yang sudah diposting tidak dapat dihapus.', type: 'error');

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
        $this->selectedPayment = ArDpPaymentModel::withTrashed()
            ->with(['allocations.preOrder.salesOrder', 'customer', 'bankAccount', 'creator'])
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'customerId', 'selectedPreOrderIds', 'allocations', 'bankAccountId', 'amount', 'notes']);
        $this->paymentDate = now()->format('Y-m-d');
        $this->paymentMethod = 'Transfer';
        $this->resetErrorBag();
    }

    private function recalculateAmount(): void
    {
        $this->amount = array_sum(array_map('intval', $this->allocations));
    }

    private function eligiblePreOrdersQuery(): Builder
    {
        return PreOrder::query()
            ->withSum([
                'dpAllocations as posted_dp_amount' => fn ($query) => $query->whereHas('payment', fn ($payment) => $payment->where('status', ArDpPaymentModel::STATUS_POSTED)),
            ], 'amount')
            ->where('status', PreOrder::STATUS_CONFIRMED)
            ->where('dp_payment_status', '!=', PreOrder::DP_STATUS_PAID)
            ->where('dp_amount', '>', 0);
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
        $preOrders = $this->customerId
            ? $this->eligiblePreOrdersQuery()->where('customer_id', $this->customerId)->latest('date')->latest('id')->get()
            : new Collection;

        return view('livewire.finance.transaction.ar-dp-payment', [
            'payments' => ArDpPaymentModel::query()->with(['allocations.preOrder', 'customer', 'bankAccount'])
                ->when($this->showTrashed, fn (Builder $query) => $query->withTrashed())
                ->when($this->statusFilter, fn (Builder $query) => $query->where('status', $this->statusFilter))
                ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('payment_date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn (Builder $query) => $query->whereDate('payment_date', '<=', $this->dateTo))
                ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('code', 'like', '%'.$this->search.'%')
                    ->orWhereHas('allocations.preOrder', fn (Builder $preOrder) => $preOrder->where('pre_order_no', 'like', '%'.$this->search.'%'))
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%'.$this->search.'%'))))
                ->latest('payment_date')->latest('id')->paginate($this->perPage),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'preOrders' => $preOrders,
            'bankAccounts' => BankAccount::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
