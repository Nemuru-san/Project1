<?php

namespace App\Livewire\Sales\Transaction;

use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\JournalEntry;
use App\Models\SalesInvoice as SalesInvoiceModel;
use App\Models\SalesOrder;
use App\Services\Sales\CustomerCreditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class SalesInvoice extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public bool $showModal = false;

    public bool $showDetailModal = false;

    public bool $showConfirmModal = false;

    public bool $showDeleteModal = false;

    public ?int $confirmTargetId = null;

    public ?int $deleteTargetId = null;

    public ?int $editingId = null;

    public ?SalesInvoiceModel $selectedInvoice = null;

    public string $invoiceNo = '';

    public string $invoiceDate = '';

    public string $dueDate = '';

    public ?int $salesOrderId = null;

    public array $selectedDeliveryOrderIds = [];

    public string $notes = '';

    public array $items = [];

    public array $totals = [];

    protected function rules(): array
    {
        return [
            'invoiceDate' => ['required', 'date'],
            'dueDate' => ['nullable', 'date', 'after_or_equal:invoiceDate'],
            'salesOrderId' => [
                'required', 'integer',
                Rule::exists('sales_orders', 'id')->whereNotNull('verified_at')->whereNull('deleted_at'),
                Rule::unique('sales_invoices', 'sales_order_id')->whereNull('deleted_at')->ignore($this->editingId),
            ],
            'selectedDeliveryOrderIds' => ['array'],
            'selectedDeliveryOrderIds.*' => ['integer', 'distinct', 'exists:delivery_orders,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected $messages = [
        'salesOrderId.required' => 'Pesanan Penjualan wajib dipilih.',
        'salesOrderId.exists' => 'Pesanan Penjualan harus dikonfirmasi terlebih dahulu.',
        'salesOrderId.unique' => 'Pesanan Penjualan ini sudah memiliki Faktur Penjualan.',
        'dueDate.after_or_equal' => 'Tanggal jatuh tempo tidak boleh sebelum tanggal faktur.',
        'selectedDeliveryOrderIds.required' => 'Pilih minimal satu Surat Jalan.',
    ];

    public function mount(): void
    {
        $this->invoiceDate = now()->toDateString();
        $this->dueDate = now()->addDays(30)->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
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

    public function resetFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorizeModule();
        $this->resetForm();
        $this->invoiceNo = $this->generateCode();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorizeModule();
        $invoice = SalesInvoiceModel::with([
            'salesOrder', 'items.product', 'items.warehouse', 'items.unit', 'deliveryOrders',
        ])->findOrFail($id);
        $this->authorizeInvoice($invoice);

        $this->resetForm();
        $this->editingId = $invoice->id;
        $this->invoiceNo = $invoice->invoice_no;
        $this->invoiceDate = $invoice->invoice_date->toDateString();
        $this->dueDate = $invoice->due_date?->toDateString() ?? '';
        $this->salesOrderId = $invoice->sales_order_id;
        $this->notes = $invoice->notes ?? '';
        $this->selectedDeliveryOrderIds = $invoice->deliveryOrders->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->items = $invoice->items->map(fn ($item) => [
            'product_name' => $item->product?->name ?? '-',
            'sku' => $item->product?->sku ?? '-',
            'warehouse_name' => $item->warehouse?->name ?? '-',
            'unit_name' => $item->unit?->name ?? '-',
            'qty' => (int) $item->qty,
            'unit_price' => (int) $item->unit_price,
            'discount_amount' => (int) $item->discount_amount,
            'line_total' => (int) $item->line_total,
        ])->all();
        $this->totals = [
            'subtotal' => (int) $invoice->subtotal,
            'discount_total' => (int) $invoice->discount_total,
            'tax_amount' => (int) $invoice->tax_amount,
            'grand_total' => (int) $invoice->grand_total,
            'dp_amount' => (int) $invoice->dp_amount,
            'amount_due' => (int) $invoice->amount_due,
        ];
        $this->showModal = true;
    }

    public function updatedSalesOrderId(mixed $value): void
    {
        $this->selectedDeliveryOrderIds = [];
        $this->items = [];
        $this->totals = [];
        if (! $value) {
            return;
        }

        $order = SalesOrder::with(['customer', 'items.product', 'items.warehouse', 'items.unit'])
            ->whereNotNull('verified_at')->findOrFail((int) $value);
        $this->authorizeSalesOrder($order);

        if ($order->salesInvoice()->whereNull('deleted_at')->exists()) {
            $this->addError('salesOrderId', 'Pesanan Penjualan ini sudah memiliki Faktur Penjualan.');

            return;
        }

        $this->items = $order->items->map(fn ($item) => [
            'product_name' => $item->product?->name ?? '-',
            'sku' => $item->product?->sku ?? '-',
            'warehouse_name' => $item->warehouse?->name ?? '-',
            'unit_name' => $item->unit?->name ?? '-',
            'qty' => (int) $item->qty,
            'unit_price' => (int) $item->unit_price,
            'discount_amount' => (int) $item->discount_amount,
            'line_total' => (int) $item->line_total,
        ])->all();
        $this->totals = $this->totalsFromOrder($order);
        $this->applyCustomerPaymentTerms($order->customer);
    }

    public function updatedInvoiceDate(): void
    {
        if (! $this->salesOrderId) {
            return;
        }

        $this->applyCustomerPaymentTerms(
            SalesOrder::with('customer')->find($this->salesOrderId)?->customer
        );
    }

    public function updatedSelectedDeliveryOrderIds(): void
    {
        $ids = array_values(array_unique(array_map('intval', $this->selectedDeliveryOrderIds)));

        if ($ids === []) {
            $this->items = [];
            $this->totals = [];

            return;
        }

        $deliveryOrders = DeliveryOrder::with([
            'items.deliveryOrder',
            'items.salesOrderItem',
            'items.product',
            'items.warehouse',
            'items.unit',
        ])
            ->whereIn('id', $ids)
            ->where('sales_order_id', $this->salesOrderId)
            ->where('status', DeliveryOrder::STATUS_SHIPPED)
            ->get();

        if ($deliveryOrders->count() !== count($ids)) {
            $this->addError('selectedDeliveryOrderIds', 'Semua Surat Jalan harus berstatus Dikirim dan berasal dari SO yang dipilih.');
            $this->items = [];
            $this->totals = [];

            return;
        }

        $order = SalesOrder::findOrFail($this->salesOrderId);
        $this->items = $this->rowsFromDeliveryOrders($deliveryOrders);
        $this->totals = $this->totalsFromItems($order, $this->items);
    }

    private function rowsFromDeliveryOrders(Collection $deliveryOrders): array
    {
        return $deliveryOrders->flatMap->items
            ->groupBy('sales_order_item_id')
            ->map(function ($items) {
                $first = $items->first();
                $orderItem = $first->salesOrderItem;
                $qty = (int) $items->sum('qty_delivered');
                $orderedQty = max(1, (int) ($orderItem?->qty ?? $qty));
                $unitPrice = (int) ($orderItem?->unit_price ?? 0);
                $discount = (int) round(((int) ($orderItem?->discount_amount ?? 0)) * $qty / $orderedQty);

                return [
                    'sales_order_item_id' => $first->sales_order_item_id,
                    'product_id' => $first->product_id,
                    'warehouse_id' => $first->warehouse_id,
                    'unit_id' => $first->unit_id,
                    'conversion' => (int) ($first->conversion ?? 1),
                    'product_name' => $first->product?->name ?? '-',
                    'sku' => $first->product?->sku ?? '-',
                    'warehouse_name' => $first->warehouse?->name ?? '-',
                    'unit_name' => $first->unit?->name ?? '-',
                    'do_codes' => $items->pluck('deliveryOrder.delivery_no')->filter()->unique()->implode(', '),
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discount,
                    'line_total' => max(0, ($qty * $unitPrice) - $discount),
                ];
            })
            ->values()
            ->all();
    }

    private function totalsFromItems(SalesOrder $order, array $items): array
    {
        $subtotal = collect($items)->sum(fn (array $item) => (int) $item['qty'] * (int) $item['unit_price']);
        $discount = collect($items)->sum(fn (array $item) => (int) $item['discount_amount']);
        $taxable = max(0, $subtotal - $discount);
        $tax = $order->is_taxed ? (int) round($taxable * 0.11) : 0;
        $grandTotal = $taxable + $tax;
        $dpAmount = min((int) $order->dp_amount, $grandTotal);

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discount,
            'tax_amount' => $tax,
            'grand_total' => $grandTotal,
            'dp_amount' => $dpAmount,
            'amount_due' => max(0, $grandTotal - $dpAmount),
        ];
    }

    public function save(): void
    {
        $this->authorizeModule();
        $this->validate();

        if ($this->editingId) {
            $invoice = DB::transaction(function () {
                $invoice = SalesInvoiceModel::lockForUpdate()->findOrFail($this->editingId);
                $this->authorizeInvoice($invoice);
                abort_unless($invoice->sales_order_id === $this->salesOrderId, 422);

                $invoice->update([
                    'invoice_date' => $this->invoiceDate,
                    'due_date' => $this->dueDate ?: null,
                    'notes' => trim($this->notes) ?: null,
                ]);

                if ($invoice->status === SalesInvoiceModel::STATUS_CONFIRMED) {
                    JournalEntry::where('source_type', JournalEntry::SOURCE_SALES_INVOICE)
                        ->where('source_id', $invoice->id)
                        ->update([
                            'date' => $invoice->invoice_date,
                            'description' => 'Faktur Penjualan '.$invoice->invoice_no,
                        ]);
                }

                return $invoice;
            });

            $this->resetForm();
            $this->dispatch('toast', message: "Faktur Penjualan {$invoice->invoice_no} berhasil diubah.", type: 'success');

            return;
        }

        $deliveryOrderIds = array_values(array_unique(array_map('intval', $this->selectedDeliveryOrderIds)));
        $invoice = DB::transaction(function () use ($deliveryOrderIds) {
            $order = SalesOrder::with('items')->lockForUpdate()->findOrFail($this->salesOrderId);
            $this->authorizeSalesOrder($order);
            if (! $order->verified_at) {
                throw ValidationException::withMessages(['salesOrderId' => 'Pesanan Penjualan harus dikonfirmasi terlebih dahulu.']);
            }
            if ($order->salesInvoice()->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['salesOrderId' => 'Pesanan Penjualan ini sudah memiliki Faktur Penjualan.']);
            }

            $deliveryOrders = DeliveryOrder::with([
                'items.deliveryOrder', 'items.salesOrderItem', 'items.product', 'items.warehouse', 'items.unit',
            ])->whereIn('id', $deliveryOrderIds)->lockForUpdate()->get();
            $alreadyUsed = DeliveryOrder::query()
                ->whereIn('id', $deliveryOrderIds)
                ->whereHas('salesInvoices')
                ->exists();

            if ($deliveryOrders->count() !== count($deliveryOrderIds)
                || $deliveryOrders->contains(fn (DeliveryOrder $deliveryOrder) => $deliveryOrder->status !== DeliveryOrder::STATUS_SHIPPED
                    || $deliveryOrder->sales_order_id !== $order->id
                    || $deliveryOrder->customer_id !== $order->customer_id)
                || $alreadyUsed) {
                throw ValidationException::withMessages([
                    'selectedDeliveryOrderIds' => 'Surat Jalan harus berstatus Dikirim, berasal dari SO yang dipilih, dan belum digunakan pada invoice lain.',
                ]);
            }

            if ($deliveryOrderIds !== []) {
                $invoiceItems = $this->rowsFromDeliveryOrders($deliveryOrders);
                $totals = $this->totalsFromItems($order, $invoiceItems);
            } else {
                $invoiceItems = $order->items->map(fn ($item) => [
                    'sales_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->warehouse_id,
                    'unit_id' => $item->unit_id,
                    'qty' => (int) $item->qty,
                    'conversion' => (int) $item->conversion,
                    'unit_price' => (int) $item->unit_price,
                    'discount_amount' => (int) $item->discount_amount,
                    'line_total' => (int) $item->line_total,
                ])->all();
                $totals = $this->totalsFromOrder($order);
            }
            $invoice = SalesInvoiceModel::create([
                'invoice_no' => $this->generateCode(),
                'invoice_date' => $this->invoiceDate,
                'due_date' => $this->dueDate ?: null,
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                ...$totals,
                'paid_amount' => 0,
                'status' => SalesInvoiceModel::STATUS_DRAFT,
                'notes' => trim($this->notes) ?: null,
                'created_by' => Auth::id(),
            ]);

            foreach ($invoiceItems as $item) {
                $invoice->items()->create([
                    'sales_order_item_id' => $item['sales_order_item_id'],
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $item['warehouse_id'],
                    'unit_id' => $item['unit_id'],
                    'qty' => $item['qty'],
                    'conversion' => $item['conversion'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'line_total' => $item['line_total'],
                ]);
            }

            $invoice->deliveryOrders()->sync($deliveryOrderIds);

            return $invoice;
        });

        $this->resetForm();
        $this->dispatch('toast', message: "Faktur Penjualan {$invoice->invoice_no} berhasil dibuat sebagai draf.", type: 'success');
    }

    public function openDetail(int $id): void
    {
        $invoice = SalesInvoiceModel::with([
            'salesOrder.preOrder', 'salesOrder.salesCanvas', 'customer', 'creator', 'confirmer',
            'items.product', 'items.warehouse', 'items.unit', 'deliveryOrders',
        ])->findOrFail($id);
        $this->authorizeInvoice($invoice);
        $this->selectedInvoice = $invoice;
        $this->showDetailModal = true;
    }

    public function openConfirmInvoice(int $id): void
    {
        $this->authorizeConfirm();
        $invoice = SalesInvoiceModel::findOrFail($id);
        $this->authorizeInvoice($invoice);
        abort_unless($invoice->status === SalesInvoiceModel::STATUS_DRAFT, 422);
        $this->confirmTargetId = $id;
        $this->showConfirmModal = true;
    }

    public function confirmInvoice(): void
    {
        $this->authorizeConfirm();
        if (! $this->confirmTargetId) {
            return;
        }

        try {
            DB::transaction(function () {
                $invoice = SalesInvoiceModel::with('salesOrder')->lockForUpdate()->findOrFail($this->confirmTargetId);
                $this->authorizeInvoice($invoice);
                if ($invoice->status !== SalesInvoiceModel::STATUS_DRAFT) {
                    throw new \RuntimeException('Faktur Penjualan sudah diproses.');
                }

                $customer = Customer::lockForUpdate()->findOrFail($invoice->customer_id);
                app(CustomerCreditService::class)->assertAvailable($customer, (int) $invoice->amount_due);

                $receivableId = $this->accountId('1300', $invoice->amount_due > 0);
                $advanceId = $this->accountId('2300', $invoice->dp_amount > 0);
                $revenueId = $this->accountId('4100', ($invoice->grand_total - $invoice->tax_amount) > 0);
                $taxOutId = $this->accountId('2200', $invoice->tax_amount > 0);

                $invoice->update([
                    'status' => SalesInvoiceModel::STATUS_CONFIRMED,
                    'confirmed_at' => now(),
                    'confirmed_by' => Auth::id(),
                ]);

                $journal = JournalEntry::create([
                    'code' => $this->generateJournalCode(),
                    'date' => $invoice->invoice_date,
                    'source_type' => JournalEntry::SOURCE_SALES_INVOICE,
                    'source_id' => $invoice->id,
                    'description' => 'Faktur Penjualan '.$invoice->invoice_no,
                    'status' => JournalEntry::STATUS_POSTED,
                    'created_by' => Auth::id(),
                ]);

                if ($invoice->amount_due > 0) {
                    $journal->lines()->create(['chart_of_account_id' => $receivableId, 'debit' => $invoice->amount_due, 'credit' => 0, 'description' => 'Piutang pelanggan']);
                }
                if ($invoice->dp_amount > 0) {
                    $journal->lines()->create(['chart_of_account_id' => $advanceId, 'debit' => $invoice->dp_amount, 'credit' => 0, 'description' => 'Pemakaian uang muka pelanggan']);
                }
                $revenue = $invoice->grand_total - $invoice->tax_amount;
                if ($revenue > 0) {
                    $journal->lines()->create(['chart_of_account_id' => $revenueId, 'debit' => 0, 'credit' => $revenue, 'description' => 'Pendapatan penjualan']);
                }
                if ($invoice->tax_amount > 0) {
                    $journal->lines()->create(['chart_of_account_id' => $taxOutId, 'debit' => 0, 'credit' => $invoice->tax_amount, 'description' => 'PPN keluaran']);
                }
            });

            $this->showConfirmModal = false;
            $this->confirmTargetId = null;
            $this->dispatch('toast', message: 'Faktur Penjualan berhasil dikonfirmasi. Pembayaran Piutang sekarang dapat dibuat.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function confirmDelete(int $id): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        $invoice = SalesInvoiceModel::findOrFail($id);
        $this->authorizeInvoice($invoice);
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
        if (! $this->deleteTargetId) {
            return;
        }
        $invoice = SalesInvoiceModel::withCount(['payments', 'salesReturnInvoices'])->findOrFail($this->deleteTargetId);
        $this->authorizeInvoice($invoice);
        if ($invoice->payments_count > 0 || $invoice->sales_return_invoices_count > 0) {
            $this->dispatch('toast', message: 'Faktur tidak dapat dihapus karena sudah memiliki pembayaran atau retur.', type: 'error');

            return;
        }

        DB::transaction(function () use ($invoice) {
            JournalEntry::where('source_type', JournalEntry::SOURCE_SALES_INVOICE)
                ->where('source_id', $invoice->id)
                ->get()
                ->each->delete();
            $invoice->delete();
        });
        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Faktur Penjualan berhasil dihapus.', type: 'success');
    }

    private function totalsFromOrder(SalesOrder $order): array
    {
        return [
            'subtotal' => (int) $order->subtotal,
            'discount_total' => (int) $order->discount_total,
            'tax_amount' => (int) $order->tax_amount,
            'grand_total' => (int) $order->grand_total,
            'dp_amount' => (int) $order->dp_amount,
            'amount_due' => (int) $order->amount_due,
        ];
    }

    private function accountId(string $code, bool $required): ?int
    {
        if (! $required) {
            return null;
        }
        $id = ChartOfAccount::where('code', $code)->where('is_active', true)->where('is_postable', true)->value('id');
        if (! $id) {
            throw new \RuntimeException("Akun {$code} belum tersedia atau tidak dapat diposting.");
        }

        return (int) $id;
    }

    private function authorizeModule(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.transaction.sales-invoice'), 403);
    }

    private function authorizeConfirm(): void
    {
        abort_unless(auth()->user()?->hasPermission('sales.transaction.sales-invoice.confirm'), 403);
    }

    private function authorizeInvoice(SalesInvoiceModel $invoice): void
    {
        $invoice->loadMissing('salesOrder.salesCanvas');
        $this->authorizeSalesOrder($invoice->salesOrder);
    }

    private function authorizeSalesOrder(SalesOrder $order): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            return;
        }
        $salesmanId = auth()->user()?->salesman()->where('is_active', true)->value('id');
        $ownsConverted = $order->salesman_id === $salesmanId;
        abort_unless($ownsConverted || $order->created_by === Auth::id(), 403);
    }

    private function resetForm(): void
    {
        $this->reset([
            'showModal', 'showDetailModal', 'showConfirmModal', 'showDeleteModal',
            'confirmTargetId', 'deleteTargetId', 'editingId', 'selectedInvoice', 'invoiceNo',
            'salesOrderId', 'selectedDeliveryOrderIds', 'notes', 'items', 'totals',
        ]);
        $this->invoiceDate = now()->toDateString();
        $this->dueDate = now()->addDays(30)->toDateString();
        $this->resetErrorBag();
    }

    private function applyCustomerPaymentTerms(?Customer $customer): void
    {
        if (! $customer || ! $this->invoiceDate) {
            return;
        }

        $this->dueDate = Carbon::parse($this->invoiceDate)
            ->addDays((int) $customer->payment_terms_days)
            ->toDateString();
    }

    private function generateCode(): string
    {
        $prefix = 'FP-'.now()->format('ymd').'-';
        $last = SalesInvoiceModel::withTrashed()->where('invoice_no', 'like', $prefix.'%')->orderByDesc('invoice_no')->value('invoice_no');

        return $prefix.str_pad((string) ($last ? (int) substr($last, strlen($prefix)) + 1 : 1), 3, '0', STR_PAD_LEFT);
    }

    private function generateJournalCode(): string
    {
        $prefix = 'JE-'.now()->format('dmy').'-';
        $last = JournalEntry::withTrashed()->where('code', 'like', $prefix.'%')->orderByDesc('code')->value('code');

        return $prefix.str_pad((string) ($last ? (int) substr($last, strlen($prefix)) + 1 : 1), 3, '0', STR_PAD_LEFT);
    }

    private function accessibleSalesOrders(): Builder
    {
        $salesmanId = auth()->user()?->salesman()->where('is_active', true)->value('id');

        return SalesOrder::query()
            ->with('customer')
            ->whereNotNull('verified_at')
            ->whereDoesntHave('salesInvoice')
            ->when(! auth()->user()?->isSuperAdmin(), fn (Builder $query) => $query->where(function (Builder $query) use ($salesmanId) {
                $query->where('created_by', Auth::id())
                    ->orWhere('salesman_id', $salesmanId ?? 0)
                    ->orWhereHas('salesCanvas', fn (Builder $canvas) => $canvas->where('salesman_id', $salesmanId ?? 0));
            }));
    }

    public function render()
    {
        $salesmanId = auth()->user()?->salesman()->where('is_active', true)->value('id');
        $invoices = SalesInvoiceModel::query()
            ->with(['salesOrder', 'customer'])
            ->when(! auth()->user()?->isSuperAdmin(), fn (Builder $query) => $query->where(function (Builder $query) use ($salesmanId) {
                $query->where('created_by', Auth::id())
                    ->orWhereHas('salesOrder', fn (Builder $order) => $order->where('salesman_id', $salesmanId ?? 0))
                    ->orWhereHas('salesOrder.salesCanvas', fn (Builder $canvas) => $canvas->where('salesman_id', $salesmanId ?? 0));
            }))
            ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('invoice_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn (Builder $query) => $query->whereDate('invoice_date', '<=', $this->dateTo))
            ->when($this->search, fn (Builder $query) => $query->where(function (Builder $query) {
                $query->where('invoice_no', 'like', '%'.$this->search.'%')
                    ->orWhereHas('salesOrder', fn (Builder $order) => $order->where('order_no', 'like', '%'.$this->search.'%'))
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', '%'.$this->search.'%'));
            }))
            ->latest('invoice_date')->latest('id')->paginate($this->perPage);

        $salesOrders = $this->accessibleSalesOrders()->latest('date')->get();
        if ($this->editingId && $this->salesOrderId) {
            $currentOrder = SalesOrder::with('customer')->find($this->salesOrderId);
            if ($currentOrder && ! $salesOrders->contains('id', $currentOrder->id)) {
                $salesOrders->prepend($currentOrder);
            }
        }

        $deliveryOrders = DeliveryOrder::query()
            ->where('sales_order_id', $this->salesOrderId)
            ->where('status', DeliveryOrder::STATUS_SHIPPED)
            ->where(function (Builder $query) {
                $query->whereDoesntHave('salesInvoices');

                if ($this->selectedDeliveryOrderIds !== []) {
                    $query->orWhereIn('id', array_map('intval', $this->selectedDeliveryOrderIds));
                }
            })
            ->latest('delivery_date')
            ->latest('id')
            ->get();

        return view('livewire.sales.transaction.sales-invoice', [
            'invoices' => $invoices,
            'salesOrders' => $salesOrders,
            'deliveryOrders' => $deliveryOrders,
        ]);
    }
}
