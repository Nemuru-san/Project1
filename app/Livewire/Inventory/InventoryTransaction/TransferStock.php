<?php

namespace App\Livewire\Inventory\InventoryTransaction;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductUnit;
use App\Models\StockBalance;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class TransferStock extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public bool $showTrashed = false;

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $deleteTargetId = null;

    public ?int $editingId = null;

    public string $trf_no = '';
    public string $date = '';
    public ?int $warehouse_from_id = null;
    public ?int $warehouse_to_id = null;
    public ?string $notes = null;
    public string $status = 'draft';

    public array $items = [];

    public string $productSearch = '';
    public ?int $categoryFilter = null;
    public array $selectedProductIds = [];

    public bool $showApproveModal = false;
    public ?int $approveTargetId = null;

    public bool $showDetail = false;
    public ?StockTransfer $selectedTransfer = null;

    protected function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'warehouse_from_id' => ['required', 'exists:warehouses,id'],
            'warehouse_to_id' => ['required', 'exists:warehouses,id', 'different:warehouse_from_id'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'exists:product_units,id'],
            'items.*.qty' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
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

    public function openCreate(): void
    {
        $this->resetForm();

        $this->trf_no = $this->generateCode();
        $this->date = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $transfer = StockTransfer::with(['items.product', 'items.unit'])->findOrFail($id);

        $this->editingId = $transfer->id;
        $this->trf_no = $transfer->trf_no;
        $this->date = $transfer->date->format('Y-m-d');
        $this->warehouse_from_id = $transfer->warehouse_from_id;
        $this->warehouse_to_id = $transfer->warehouse_to_id;
        $this->notes = $transfer->notes;
        $this->status = $transfer->status;

        $this->items = $transfer->items->map(fn($item) => [
            'product_id' => $item->product_id,
            'sku' => $item->product?->sku,
            'name' => $item->product?->name,
            'stock_available' => $item->stock_available,
            'qty' => $item->qty,
            'unit_id' => $item->unit_id,
            'conversion' => $item->conversion,
        ])->toArray();

        $this->showModal = true;
    }

    public function addSelectedProducts(): void
    {
        if (empty($this->selectedProductIds)) {
            $this->dispatch('toast', message: 'Pilih produk terlebih dahulu.', type: 'error');
            return;
        }

        foreach ($this->selectedProductIds as $productId) {
            $this->addProduct((int) $productId);
        }

        $this->selectedProductIds = [];

        $this->dispatch('toast', message: 'Produk berhasil ditambahkan.', type: 'success');
    }

    public function addProduct(int $productId): void
    {
        foreach ($this->items as $item) {
            if ((int) $item['product_id'] === $productId) {
                $this->dispatch('toast', message: 'Produk sudah ada di detail.', type: 'error');
                return;
            }
        }

        $product = Product::with('prices.unit')->findOrFail($productId);
        $defaultPrice = $product->prices->first();

        $stockAvailable = 0;

        if ($this->warehouse_from_id) {
            $stockAvailable = StockBalance::where('product_id', $productId)
                ->where('warehouse_id', $this->warehouse_from_id)
                ->value('quantity') ?? 0;
        }

        $this->items[] = [
            'product_id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'stock_available' => $stockAvailable,
            'qty' => $stockAvailable > 0 ? 1 : 0,
            'unit_id' => $defaultPrice?->unit_id,
            'conversion' => $defaultPrice?->conversion ?? 1,

            'unit_options' => $product->prices
                ->map(fn($price) => [
                    'unit_id' => $price->unit_id,
                    'unit_name' => $price->unit?->name ?? '-',
                    'conversion' => $price->conversion,
                ])
                ->values()
                ->toArray(),
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedWarehouseFromId(): void
    {
        foreach ($this->items as $index => $item) {
            $this->items[$index]['stock_available'] = StockBalance::where('product_id', $item['product_id'])
                ->where('warehouse_id', $this->warehouse_from_id)
                ->value('quantity') ?? 0;
        }
    }

    public function updatedItems($value, string $key): void
    {
        if (!str_ends_with($key, '.unit_id')) {
            return;
        }

        [$index] = explode('.', $key);

        $productId = $this->items[$index]['product_id'] ?? null;

        if (!$productId || !$value) {
            return;
        }

        $price = ProductPrice::where('product_id', $productId)
            ->where('unit_id', $value)
            ->first();

        $this->items[$index]['conversion'] = $price?->conversion ?? 1;
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            $transfer = StockTransfer::updateOrCreate(
                ['id' => $this->editingId],
                [
                    'trf_no' => $this->editingId ? $this->trf_no : $this->generateCode(),
                    'date' => $this->date,
                    'warehouse_from_id' => $this->warehouse_from_id,
                    'warehouse_to_id' => $this->warehouse_to_id,
                    'notes' => $this->notes,
                    'status' => $this->status,
                    'created_by' => Auth::id(),
                ]
            );

            $transfer->items()->delete();

            foreach ($this->items as $item) {
                $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'],
                    'stock_available' => $item['stock_available'] ?? 0,
                    'qty' => $item['qty'],
                    'conversion' => $item['conversion'] ?? 1,
                ]);
            }
        });

        $this->resetForm();

        $this->dispatch('toast', message: 'Transfer stock berhasil disimpan.', type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (!$this->deleteTargetId) {
            return;
        }

        StockTransfer::findOrFail($this->deleteTargetId)->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'Transfer stock berhasil dihapus.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $this->selectedTransfer = StockTransfer::with([
            'warehouseFrom',
            'warehouseTo',
            'creator',
            'items.product',
            'items.unit',
        ])
            ->withTrashed()
            ->findOrFail($id);

        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->selectedTransfer = null;
    }

    private function resetForm(): void
    {
        $this->reset([
            'showModal',
            'showDeleteModal',
            'deleteTargetId',
            'editingId',
            'trf_no',
            'warehouse_from_id',
            'warehouse_to_id',
            'notes',
            'status',
            'items',
            'productSearch',
            'categoryFilter',
            'selectedProductIds',
        ]);

        $this->date = now()->format('Y-m-d');
        $this->status = 'draft';
    }

    public function confirmApprove(int $id): void
    {
        $this->approveTargetId = $id;
        $this->showApproveModal = true;
    }

    public function cancelApprove(): void
    {
        $this->approveTargetId = null;
        $this->showApproveModal = false;
    }

    public function approve(): void
    {
        if (!$this->approveTargetId) {
            return;
        }

        DB::transaction(function () {
            $transfer = StockTransfer::with('items')->findOrFail($this->approveTargetId);

            if ($transfer->status !== 'draft') {
                return;
            }

            foreach ($transfer->items as $item) {
                $qtyBase = $item->qty * $item->conversion;

                $fromStock = StockBalance::where('warehouse_id', $transfer->warehouse_from_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$fromStock || $fromStock->quantity < $qtyBase) {
                    throw new \Exception('Stock tidak cukup untuk salah satu produk.');
                }

                $fromStock->decrement('quantity', $qtyBase);

                $toStock = StockBalance::firstOrCreate(
                    [
                        'warehouse_id' => $transfer->warehouse_to_id,
                        'product_id' => $item->product_id,
                    ],
                    [
                        'quantity' => 0,
                    ]
                );

                $toStock->increment('quantity', $qtyBase);
            }

            $transfer->update([
                'status' => 'approved',
            ]);
        });

        $this->showApproveModal = false;
        $this->approveTargetId = null;

        $this->dispatch('toast', message: 'Transfer stock berhasil di-approve.', type: 'success');
    }

    private function generateCode(): string
    {
        $date = now()->format('dmy');
        $prefix = "TRF-{$date}-";

        $last = StockTransfer::withTrashed()
            ->where('trf_no', 'like', $prefix . '%')
            ->orderByDesc('trf_no')
            ->value('trf_no');

        $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function print(int $id)
    {
        return redirect()->route('purchases.transaction.transfer-stock.print', $id);
    }

    public function render()
    {
        $transfers = StockTransfer::with(['warehouseFrom', 'warehouseTo'])
            ->when($this->showTrashed, fn($q) => $q->withTrashed())
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('trf_no', 'like', '%' . $this->search . '%')
                        ->orWhereHas('warehouseFrom', fn($w) => $w->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('warehouseTo', fn($w) => $w->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.inventory.inventory-transaction.transfer-stock', [
            'transfers' => $transfers,
            'warehouses' => Warehouse::orderBy('name')->get(),
            'units' => ProductUnit::orderBy('name')->get(),
            'products' => Product::with('category')
                ->when($this->productSearch, function ($q) {
                    $q->where(function ($query) {
                        $query->where('name', 'like', '%' . $this->productSearch . '%')
                            ->orWhere('sku', 'like', '%' . $this->productSearch . '%');
                    });
                })
                ->when($this->categoryFilter, fn($q) => $q->where('category_id', $this->categoryFilter))
                ->limit(20)
                ->get(),
            'categories' => ProductCategory::orderBy('name')->get(),
        ]);
    }
}
