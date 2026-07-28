<?php

namespace App\Livewire\Purchasing\Transaction;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder as PurchaseOrderModel;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrder extends Component
{
    use WithPagination;

    // ─── Table state ──────────────────────────────────────────────────────────
    public string $search = '';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showTrashed = false;

    // ─── Modal state ──────────────────────────────────────────────────────────
    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    public bool $showApproveModal = false;

    public ?int $approveTargetId = null;

    // ─── Create form ──────────────────────────────────────────────────────────
    public string $date = '';

    public int|string $supplier_id = '';

    public bool $tax = false;

    public string $purchase_note = '';

    public array $items = [];

    // Summary
    public int $gross = 0;

    public int $totalDisc = 0;

    public int $ppn = 0;

    public int $nett = 0;

    // ─── Product modal state ──────────────────────────────────────────────────
    public string $searchProduct = '';

    public $filterCategory = '';

    public array $selectedProductIds = [];

    // ─────────────────────────────────────────────────────────────────────────

    public bool $showDetail = false;

    public ?PurchaseOrderModel $selectedPO = null;

    public string $selectedStatus = '';

    public ?int $editId = null;

    protected function rules(): array
    {
        return [
            'date' => 'required|date|before_or_equal:today',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.price_id' => 'required|exists:product_prices,id',
            'items.*.unit_id' => 'nullable|exists:product_units,id',
            'items.*.conversion' => 'required|integer|min:1',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.disc' => 'required|integer|min:0',
        ];
    }

    protected function messages(): array
    {
        return [
            'date.required' => 'Tanggal wajib diisi.',
            'date.date' => 'Format tanggal tidak valid.',
            'date.before_or_equal' => 'Tanggal tidak boleh lebih dari hari ini.',

            'supplier_id.required' => 'Supplier wajib dipilih.',
            'supplier_id.exists' => 'Supplier tidak valid.',

            'items.required' => 'Detail produk wajib diisi.',
            'items.array' => 'Format detail produk tidak valid.',
            'items.min' => 'Minimal tambah 1 produk.',

            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.product_id.exists' => 'Produk tidak valid.',

            'items.*.price_id.required' => 'Satuan wajib dipilih.',
            'items.*.price_id.exists' => 'Satuan harga tidak valid.',

            'items.*.conversion.required' => 'Konversi wajib diisi.',
            'items.*.conversion.integer' => 'Konversi harus angka.',
            'items.*.conversion.min' => 'Konversi minimal 1.',

            'items.*.qty.required' => 'Qty order wajib diisi.',
            'items.*.qty.integer' => 'Qty order harus angka bulat.',
            'items.*.qty.min' => 'Qty order minimal 1.',

            'items.*.price.required' => 'Price wajib diisi.',
            'items.*.price.numeric' => 'Price harus angka.',
            'items.*.price.min' => 'Price tidak boleh minus.',

            'items.*.disc.required' => 'Disc wajib diisi. Isi 0 jika tidak ada diskon.',
            'items.*.disc.integer' => 'Disc harus angka bulat.',
            'items.*.disc.min' => 'Disc tidak boleh minus.',
        ];
    }

    protected array $messages = [
        'date.before_or_equal' => 'Tanggal PO tidak boleh tanggal yang akan datang.',
        'items.min' => 'Minimal 1 produk harus dipilih.',
    ];

    // ─── Table actions ────────────────────────────────────────────────────────

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
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo', 'showTrashed']);
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        $this->sortDirection = $this->sortField === $field
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->sortField = $field;
    }

    // ─── Modal open/close ─────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->resetCreateForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetCreateForm();
    }

    // ─── Product picker ───────────────────────────────────────────────────────

    public function updatedSearchProduct(): void
    {
        $this->resetPage('productsPage');
    }

    public function updatedFilterCategory(): void
    {
        $this->resetPage('productsPage');
    }

    public function addProduct(int $productId): void
    {
        foreach ($this->items as $item) {
            if ((int) $item['product_id'] === $productId) {
                return;
            }
        }

        $product = Product::with(['category', 'prices.unit'])->find($productId);

        if (! $product || $product->prices->isEmpty()) {
            return;
        }

        $prices = $product->prices->map(fn ($p) => [
            'id' => $p->id,
            'unit_id' => $p->unit_id,
            'unit_name' => $p->unit?->name ?? '-',
            'price' => $p->price,
            'conversion' => $p->conversion ?? $p->unit?->conversion ?? 1,
        ])->toArray();

        $defaultPrice = $product->prices->first();

        $this->items[] = [
            'product_id' => $product->id,
            'product_code' => $product->sku,
            'product_name' => $product->name,
            'category' => $product->category?->name ?? '-',
            'prices' => $prices,
            'price_id' => $defaultPrice->id,
            'unit_id' => $defaultPrice->unit_id,
            'unit_name' => $defaultPrice->unit?->name ?? '-',
            'conversion' => $defaultPrice->conversion ?? $defaultPrice->unit?->conversion ?? 1,
            'qty' => 1,
            'qty_display' => '1',
            'qty_base' => 1 * ($defaultPrice->conversion ?? $defaultPrice->unit?->conversion ?? 1),
            'price' => null,
            'price_display' => '',
            'disc' => 0,
            'disc_display' => '0',
            'subtotal' => 0,
        ];

        $this->recalculate();
    }

    public function addSelectedProducts(): void
    {
        foreach ($this->selectedProductIds as $productId) {
            $this->addProduct((int) $productId);
        }

        $this->selectedProductIds = [];

        $this->resetAddProductModal();

        $this->dispatch('toast', message: 'Produk berhasil ditambahkan.', type: 'success');
    }

    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
        $this->recalculate();
    }

    public function updatedItems(mixed $value, string $key): void
    {
        $parts = explode('.', $key);

        if (count($parts) === 1) {
            $this->normalizeItemRow((int) $parts[0]);
            $this->recalculate();

            return;
        }

        if (count($parts) !== 2) {
            return;
        }

        [$index, $field] = $parts;
        $index = (int) $index;

        if (! isset($this->items[$index])) {
            return;
        }

        if ($field === 'price_id') {
            $priceId = (int) $value;

            $matched = collect($this->items[$index]['prices'] ?? [])
                ->firstWhere('id', $priceId);

            if ($matched) {
                $this->items[$index]['unit_id'] = $matched['unit_id'] ?? null;
                $this->items[$index]['unit_name'] = $matched['unit_name'] ?? '-';
                $this->items[$index]['conversion'] = max(1, (int) ($matched['conversion'] ?? 1));
                $this->items[$index]['qty_base'] = max(1, (int) ($this->items[$index]['qty'] ?? 1)) * $this->items[$index]['conversion'];

                // harga sengaja dikosongkan karena kamu input manual
                $this->items[$index]['price'] = '';
                $this->items[$index]['price_display'] = '';
            }
        }

        $this->normalizeItemRow($index);
        $this->recalculate();
    }

    public function updatedTax(): void
    {
        $this->recalculate();
    }

    // ─── Kalkulasi ────────────────────────────────────────────────────────────

    private function recalculate(): void
    {
        $gross = 0;
        $totalDisc = 0;

        foreach ($this->items as $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $price = max(0, (int) ($item['price'] ?? 0));
            $disc = max(0, (int) ($item['disc'] ?? 0));

            $subtotalBeforeDisc = $qty * $price;
            $disc = min($disc, $subtotalBeforeDisc);

            $gross += $subtotalBeforeDisc;
            $totalDisc += $disc;
        }

        $afterDisc = max(0, $gross - $totalDisc);

        $this->gross = $gross;
        $this->totalDisc = $totalDisc;
        $this->ppn = $this->tax ? (int) round($afterDisc * 0.11) : 0;
        $this->nett = $afterDisc + $this->ppn;
    }

    private function normalizeItemRow(int $index): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $qty = max(1, (int) ($this->items[$index]['qty'] ?? 1));
        $conversion = max(1, (int) ($this->items[$index]['conversion'] ?? 1));
        $rawPrice = $this->items[$index]['price'] ?? null;
        $price = ($rawPrice === '' || $rawPrice === null)
            ? null
            : max(0, (int) $rawPrice);
        $disc = max(0, (int) ($this->items[$index]['disc'] ?? 0));

        $lineGross = $qty * (int) ($price ?? 0);
        $disc = min($disc, $lineGross);

        $this->items[$index]['qty'] = $qty;
        $this->items[$index]['qty_base'] = $qty * $conversion;
        $this->items[$index]['price'] = $price;
        $this->items[$index]['disc'] = $disc;
        $this->items[$index]['subtotal'] = $lineGross - $disc;
    }

    // ─── Generate code ────────────────────────────────────────────────────────

    private function generateCode(): string
    {
        $date = now()->format('dmy');
        $prefix = "PO-{$date}-";
        $last = PurchaseOrderModel::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function confirmApprove(int $id): void
    {
        if (! auth()->user()?->hasPermission('purchases.transaction.purchase-order.approve')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menyetujui Pesanan Pembelian.', type: 'error');

            return;
        }

        $purchaseOrder = PurchaseOrderModel::with('items')->findOrFail($id);

        if ($purchaseOrder->status !== PurchaseOrderModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya Pesanan Pembelian berstatus Draf yang dapat disetujui.', type: 'error');

            return;
        }

        if ($purchaseOrder->items->isEmpty()) {
            $this->dispatch('toast', message: 'Pesanan Pembelian tidak dapat disetujui karena rincian masih kosong.', type: 'error');

            return;
        }

        if ((int) $purchaseOrder->nett <= 0) {
            $this->dispatch('toast', message: 'Pesanan Pembelian tidak dapat disetujui karena nilai bersih masih 0.', type: 'error');

            return;
        }

        $this->approveTargetId = $id;
        $this->showApproveModal = true;
    }

    public function cancelApprove(): void
    {
        $this->showApproveModal = false;
        $this->approveTargetId = null;
    }

    public function approve(): void
    {
        if (! auth()->user()?->hasPermission('purchases.transaction.purchase-order.approve')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menyetujui Pesanan Pembelian.', type: 'error');

            return;
        }

        if (! $this->approveTargetId) {
            return;
        }

        $purchaseOrder = PurchaseOrderModel::with('items')->findOrFail($this->approveTargetId);

        if ($purchaseOrder->status !== PurchaseOrderModel::STATUS_DRAFT) {
            $this->showApproveModal = false;
            $this->approveTargetId = null;

            $this->dispatch('toast', message: 'Hanya Pesanan Pembelian berstatus Draf yang dapat disetujui.', type: 'error');

            return;
        }

        if ($purchaseOrder->items->isEmpty()) {
            $this->showApproveModal = false;
            $this->approveTargetId = null;

            $this->dispatch('toast', message: 'Pesanan Pembelian tidak dapat disetujui karena rincian masih kosong.', type: 'error');

            return;
        }

        if ((int) $purchaseOrder->nett <= 0) {
            $this->showApproveModal = false;
            $this->approveTargetId = null;

            $this->dispatch('toast', message: 'Pesanan Pembelian tidak dapat disetujui karena nilai bersih masih 0.', type: 'error');

            return;
        }

        $purchaseOrder->update([
            'status' => PurchaseOrderModel::STATUS_APPROVED,
        ]);

        $this->showApproveModal = false;
        $this->approveTargetId = null;

        $this->dispatch('toast', message: 'Pesanan Pembelian berhasil disetujui.', type: 'success');
    }

    public function openEdit(int $id): void
    {
        $po = PurchaseOrderModel::with(['items.product.prices.unit', 'items.unit'])->findOrFail($id);

        $this->editId = $po->id;
        $this->date = $po->date->toDateString();
        $this->supplier_id = $po->supplier_id;
        $this->tax = (bool) $po->tax;
        $this->purchase_note = $po->purchase_note ?? '';

        $this->items = $po->items->map(function ($item) {
            $product = $item->product;
            if (! $product) {
                return null;
            }

            $prices = $product->prices->map(fn ($p) => [
                'id' => $p->id,
                'unit_id' => $p->unit_id,
                'unit_name' => $p->unit?->name ?? '-',
                'price' => $p->price,
                'conversion' => $p->conversion ?? $p->unit?->conversion ?? 1,
            ])->toArray();

            return [
                'product_id' => $product->id,
                'product_code' => $product->sku,
                'product_name' => $product->name,
                'category' => $product->category?->name ?? '-',
                'prices' => $prices,
                'price_id' => $item->price_id,
                'unit_id' => $item->unit_id,
                'unit_name' => $item->unit?->name ?? '-',
                'conversion' => $item->conversion ?? 1,
                'qty' => $item->qty,
                'qty_base' => $item->qty_base ?? ($item->qty * ($item->conversion ?? 1)),
                'qty_display' => number_format($item->qty, 0, ',', '.'),      // ← tambah
                'price' => $item->price,
                'price_display' => $item->price ? number_format($item->price, 0, ',', '.') : '',  // ← tambah
                'disc' => $item->disc,
                'disc_display' => number_format($item->disc, 0, ',', '.'),     // ← tambah
                'subtotal' => $item->total_harga,
            ];
        })->filter()->values()->toArray();

        $this->recalculate();
        $this->showModal = true;
    }

    public function resetAddProductModal()
    {
        $this->selectedProductIds = [];
        $this->searchProduct = '';
        $this->filterCategory = '';

        $this->resetPage('productsPage'); // kalau pakai pagination dengan pageName
        // atau cukup:
        // $this->resetPage();
    }

    // ─── Save ─────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate();

        $isEdit = (bool) $this->editId;

        try {
            DB::transaction(function () {
                $this->recalculate();

                $afterDisc = max(0, $this->gross - $this->totalDisc);

                $this->ppn = $this->tax
                    ? (int) round($afterDisc * 0.11)
                    : 0;

                $this->nett = $afterDisc + $this->ppn;

                $data = [
                    'date' => $this->date,
                    'supplier_id' => $this->supplier_id,
                    'user_id' => Auth::id(),
                    'total_price' => $afterDisc,
                    'tax' => $this->tax,
                    'ppn' => $this->ppn,
                    'purchase_note' => $this->purchase_note,
                    'gross' => $this->gross,
                    'nett' => $this->nett,
                ];

                if ($this->editId) {
                    $po = PurchaseOrderModel::with(['items.goodsReceiveItems', 'items.purchaseInvoiceItems'])
                        ->findOrFail($this->editId);

                    $alreadyUsed = $po->items->contains(function ($item) {
                        return $item->goodsReceiveItems->isNotEmpty()
                            || $item->purchaseInvoiceItems->isNotEmpty();
                    });

                    if ($alreadyUsed) {
                        throw new \Exception('Pesanan Pembelian sudah dipakai di Penerimaan Barang/Faktur Pembelian sehingga rincian tidak dapat diubah.');
                    }

                    $po->update($data);
                    $po->items()->delete();
                } else {
                    $data['code'] = $this->generateCode();
                    $data['status'] = PurchaseOrderModel::STATUS_DRAFT;

                    $po = PurchaseOrderModel::create($data);
                }

                foreach ($this->items as $item) {
                    $qty = max(1, (int) ($item['qty'] ?? 1));
                    $conversion = max(1, (int) ($item['conversion'] ?? 1));
                    $price = max(0, (int) ($item['price'] ?: 0));
                    $disc = max(0, (int) ($item['disc'] ?? 0));

                    $lineGross = $qty * $price;
                    $disc = min($disc, $lineGross);
                    $subtotal = $lineGross - $disc;

                    $po->items()->create([
                        'product_id' => $item['product_id'],
                        'price_id' => $item['price_id'],
                        'unit_id' => $item['unit_id'] ?? null,
                        'qty' => $qty,
                        'price' => $price,
                        'conversion' => $conversion,
                        'qty_base' => $qty * $conversion,
                        'total_harga' => $subtotal,
                        'disc' => $disc,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal: '.$e->getMessage());

            return;
        }

        $this->closeModal();

        $this->dispatch(
            'toast',
            message: $isEdit ? 'PO berhasil diupdate.' : 'PO berhasil dibuat.',
            type: 'success'
        );
    }

    // ─── Reset ────────────────────────────────────────────────────────────────

    private function resetCreateForm(): void
    {
        $this->date = now()->toDateString();
        $this->supplier_id = '';
        $this->tax = false;
        $this->purchase_note = '';
        $this->items = [];
        $this->gross = 0;
        $this->totalDisc = 0;
        $this->ppn = 0;
        $this->nett = 0;
        $this->searchProduct = '';
        $this->filterCategory = '';
        $this->selectedProductIds = [];
        $this->resetValidation();
        $this->editId = null;
    }

    public function openDetail(int $id): void
    {
        $this->selectedPO = PurchaseOrderModel::with(['supplier', 'user', 'items.product'])->findOrFail($id);
        $this->selectedStatus = $this->selectedPO->status;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->selectedPO = null;
    }

    public function updateStatus(): void
    {
        if (! auth()->user()?->hasPermission('purchases.transaction.purchase-order.approve')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk mengubah status Pesanan Pembelian.', type: 'error');

            return;
        }

        if (! $this->selectedPO) {
            return;
        }

        $this->validate([
            'selectedStatus' => 'required|in:Draft,Approved',
        ]);

        $purchaseOrder = PurchaseOrderModel::with([
            'items',
            'goodsReceives',
            'purchaseInvoices',
        ])->findOrFail($this->selectedPO->id);

        if (! in_array($purchaseOrder->status, [
            PurchaseOrderModel::STATUS_DRAFT,
            PurchaseOrderModel::STATUS_APPROVED,
        ])) {
            $this->addError('selectedStatus', 'Status PO ini sudah tidak bisa diubah manual.');

            return;
        }

        if ($purchaseOrder->status === $this->selectedStatus) {
            return;
        }

        if (
            $purchaseOrder->status === PurchaseOrderModel::STATUS_APPROVED &&
            $this->selectedStatus === PurchaseOrderModel::STATUS_DRAFT
        ) {
            if ($purchaseOrder->goodsReceives()->exists()) {
                $this->addError('selectedStatus', 'PO tidak bisa dikembalikan ke Draft karena sudah memiliki Goods Receive.');

                return;
            }

            if ($purchaseOrder->purchaseInvoices()->exists()) {
                $this->addError('selectedStatus', 'Pesanan Pembelian tidak dapat dikembalikan ke Draf karena sudah memiliki Faktur Pembelian.');

                return;
            }
        }

        if (
            $purchaseOrder->status === PurchaseOrderModel::STATUS_DRAFT &&
            $this->selectedStatus === PurchaseOrderModel::STATUS_APPROVED
        ) {
            if ($purchaseOrder->items()->count() <= 0) {
                $this->addError('selectedStatus', 'PO tidak bisa di-approve karena item masih kosong.');

                return;
            }

            if ((int) $purchaseOrder->nett <= 0) {
                $this->addError('selectedStatus', 'PO tidak bisa di-approve karena nett masih 0.');

                return;
            }
        }

        $purchaseOrder->update([
            'status' => $this->selectedStatus,
        ]);

        $this->selectedPO = PurchaseOrderModel::with([
            'supplier',
            'user',
            'items.product',
            'goodsReceives',
            'purchaseInvoices',
        ])->find($purchaseOrder->id);

        $this->showDetail = false;
        $this->selectedPO = null;
        $this->selectedStatus = '';

        $this->dispatch('toast', message: 'Status Pesanan Pembelian berhasil diubah.', type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        if (! auth()->user()?->hasPermission('purchases.transaction.purchase-order.delete')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menghapus Pesanan Pembelian.', type: 'error');

            return;
        }

        $po = PurchaseOrderModel::findOrFail($id);

        if ($po->trashed()) {
            return;
        }

        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! auth()->user()?->hasPermission('purchases.transaction.purchase-order.delete')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menghapus Pesanan Pembelian.', type: 'error');

            return;
        }

        if (! $this->deleteTargetId) {
            return;
        }

        $purchaseOrder = PurchaseOrderModel::findOrFail($this->deleteTargetId);
        if ($purchaseOrder->status !== PurchaseOrderModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya Pesanan Pembelian berstatus Draf yang dapat dihapus.', type: 'error');

            return;
        }

        $purchaseOrder->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'Pesanan Pembelian berhasil dihapus.', type: 'success');
    }

    public function print(int $id)
    {
        return redirect()->route('purchases.transaction.purchase-order.print', $id);
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $purchaseOrders = PurchaseOrderModel::with(['supplier'])
            ->when($this->showTrashed, fn ($q) => $q->withTrashed())
            ->when(
                $this->search,
                fn ($q) => $q->where('code', 'like', "%{$this->search}%")
            )
            ->when(
                $this->statusFilter,
                fn ($q) => $q->where('status', $this->statusFilter)
            )
            ->when($this->dateFrom, fn ($q) => $q->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('date', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $suppliers = Supplier::orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();

        $products = Product::with(['category', 'prices.unit'])
            ->when(
                $this->searchProduct,
                fn ($q) => $q->where('name', 'like', "%{$this->searchProduct}%")
                    ->orWhere('sku', 'like', "%{$this->searchProduct}%")
            )
            ->when(
                $this->filterCategory,
                fn ($q) => $q->where('category_id', $this->filterCategory)
            )
            ->paginate(10, ['*'], 'productsPage');

        return view('livewire.purchasing.transaction.purchase-order', [
            'purchaseOrders' => $purchaseOrders,
            'suppliers' => $suppliers,
            'categories' => $categories,
            'products' => $products,
        ]);
    }
}
