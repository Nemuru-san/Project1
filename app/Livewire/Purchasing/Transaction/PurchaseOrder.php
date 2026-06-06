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
    public string $search        = '';
    public string $statusFilter  = '';
    public int $perPage          = 10;
    public string $sortField     = 'created_at';
    public string $sortDirection = 'desc';
    public bool $showTrashed     = false;

    // ─── Modal state ──────────────────────────────────────────────────────────
    public bool $showModal       = false;
    public bool $showDeleteModal = false;
    public ?int $deleteTargetId  = null;

    // ─── Create form ──────────────────────────────────────────────────────────
    public string $date           = '';
    public int|string $supplier_id = '';
    public bool $tax              = false;
    public string $purchase_note  = '';
    public array $items           = [];

    // Summary
    public int $gross     = 0;
    public int $totalDisc = 0;
    public int $ppn       = 0;
    public int $nett      = 0;

    // ─── Product modal state ──────────────────────────────────────────────────
    public string $searchProduct      = '';
    public int|string $filterCategory = '';
    public array $selectedProductIds = [];

    // ─────────────────────────────────────────────────────────────────────────

    public bool $showDetail = false;
    public ?PurchaseOrderModel $selectedPO = null;
    public string $selectedStatus = '';

    public ?int $editId = null;

    protected function rules(): array
    {
        return [
            'date'               => 'required|date|before_or_equal:today',
            'supplier_id'        => 'required|exists:suppliers,id',
            'purchase_note'      => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.price_id'   => 'required|exists:product_prices,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.price'      => 'required|numeric|min:0',
            'items.*.disc'       => 'required|integer|min:0',
        ];
    }

    protected array $messages = [
        'date.before_or_equal' => 'Tanggal PO tidak boleh tanggal yang akan datang.',
        'items.min'            => 'Minimal 1 produk harus dipilih.',
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

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
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

        if (!$product || $product->prices->isEmpty()) {
            return;
        }

        $prices = $product->prices->map(fn($p) => [
            'id'        => $p->id,
            'unit_name' => $p->unit->name,
            'price'     => $p->price,
        ])->toArray();

        $defaultPrice = $product->prices->first();

        $this->items[] = [
            'product_id'   => $product->id,
            'product_code' => $product->sku,
            'product_name' => $product->name,
            'category'     => $product->category?->name ?? '-',
            'prices'       => $prices,
            'price_id'     => $defaultPrice->id,
            'unit_name'    => $defaultPrice->unit->name,
            'qty'          => 1,

            // ini dikosongkan
            'price'        => '',
            'disc'         => 0,
            'subtotal'     => 0,
        ];

        $this->recalculate();
    }

    public function addSelectedProducts(): void
    {
        foreach ($this->selectedProductIds as $productId) {
            $this->addProduct((int) $productId);
        }

        $this->selectedProductIds = [];

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
        if (count($parts) !== 2) return;

        [$index, $field] = $parts;
        $index = (int) $index;

        if (!isset($this->items[$index])) return;

        if ($field === 'price_id') {
            $priceId = (int) $value;
            $matched = collect($this->items[$index]['prices'])->firstWhere('id', $priceId);

            if ($matched) {
                $this->items[$index]['unit_name'] = $matched['unit_name'];

                // price jangan otomatis diisi
                $this->items[$index]['price'] = '';
            }
        }

        $qty   = max(1, (int) ($this->items[$index]['qty']   ?? 1));
        $price = (int) ($this->items[$index]['price'] ?: 0);
        $disc  = max(0, (int) ($this->items[$index]['disc']  ?? 0));
        $disc  = min($disc, $price * $qty);

        $this->items[$index]['qty']      = $qty;
        $this->items[$index]['disc']     = $disc;
        $this->items[$index]['subtotal'] = ($price * $qty) - $disc;

        $this->recalculate();
    }

    public function updatedTax(): void
    {
        $this->recalculate();
    }

    // ─── Kalkulasi ────────────────────────────────────────────────────────────

    private function recalculate(): void
    {
        $gross = $totalDisc = 0;

        foreach ($this->items as $item) {
            $gross     += (int) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1);
            $totalDisc += (int) ($item['disc']  ?? 0);
        }

        $afterDisc       = $gross - $totalDisc;
        $this->gross     = $gross;
        $this->totalDisc = $totalDisc;
        $this->ppn       = $this->tax ? (int) round($afterDisc * 0.11) : 0;
        $this->nett      = $afterDisc + $this->ppn;
    }

    // ─── Generate code ────────────────────────────────────────────────────────

    private function generateCode(): string
    {
        $date   = now()->format('dmy');
        $prefix = "PO-{$date}-";
        $last   = PurchaseOrderModel::withTrashed()
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function openEdit(int $id): void
    {
        $po = PurchaseOrderModel::with(['items.product.prices.unit'])->findOrFail($id);

        $this->editId        = $po->id;
        $this->date          = $po->date->toDateString();
        $this->supplier_id   = $po->supplier_id;
        $this->tax           = (bool) $po->tax;
        $this->purchase_note = $po->purchase_note ?? '';

        $this->items = $po->items->map(function ($item) {
            $product = $item->product;
            if (!$product) return null;

            $prices = $product->prices->map(fn($p) => [
                'id'        => $p->id,
                'unit_name' => $p->unit->name,
                'price'     => $p->price,
            ])->toArray();

            $price = $item->qty > 0 ? (int)(($item->total_harga + $item->disc) / $item->qty) : 0;

            $matchedPrice = collect($prices)->firstWhere('price', $price);
            $idPrice = $matchedPrice ? $matchedPrice['id'] : ($prices[0]['id'] ?? null);

            return [
                'product_id'   => $product->id,
                'product_code' => $product->sku,
                'product_name' => $product->name,
                'category'     => $product->category?->name ?? '-',
                'prices'       => $prices,
                'price_id'     => $idPrice,
                'unit_name'    => collect($prices)->firstWhere('id', $item->price_id)['unit_name'] ?? '',
                'qty'          => $item->qty,
                'price'        => $price,
                'disc'         => $item->disc,
                'subtotal'     => $item->total_harga,
            ];
        })->filter()->values()->toArray();

        $this->recalculate();
        $this->showModal = true;
    }

    // ─── Save ─────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate();

        $isEdit = (bool) $this->editId; // ← simpan dulu sebelum closeModal reset editId

        try {
            DB::transaction(function () {
                $afterDisc = $this->gross - $this->totalDisc;

                $data = [
                    'date'          => $this->date,
                    'supplier_id'   => $this->supplier_id,
                    'user_id'       => Auth::id(),
                    'total_price'   => $afterDisc,
                    'tax'           => $this->tax,
                    'purchase_note' => $this->purchase_note,
                    'gross'         => $this->gross,
                    'nett'          => $this->nett,
                ];

                if ($this->editId) {
                    $po = PurchaseOrderModel::findOrFail($this->editId);
                    $po->update($data);
                    $po->items()->delete();
                } else {
                    $data['code']   = $this->generateCode();
                    $data['status'] = PurchaseOrderModel::STATUS_DRAFT;
                    $po = PurchaseOrderModel::create($data);
                }

                foreach ($this->items as $item) {
                    $po->items()->create([
                        'product_id'  => $item['product_id'],
                        'price_id'    => $item['price_id'],
                        'qty'         => $item['qty'],
                        'total_harga' => $item['subtotal'],
                        'disc'        => $item['disc'],
                    ]);
                }
            });
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal: ' . $e->getMessage());
            return;
        }

        $this->closeModal();
        $this->dispatch('toast', message: $isEdit ? 'PO berhasil diupdate.' : 'PO berhasil dibuat.', type: 'success');
    }

    // ─── Reset ────────────────────────────────────────────────────────────────

    private function resetCreateForm(): void
    {
        $this->date          = now()->toDateString();
        $this->supplier_id   = '';
        $this->tax           = false;
        $this->purchase_note = '';
        $this->items         = [];
        $this->gross         = 0;
        $this->totalDisc     = 0;
        $this->ppn           = 0;
        $this->nett          = 0;
        $this->searchProduct  = '';
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
        $allowed = [
            PurchaseOrderModel::STATUS_DRAFT,
            PurchaseOrderModel::STATUS_APPROVED,
        ];

        $this->validate([
            'selectedStatus' => ['required', \Illuminate\Validation\Rule::in($allowed)],
        ]);

        PurchaseOrderModel::findOrFail($this->selectedPO->id)
            ->update(['status' => $this->selectedStatus]);

        session()->flash('success', 'Status berhasil diperbarui.');
        $this->closeDetail();
    }

    public function confirmDelete(int $id): void
    {
        $po = PurchaseOrderModel::findOrFail($id);

        if ($po->trashed()) {
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

        PurchaseOrderModel::findOrFail($this->deleteTargetId)->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId  = null;

        $this->dispatch('toast', message: 'Purchase Order dihapus.', type: 'success');
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $purchaseOrders = PurchaseOrderModel::with(['supplier'])
            ->when($this->showTrashed, fn($q) => $q->withTrashed())
            ->when(
                $this->search,
                fn($q) =>
                $q->where('code', 'like', "%{$this->search}%")
            )
            ->when(
                $this->statusFilter,
                fn($q) =>
                $q->where('status', $this->statusFilter)
            )
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $suppliers  = Supplier::orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();

        $products = Product::with(['category', 'prices.unit'])
            ->when(
                $this->searchProduct,
                fn($q) =>
                $q->where('name', 'like', "%{$this->searchProduct}%")
                    ->orWhere('sku', 'like', "%{$this->searchProduct}%")
            )
            ->when(
                $this->filterCategory,
                fn($q) =>
                $q->where('category_id', $this->filterCategory)
            )
            ->paginate(10, ['*'], 'productsPage');

        return view('livewire.purchasing.transaction.purchase-order', [
            'purchaseOrders' => $purchaseOrders,
            'suppliers'      => $suppliers,
            'categories'     => $categories,
            'products'       => $products,
        ]);
    }
}
