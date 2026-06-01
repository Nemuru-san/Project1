<?php

namespace App\Livewire\Purchasing\Transaction;

use App\Models\Inventory\Product\Product;
use App\Models\Inventory\Product\ProductCategory;
use App\Models\Purchase\Transaction\PurchaseOrder as PurchaseOrderModel;
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
    public int|string $id_supplier = '';
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

    // ─────────────────────────────────────────────────────────────────────────

    public bool $showDetail = false;
    public ?PurchaseOrderModel $selectedPO = null;
    public string $selectedStatus = '';

    public ?int $editId = null;

    protected function rules(): array
    {
        return [
            'date'               => 'required|date|before_or_equal:today',
            'id_supplier'        => 'required|exists:suppliers,id',
            'purchase_note'      => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.id_product' => 'required|exists:products,id',
            'items.*.id_price'   => 'required|exists:product_prices,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.price'      => 'required|integer|min:0',
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
        // Cegah duplikat produk (boleh beda satuan, tapi cek id_product saja)
        foreach ($this->items as $item) {
            if ($item['id_product'] === $productId) {
                return;
            }
        }

        $product = Product::with(['category', 'prices.unit'])->find($productId);
        if (!$product || $product->prices->isEmpty()) return;

        // Siapkan list prices untuk dropdown
        $prices = $product->prices->map(fn($p) => [
            'id'        => $p->id,
            'unit_name' => $p->unit->name,
            'price'     => $p->price,
        ])->toArray();

        $defaultPrice = $product->prices->first();

        $this->items[] = [
            'id_product'   => $product->id,
            'product_code' => $product->sku,
            'product_name' => $product->name,
            'category'     => $product->category?->name ?? '-',
            'prices'       => $prices,
            'id_price'     => $defaultPrice->id,
            'unit_name'    => $defaultPrice->unit->name,
            'qty'          => 1,
            'price'        => $defaultPrice->price,
            'disc'         => 0,
            'subtotal'     => $defaultPrice->price,
        ];

        $this->recalculate();
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

        // Kalau satuan berubah, update price otomatis
        if ($field === 'id_price') {
            $priceId = (int) $value;
            $matched = collect($this->items[$index]['prices'])->firstWhere('id', $priceId);
            if ($matched) {
                $this->items[$index]['price']     = $matched['price'];
                $this->items[$index]['unit_name'] = $matched['unit_name'];
            }
        }

        $qty   = max(1, (int) ($this->items[$index]['qty']   ?? 1));
        $price = (int) ($this->items[$index]['price'] ?? 0);
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
        $this->id_supplier   = $po->id_supplier;
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
                'id_product'   => $product->id,
                'product_code' => $product->sku,
                'product_name' => $product->name,
                'category'     => $product->category?->name ?? '-',
                'prices'       => $prices,
                'id_price'     => $idPrice,
                'unit_name'    => collect($prices)->firstWhere('id', $item->id_price)['unit_name'] ?? '',
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
                    'id_supplier'   => $this->id_supplier,
                    'id_user'       => Auth::id(),
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
                        'id_product'  => $item['id_product'],
                        'id_price'    => $item['id_price'],
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
        $this->id_supplier   = '';
        $this->tax           = false;
        $this->purchase_note = '';
        $this->items         = [];
        $this->gross         = 0;
        $this->totalDisc     = 0;
        $this->ppn           = 0;
        $this->nett          = 0;
        $this->searchProduct  = '';
        $this->filterCategory = '';
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
            PurchaseOrderModel::STATUS_CANCELED,
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
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deleteTargetId) {
            PurchaseOrderModel::find($this->deleteTargetId)?->delete(); // soft delete
            $this->showDeleteModal = false;
            $this->deleteTargetId  = null;
            $this->dispatch('toast', message: 'Purchase Order dihapus.', type: 'success');
        }
    }

    public function restore(int $id): void
    {
        PurchaseOrderModel::withTrashed()->find($id)?->restore();
        $this->dispatch('toast', message: 'Purchase Order dipulihkan.', type: 'success');
    }

    public function forceDelete(int $id): void
    {
        PurchaseOrderModel::withTrashed()->find($id)?->forceDelete();
        $this->dispatch('toast', message: 'Purchase Order dihapus permanen.', type: 'success');
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $purchaseOrders = PurchaseOrderModel::with(['supplier'])
            ->when($this->showTrashed, fn($q) => $q->onlyTrashed())
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
        $categories = ProductCategory::where('is_active', true)->orderBy('name')->get();

        $products = Product::with(['category', 'prices.unit'])
            ->where('is_active', true)
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
