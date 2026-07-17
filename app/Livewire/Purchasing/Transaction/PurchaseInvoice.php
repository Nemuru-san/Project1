<?php

namespace App\Livewire\Purchasing\Transaction;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\PurchaseInvoice as ModelsPurchaseInvoice;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseInvoice extends Component
{
    use WithPagination;

    private const ALLOWED_PURCHASE_ORDER_STATUSES = [
        PurchaseOrder::STATUS_APPROVED,
        PurchaseOrder::STATUS_RECEIVED,
        PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
    ];

    // Table state
    public string $search = '';

    public string $statusFilter = '';

    public string $paymentStatusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showTrashed = false;

    // Modal state
    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    public bool $showDetail = false;

    public ?ModelsPurchaseInvoice $selectedInvoice = null;

    public bool $showPostModal = false;

    public ?int $postTargetId = null;

    // Form state
    public ?int $invoiceId = null;

    public string $code = '';

    public string $supplier_invoice_number = '';

    public string $date = '';

    public ?int $supplier_id = null;

    public ?int $purchase_order_id = null;

    public bool $tax = false;

    public string $note = '';

    public string $top_term = '';

    public string $custom_top = '';

    public string $due_date = '';

    // Detail rows
    public array $itemRows = [];

    // Totals
    public int $sub_total = 0;

    public int $discount_total = 0;

    public int $tax_amount = 0;

    public int $grand_total = 0;

    public int $paid_amount = 0;

    public int $remaining_amount = 0;

    public int $itemPage = 1;

    public int $itemPerPage = 10;

    protected function rules(): array
    {
        return [
            'supplier_invoice_number' => 'nullable|string|max:255',
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'tax' => 'boolean',
            'note' => 'nullable|string|max:1000',
            'itemRows' => 'required|array|min:1',
        ];
    }

    protected $messages = [
        'date.required' => 'Tanggal invoice wajib diisi.',
        'purchase_order_id.required' => 'Pesanan Pembelian wajib dipilih.',
        'supplier_id.required' => 'Supplier wajib dipilih.',
        'itemRows.required' => 'Detail produk wajib ada.',
        'itemRows.min' => 'Minimal harus ada 1 detail produk.',
    ];

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search',
            'statusFilter',
            'paymentStatusFilter',
            'dateFrom',
            'dateTo',
            'showTrashed',
        ]);
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['code', 'date', 'supplier_invoice_number', 'grand_total', 'status', 'payment_status', 'created_at'], true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $field
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';

        $this->sortField = $field;
    }

    public function openCreate(): void
    {
        $this->resetForm();

        $this->code = $this->generateCode();
        $this->date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $invoice = ModelsPurchaseInvoice::with([
            'items.product.category',
            'items.unit',
            'purchaseOrder',
            'supplier',
        ])->findOrFail($id);

        if ($invoice->status !== ModelsPurchaseInvoice::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Invoice yang sudah posted tidak bisa diedit.', type: 'error');

            return;
        }

        $this->invoiceId = $invoice->id;
        $this->code = $invoice->code;
        $this->supplier_invoice_number = $invoice->supplier_invoice_number ?? '';
        $this->date = $invoice->date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->due_date = $invoice->due_date?->format('Y-m-d') ?? '';
        $this->supplier_id = $invoice->supplier_id;
        $this->purchase_order_id = $invoice->purchase_order_id;
        $this->tax = (bool) $invoice->tax;
        $this->note = $invoice->note ?? '';

        $this->sub_total = (int) $invoice->sub_total;
        $this->discount_total = (int) $invoice->discount_total;
        $this->tax_amount = (int) $invoice->tax_amount;
        $this->grand_total = (int) $invoice->grand_total;
        $this->paid_amount = (int) $invoice->paid_amount;
        $this->remaining_amount = (int) $invoice->remaining_amount;

        $this->itemRows = $invoice->items->map(function ($item) use ($invoice) {
            return [
                'purchase_order_item_id' => $item->purchase_order_item_id,
                'product_id' => $item->product_id,
                'unit_id' => $item->unit_id,
                'conversion' => (int) $item->conversion,
                'qty' => (int) $item->qty,
                'qty_base' => (int) $item->qty_base,
                'price' => (int) $item->price,
                'discount' => (int) $item->discount,
                'tax_amount' => (int) $item->tax_amount,
                'total' => (int) $item->total,

                'po_code' => $invoice->purchaseOrder?->code ?? '-',
                'product_code' => $item->product?->sku ?? $item->product?->code ?? '-',
                'product_name' => $item->product?->name ?? '-',
                'category_name' => $item->product?->category?->name ?? '-',
                'unit_name' => $item->unit?->name ?? '-',
            ];
        })->toArray();

        $this->showModal = true;
    }

    public function updatedPurchaseOrderId($value): void
    {
        if (! $value) {
            $this->supplier_id = null;
            $this->tax = false;
            $this->itemRows = [];
            $this->recalculateTotals();

            return;
        }

        $this->loadPurchaseOrder((int) $value);
    }

    public function updatedTax(): void
    {
        $this->recalculateTotals();
    }

    public function updatedItemRows($value, string $key): void
    {
        $index = explode('.', $key)[0] ?? null;

        if ($index === null || ! isset($this->itemRows[$index])) {
            return;
        }

        $this->recalculateItemRow((int) $index);
        $this->recalculateTotals();
    }

    private function recalculateItemRow(int $index): void
    {
        $qty = (int) ($this->itemRows[$index]['qty'] ?? 0);
        $price = (int) ($this->itemRows[$index]['price'] ?? 0);
        $discount = (int) ($this->itemRows[$index]['discount'] ?? 0);

        $gross = $qty * $price;
        $total = max(0, $gross - $discount);

        $this->itemRows[$index]['price'] = $price;
        $this->itemRows[$index]['discount'] = $discount;
        $this->itemRows[$index]['total'] = $total;
    }

    public function updatedTopTerm($value): void
    {
        if (! $value || ! $this->date) {
            $this->due_date = '';
            $this->custom_top = '';

            return;
        }

        if ($value === 'custom') {
            $this->due_date = '';
            $this->custom_top = '';

            return;
        }

        $this->custom_top = '';

        $this->due_date = Carbon::parse($this->date)
            ->addDays((int) $value)
            ->format('Y-m-d');
    }

    public function updatedCustomTop($value): void
    {
        if ($this->top_term === 'custom') {
            $this->due_date = $value ?: '';
        }
    }

    public function updatedDate(): void
    {
        if (! $this->top_term || $this->top_term === 'custom') {
            return;
        }

        $this->due_date = Carbon::parse($this->date)
            ->addDays((int) $this->top_term)
            ->format('Y-m-d');
    }

    private function loadPurchaseOrder(int $purchaseOrderId): void
    {
        $po = PurchaseOrder::with([
            'supplier',
            'items.product.category',
            'items.unit',
        ])
            ->whereIn('status', self::ALLOWED_PURCHASE_ORDER_STATUSES)
            ->findOrFail($purchaseOrderId);

        $this->supplier_id = $po->supplier_id;
        $this->tax = (bool) $po->tax;

        $this->itemRows = $po->items->map(function ($item) use ($po) {
            $qty = (int) $item->qty;
            $price = (int) ($item->price ?? 0);
            $discount = (int) ($item->disc ?? 0);
            $conversion = (int) ($item->conversion ?? 1);
            $qtyBase = $qty * $conversion;
            $total = max(0, ($qty * $price) - $discount);

            return [
                'purchase_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'unit_id' => $item->unit_id ?? null,
                'conversion' => $conversion,
                'qty' => $qty,
                'qty_base' => $qtyBase,
                'price' => $price,
                'discount' => $discount,
                'tax_amount' => 0,
                'total' => $total,

                'po_code' => $po->code,
                'product_code' => $item->product?->sku ?? $item->product?->code ?? '-',
                'product_name' => $item->product?->name ?? '-',
                'category_name' => $item->product?->category?->name ?? '-',
                'unit_name' => $item->unit?->name ?? '-',
            ];
        })->toArray();

        $this->itemPage = 1;

        $this->recalculateTotals();
    }

    private function recalculateTotals(): void
    {
        foreach (array_keys($this->itemRows) as $index) {
            $this->recalculateItemRow((int) $index);
        }

        $this->sub_total = collect($this->itemRows)->sum(fn ($row) => (int) ($row['total'] ?? 0));
        $this->discount_total = collect($this->itemRows)->sum(fn ($row) => (int) ($row['discount'] ?? 0));

        $this->tax_amount = $this->tax
            ? (int) round($this->sub_total * 0.11)
            : 0;

        $this->grand_total = $this->sub_total + $this->tax_amount;
        $this->remaining_amount = max(0, $this->grand_total - $this->paid_amount);
    }

    public function save(): void
    {
        $this->validate();

        $validPurchaseOrder = PurchaseOrder::whereKey($this->purchase_order_id)
            ->whereIn('status', self::ALLOWED_PURCHASE_ORDER_STATUSES)
            ->exists();

        if (! $validPurchaseOrder) {
            $this->addError('purchase_order_id', 'Pesanan Pembelian harus berstatus Disetujui, Diterima, atau Diterima Sebagian.');

            return;
        }

        if (! $this->invoiceId) {
            $exists = ModelsPurchaseInvoice::where('purchase_order_id', $this->purchase_order_id)->exists();

            if ($exists) {
                $this->addError('purchase_order_id', 'Pesanan Pembelian ini sudah memiliki Faktur Pembelian.');

                return;
            }
        }

        if ($this->invoiceId) {
            $invoice = ModelsPurchaseInvoice::findOrFail($this->invoiceId);

            if ($invoice->status !== ModelsPurchaseInvoice::STATUS_DRAFT) {
                $this->dispatch('toast', message: 'Invoice yang sudah posted tidak bisa diedit.', type: 'error');

                return;
            }
        }

        $this->recalculateTotals();

        DB::transaction(function () {
            $data = [
                'code' => $this->code ?: $this->generateCode(),
                'supplier_invoice_number' => $this->supplier_invoice_number ?: null,
                'date' => $this->date,
                'due_date' => $this->due_date ?: null,
                'supplier_id' => $this->supplier_id,
                'purchase_order_id' => $this->purchase_order_id,
                'sub_total' => $this->sub_total,
                'discount_total' => $this->discount_total,
                'tax' => $this->tax,
                'tax_amount' => $this->tax_amount,
                'grand_total' => $this->grand_total,
                'paid_amount' => $this->paid_amount,
                'remaining_amount' => $this->remaining_amount,
                'note' => $this->note ?: null,
            ];

            if ($this->invoiceId) {
                $invoice = ModelsPurchaseInvoice::findOrFail($this->invoiceId);

                $invoice->update($data);
                $invoice->items()->delete();
            } else {
                $data['status'] = ModelsPurchaseInvoice::STATUS_DRAFT;
                $data['payment_status'] = ModelsPurchaseInvoice::PAYMENT_UNPAID;
                $data['created_by'] = Auth::id();

                $invoice = ModelsPurchaseInvoice::create($data);
            }

            foreach ($this->itemRows as $row) {
                $invoice->items()->create([
                    'purchase_order_item_id' => $row['purchase_order_item_id'],
                    'product_id' => $row['product_id'],
                    'unit_id' => $row['unit_id'] ?? null,
                    'conversion' => (int) ($row['conversion'] ?? 1),
                    'qty' => (int) ($row['qty'] ?? 0),
                    'qty_base' => (int) ($row['qty_base'] ?? 0),
                    'price' => (int) ($row['price'] ?? 0),
                    'discount' => (int) ($row['discount'] ?? 0),
                    'tax_amount' => (int) ($row['tax_amount'] ?? 0),
                    'total' => (int) ($row['total'] ?? 0),
                    'note' => null,
                ]);
            }
        });

        $this->showModal = false;
        $this->resetForm();

        $this->dispatch('toast', message: 'Faktur Pembelian berhasil disimpan.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $this->selectedInvoice = ModelsPurchaseInvoice::with([
            'supplier',
            'purchaseOrder',
            'items.product.category',
            'items.unit',
        ])->withTrashed()->findOrFail($id);

        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->selectedInvoice = null;
    }

    public function postInvoice(): void
    {
        if (! $this->postTargetId) {
            return;
        }

        try {
            DB::transaction(function () {
                $invoice = ModelsPurchaseInvoice::with([
                    'items',
                    'supplier',
                    'purchaseOrder',
                ])
                    ->lockForUpdate()
                    ->findOrFail($this->postTargetId);

                if ($invoice->status !== ModelsPurchaseInvoice::STATUS_DRAFT) {
                    throw new \Exception('Hanya invoice Draft yang bisa di-post.');
                }

                if ($invoice->items->isEmpty()) {
                    throw new \Exception('Invoice tidak bisa di-post karena item masih kosong.');
                }

                if ((int) $invoice->grand_total <= 0) {
                    throw new \Exception('Invoice tidak bisa di-post karena grand total masih 0.');
                }

                $invoice->update([
                    'status' => ModelsPurchaseInvoice::STATUS_POSTED,
                ]);

                $this->updatePurchaseOrderPaymentStatus($invoice);

                $this->createPurchaseInvoiceJournal($invoice);
            });

            $this->showPostModal = false;
            $this->postTargetId = null;

            if ($this->selectedInvoice) {
                $this->selectedInvoice = ModelsPurchaseInvoice::with([
                    'supplier',
                    'purchaseOrder',
                    'items.product.category',
                    'items.unit',
                ])->find($this->selectedInvoice->id);
            }

            $this->dispatch('toast', message: 'Faktur Pembelian berhasil diposting dan entri jurnal berhasil dibuat.', type: 'success');
        } catch (\Throwable $e) {
            $this->showPostModal = false;
            $this->postTargetId = null;

            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    private function updatePurchaseOrderPaymentStatus(ModelsPurchaseInvoice $invoice): void
    {
        $invoice->refresh();

        $purchaseOrder = $invoice->purchaseOrder;

        if (! $purchaseOrder) {
            return;
        }

        $paidAmount = (int) $invoice->paid_amount;
        $grandTotal = (int) $invoice->grand_total;

        if ($paidAmount <= 0) {
            $paymentStatus = ModelsPurchaseInvoice::PAYMENT_UNPAID;
        } elseif ($paidAmount < $grandTotal) {
            $paymentStatus = ModelsPurchaseInvoice::PAYMENT_PARTIAL_PAID;
        } else {
            $paymentStatus = ModelsPurchaseInvoice::PAYMENT_PAID;
        }

        $purchaseOrder->update([
            'payment_status' => $paymentStatus,
        ]);
    }

    private function createPurchaseInvoiceJournal(ModelsPurchaseInvoice $invoice): void
    {
        $existingJournal = JournalEntry::where('source_type', JournalEntry::SOURCE_PURCHASE_INVOICE)
            ->where('source_id', $invoice->id)
            ->first();

        if ($existingJournal) {
            return;
        }

        $inventoryAccountId = $this->getChartOfAccountId('1200');
        $taxInAccountId = $this->getChartOfAccountId('1400');
        $accountPayableAccountId = $this->getChartOfAccountId('2100');

        $journal = JournalEntry::create([
            'code' => $this->generateJournalCode(),
            'date' => $invoice->date,
            'source_type' => JournalEntry::SOURCE_PURCHASE_INVOICE,
            'source_id' => $invoice->id,
            'description' => 'Faktur Pembelian '.$invoice->code,
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => Auth::id(),
        ]);

        if ((int) $invoice->sub_total > 0) {
            $journal->lines()->create([
                'chart_of_account_id' => $inventoryAccountId,
                'debit' => (int) $invoice->sub_total,
                'credit' => 0,
                'description' => 'Persediaan dari Faktur Pembelian '.$invoice->code,
            ]);
        }

        if ((int) $invoice->tax_amount > 0) {
            $journal->lines()->create([
                'chart_of_account_id' => $taxInAccountId,
                'debit' => (int) $invoice->tax_amount,
                'credit' => 0,
                'description' => 'Pajak Masukan dari Faktur Pembelian '.$invoice->code,
            ]);
        }

        $journal->lines()->create([
            'chart_of_account_id' => $accountPayableAccountId,
            'debit' => 0,
            'credit' => (int) $invoice->grand_total,
            'description' => 'Account Payable ke supplier '.($invoice->supplier?->name ?? '-'),
        ]);
    }

    private function getChartOfAccountId(string $code): int
    {
        $account = ChartOfAccount::where('code', $code)
            ->where('is_active', true)
            ->where('is_postable', true)
            ->first();

        if (! $account) {
            throw new \Exception("Chart of Account {$code} tidak ditemukan / tidak aktif / tidak postable.");
        }

        return $account->id;
    }

    private function generateJournalCode(): string
    {
        $prefix = 'JE/'.now()->format('ym').'/';

        $last = JournalEntry::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->first();

        $number = 1;

        if ($last) {
            $lastNumber = (int) str_replace($prefix, '', $last->code);
            $number = $lastNumber + 1;
        }

        return $prefix.str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function confirmPost(int $id): void
    {
        $invoice = ModelsPurchaseInvoice::findOrFail($id);

        if ($invoice->status !== ModelsPurchaseInvoice::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya invoice Draft yang bisa di-post.', type: 'error');

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

    public function confirmDelete(int $id): void
    {
        $invoice = ModelsPurchaseInvoice::findOrFail($id);

        if ($invoice->trashed()) {
            return;
        }

        if ($invoice->status !== ModelsPurchaseInvoice::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Invoice yang sudah posted tidak bisa dihapus.', type: 'error');

            return;
        }

        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! $this->deleteTargetId) {
            return;
        }

        $invoice = ModelsPurchaseInvoice::findOrFail($this->deleteTargetId);

        if ($invoice->status !== ModelsPurchaseInvoice::STATUS_DRAFT) {
            $this->showDeleteModal = false;
            $this->deleteTargetId = null;

            $this->dispatch('toast', message: 'Invoice yang sudah posted tidak bisa dihapus.', type: 'error');

            return;
        }

        $invoice->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'Faktur Pembelian berhasil dihapus.', type: 'success');
    }

    private function generateCode(): string
    {
        $date = now()->format('dmy');
        $prefix = "PIV-{$date}-";

        $last = ModelsPurchaseInvoice::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    private function resetForm(): void
    {
        $this->invoiceId = null;
        $this->code = '';
        $this->supplier_invoice_number = '';
        $this->date = now()->format('Y-m-d');
        $this->due_date = '';
        $this->supplier_id = null;
        $this->purchase_order_id = null;
        $this->tax = false;
        $this->note = '';
        $this->top_term = '';
        $this->custom_top = '';

        $this->itemRows = [];

        $this->sub_total = 0;
        $this->discount_total = 0;
        $this->tax_amount = 0;
        $this->grand_total = 0;
        $this->paid_amount = 0;
        $this->remaining_amount = 0;

        $this->resetErrorBag();
    }

    public function updatingPaymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function previousItemPage(): void
    {
        if ($this->itemPage > 1) {
            $this->itemPage--;
        }
    }

    public function nextItemPage(): void
    {
        $lastPage = max(1, (int) ceil(count($this->itemRows) / $this->itemPerPage));

        if ($this->itemPage < $lastPage) {
            $this->itemPage++;
        }
    }

    public function goToItemPage(int $page): void
    {
        $lastPage = max(1, (int) ceil(count($this->itemRows) / $this->itemPerPage));

        $this->itemPage = max(1, min($page, $lastPage));
    }

    public function print(int $id)
    {
        return redirect()->route('purchases.transaction.purchase-invoice.print', $id);
    }

    public function render()
    {
        $query = ModelsPurchaseInvoice::query()
            ->with(['supplier', 'purchaseOrder']);

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->paymentStatusFilter) {
            $query->where('payment_status', $this->paymentStatusFilter);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('supplier_invoice_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('supplier', function ($supplier) {
                        $supplier->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('code', 'like', '%'.$this->search.'%');
                    })
                    ->orWhereHas('purchaseOrder', function ($po) {
                        $po->where('code', 'like', '%'.$this->search.'%');
                    });
            });
        }

        $invoices = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $purchaseOrders = PurchaseOrder::query()
            ->with('supplier')
            ->whereIn('status', self::ALLOWED_PURCHASE_ORDER_STATUSES)
            ->whereDoesntHave('purchaseInvoices')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        if ($this->invoiceId && $this->purchase_order_id) {
            $currentPo = PurchaseOrder::with('supplier')->find($this->purchase_order_id);

            if ($currentPo && ! $purchaseOrders->contains('id', $currentPo->id)) {
                $purchaseOrders->prepend($currentPo);
            }
        }

        $itemRowsTotal = count($this->itemRows);
        $itemRowsLastPage = max(1, (int) ceil($itemRowsTotal / $this->itemPerPage));

        if ($this->itemPage > $itemRowsLastPage) {
            $this->itemPage = $itemRowsLastPage;
        }

        $visibleItemRows = collect($this->itemRows)
            ->map(function ($row, $index) {
                $row['_index'] = $index;

                return $row;
            })
            ->slice(($this->itemPage - 1) * $this->itemPerPage, $this->itemPerPage)
            ->values()
            ->toArray();

        $itemRowsFrom = $itemRowsTotal > 0
            ? (($this->itemPage - 1) * $this->itemPerPage) + 1
            : 0;

        $itemRowsTo = min($this->itemPage * $this->itemPerPage, $itemRowsTotal);

        return view('livewire.purchasing.transaction.purchase-invoice', [
            'invoices' => $invoices,
            'purchaseOrders' => $purchaseOrders,
            'visibleItemRows' => $visibleItemRows,
            'itemRowsTotal' => $itemRowsTotal,
            'itemRowsFrom' => $itemRowsFrom,
            'itemRowsTo' => $itemRowsTo,
            'itemRowsLastPage' => $itemRowsLastPage,
        ]);
    }
}
