<?php

namespace App\Livewire\Inventory\MasterProduct;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnit;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\WithFileUploads;

class ProductMaster extends Component
{
    use WithPagination, WithFileUploads;

    // ── Table state ────────────────────────────────────────────────────────────
    public string $search        = '';
    public int    $perPage       = 10;
    public string $sortField     = 'created_at';
    public string $sortDirection = 'desc';
    public bool   $showTrashed   = false;

    // ── Modal state ────────────────────────────────────────────────────────────
    public bool  $showModal       = false;
    public bool  $showDeleteModal = false;
    public ?int  $deleteTargetId  = null;
    public ?int  $editingId       = null;

    // ── Form fields ────────────────────────────────────────────────────────────
    public string $sku       = '';
    public string $name      = '';
    public string $desc      = '';
    public string $specification = '';
    public ?int   $category_id = null;
    public string $brand     = '';

    public $image = null;
    public ?string $existingImage = null;

    // ── Price rows (Alpine-synced via JSON) ────────────────────────────────────
    // Each row: { unit_id, conversion, price }
    public string $priceRowsJson = '[]';

    // ── Dropdown data ──────────────────────────────────────────────────────────
    public array $categories = [];
    public array $units      = [];

    // ── Modal state ───────────────────────────────────────────────
    public bool $showDetailModal = false;
    public ?array $detailProduct = null;


    // ── Lifecycle ──────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->loadDropdowns();
    }

    private function loadDropdowns(): void
    {
        $this->categories = ProductCategory::orderBy('name')
            ->get(['id', 'name'])
            ->toArray();

        $this->units = ProductUnit::orderBy('name')
            ->get(['id', 'code', 'name'])
            ->toArray();
    }

    // ── Watchers ───────────────────────────────────────────────────────────────

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    // ── Sort ───────────────────────────────────────────────────────────────────

    public function sortBy(string $field): void
    {
        $this->sortDirection = $this->sortField === $field
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->sortField = $field;
    }

    // ── Open modals ────────────────────────────────────────────────────────────

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $product = Product::with('prices.unit')->findOrFail($id);

        $this->editingId   = $id;
        $this->sku         = $product->sku      ?? '';
        $this->name        = $product->name     ?? '';
        $this->specification = $product->specification ?? '';
        $this->desc        = $product->desc     ?? '';
        $this->category_id = $product->category_id;
        $this->brand       = $product->brand    ?? '';
        $this->existingImage = $product->image;
        $this->image = null;

        $rows = $product->prices->map(fn($p) => [
            'unit_id'    => $p->unit_id,
            'conversion' => $p->conversion,
            'price'      => $p->price,
        ])->values()->toArray();

        $this->priceRowsJson = json_encode($rows);
        $this->showModal     = true;
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    // ── Save (Create / Update) ─────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products', 'sku')
                    ->whereNull('deleted_at')
                    ->ignore($this->editingId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($this->editingId),
            ],
            'specification' => 'nullable|string|max:255',
            'category_id' => 'required|integer|exists:product_categories,id',
            'brand'       => 'nullable|string|max:255',
            'desc'        => 'nullable|string',
            'image'       => 'nullable|image|max:2048',
        ]);

        $rows = json_decode($this->priceRowsJson, true) ?? [];

        if (empty($rows)) {
            $this->addError('priceRowsJson', 'Minimal harus ada 1 baris unit.');
            return;
        }

        $rows[0]['unit_id'] = 1;
        $rows[0]['conversion'] = 1;

        // Validate price rows
        foreach ($rows as $index => $row) {
            if (empty($row['unit_id'])) {
                $this->addError('priceRowsJson', "Baris " . ($index + 1) . ": Unit harus dipilih.");
                return;
            }
            if (!is_numeric($row['conversion']) || (int)$row['conversion'] <= 0) {
                $this->addError('priceRowsJson', "Baris " . ($index + 1) . ": Konversi harus angka positif.");
                return;
            }
            if (!is_numeric($row['price']) || (int) $row['price'] <= 0) {
                $this->addError('priceRowsJson', "Baris " . ($index + 1) . ": Retail Price harus lebih dari 0.");
                return;
            }
        }

        DB::transaction(function () use ($rows) {
            $data = [
                'sku'         => $this->sku ?: null,
                'name'        => $this->name,
                'specification' => $this->specification ?: null,
                'desc'        => $this->desc,
                'category_id' => $this->category_id,
                'brand'       => $this->brand,
                'created_by'  => Auth::id(),
            ];

            if ($this->image) {
                if ($this->existingImage && Storage::disk('public')->exists($this->existingImage)) {
                    Storage::disk('public')->delete($this->existingImage);
                }
                $data['image'] = $this->image->store('products', 'public');
            }

            if ($this->editingId) {
                $product = Product::findOrFail($this->editingId);
                $product->update($data);
                // Sync prices: delete old, re-insert
                $product->prices()->delete();
            } else {
                $product = Product::create($data);
            }

            foreach ($rows as $row) {
                $product->prices()->create([
                    'unit_id'    => (int) $row['unit_id'],
                    'conversion' => (int) $row['conversion'],
                    'price'      => (int) $row['price'],
                ]);
            }
        });

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $this->editingId ? 'Produk berhasil diperbarui.' : 'Produk berhasil ditambahkan.', type: 'success');
    }

    // ── Delete ─────────────────────────────────────────────────────────────────

    public function delete(): void
    {
        if (!$this->deleteTargetId) return;

        Product::findOrFail($this->deleteTargetId)->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId  = null;
        $this->dispatch('toast', message: 'Produk berhasil dihapus.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $product = Product::with(['category', 'prices.unit'])->findOrFail($id);

        $this->detailProduct = [
            'id'          => $product->id,
            'sku'         => $product->sku,
            'name'        => $product->name,
            'specification' => $product->specification,
            'desc'        => $product->desc,
            'brand'       => $product->brand,
            'image'       => $product->image,
            'category'    => $product->category?->name,
            'created_at'  => $product->created_at?->format('d M Y'),
            'prices'      => $product->prices->map(fn($p) => [
                'unit'       => $p->unit?->name . ' (' . $p->unit?->code . ')',
                'conversion' => $p->conversion,
                'price'      => $p->price,
            ])->toArray(),
        ];

        $this->showDetailModal = true;
    }

    // ── Helper ─────────────────────────────────────────────────────────────────

    private function defaultPriceRowsJson(): string
    {
        return json_encode([
            [
                'unit_id'    => 1,
                'conversion' => 1,
                'price'      => null,
                'price_display' => '',
            ],
        ]);
    }

    private function resetForm(): void
    {
        $this->sku          = '';
        $this->name         = '';
        $this->specification = '';
        $this->desc         = '';
        $this->category_id  = null;
        $this->brand        = '';

        $this->priceRowsJson = $this->defaultPriceRowsJson();

        $this->editingId    = null;
        $this->resetErrorBag();
        $this->image = null;
        $this->existingImage = null;
    }

    // ── Render ─────────────────────────────────────────────────────────────────

    public function render()
    {
        $query = Product::with('category');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->search) {
            $query->where(function ($inner) {
                $inner->where('sku', 'like', '%' . $this->search . '%')
                    ->orWhere('name', 'like', '%' . $this->search . '%')
                    ->orWhere('specification', 'like', '%' . $this->search . '%')
                    ->orWhere('brand', 'like', '%' . $this->search . '%');
            });
        }

        $products = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.inventory.master-product.product-master', [
            'products' => $products,
        ]);
    }
}
