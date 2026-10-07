<?php

namespace App\Livewire\Inventory\InventoryTransaction;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockOpname as StockOpnameModel;
use App\Models\Warehouse;
use App\Services\Inventory\StockOpnameService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StockOpname extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public bool $showModal = false;

    public bool $showDetailModal = false;

    public bool $showApproveModal = false;

    public bool $showCancelModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $approveTargetId = null;

    public ?int $cancelTargetId = null;

    public ?int $deleteTargetId = null;

    public ?StockOpnameModel $selectedOpname = null;

    public string $date = '';

    public ?int $warehouseId = null;

    public string $notes = '';

    public string $itemSearch = '';

    // [product_id => ['sku','name','unit','system_qty','physical_qty']]
    public array $items = [];

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorizeModule();
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorizeModule();
        $opname = StockOpnameModel::with('items.product.baseUnit')->findOrFail($id);

        if ($opname->status !== StockOpnameModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya stok opname berstatus Draf yang dapat diubah.', type: 'error');

            return;
        }

        $this->resetForm();
        $this->editingId = $opname->id;
        $this->date = $opname->date->toDateString();
        $this->warehouseId = $opname->warehouse_id;
        $this->notes = $opname->notes ?? '';
        $this->items = $opname->items->mapWithKeys(fn ($item) => [$item->product_id => [
            'sku' => $item->product?->sku ?? '-',
            'name' => $item->product?->name ?? '-',
            'unit' => $item->product?->baseUnit?->name ?? '-',
            'system_qty' => $this->systemQuantity($item->product_id),
            'physical_qty' => (int) $item->physical_qty,
        ]])->all();
        $this->showModal = true;
    }

    /**
     * Isi daftar hitung dengan semua produk aktif beserta stok sistem gudang terpilih.
     * Qty fisik awal = stok sistem, sehingga pengguna cukup mengubah yang berbeda.
     */
    public function loadProducts(): void
    {
        $this->validate(['warehouseId' => ['required', 'integer', 'exists:warehouses,id']], [
            'warehouseId.required' => 'Pilih gudang terlebih dahulu.',
        ]);

        $balances = StockBalance::where('warehouse_id', $this->warehouseId)->pluck('quantity', 'product_id');

        $this->items = Product::with('baseUnit')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Product $product) => [$product->id => [
                'sku' => $product->sku,
                'name' => $product->name,
                'unit' => $product->baseUnit?->name ?? '-',
                'system_qty' => (int) ($balances[$product->id] ?? 0),
                'physical_qty' => $this->items[$product->id]['physical_qty'] ?? (int) ($balances[$product->id] ?? 0),
            ]])
            ->all();
    }

    public function updatedWarehouseId(): void
    {
        $this->items = [];
    }

    public function save(): void
    {
        $this->authorizeModule();
        $this->validate([
            'date' => ['required', 'date'],
            'warehouseId' => ['required', 'integer', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.physical_qty' => ['required', 'integer', 'min:0'],
        ], [
            'items.required' => 'Muat daftar produk terlebih dahulu.',
            'items.*.physical_qty.min' => 'Qty fisik tidak boleh negatif.',
        ]);

        DB::transaction(function () {
            $opname = $this->editingId
                ? StockOpnameModel::lockForUpdate()->findOrFail($this->editingId)
                : new StockOpnameModel(['opname_no' => $this->generateCode(), 'status' => StockOpnameModel::STATUS_DRAFT, 'created_by' => Auth::id()]);

            if ($opname->exists && $opname->status !== StockOpnameModel::STATUS_DRAFT) {
                throw new \RuntimeException('Stok opname yang sudah diproses tidak dapat diubah.');
            }

            $opname->fill(['date' => $this->date, 'warehouse_id' => $this->warehouseId, 'notes' => trim($this->notes) ?: null])->save();
            $opname->items()->delete();

            foreach ($this->items as $productId => $item) {
                $system = (int) $item['system_qty'];
                $physical = (int) $item['physical_qty'];
                $opname->items()->create([
                    'product_id' => $productId,
                    'system_qty' => $system,
                    'physical_qty' => $physical,
                    'difference' => $physical - $system,
                ]);
            }
        });

        $this->resetForm();
        $this->dispatch('toast', message: 'Stok opname berhasil disimpan sebagai draf.', type: 'success');
    }

    public function openDetail(int $id): void
    {
        $this->selectedOpname = StockOpnameModel::withTrashed()
            ->with(['warehouse', 'creator', 'approver', 'adjustments', 'items' => fn ($query) => $query->where('difference', '!=', 0), 'items.product.baseUnit'])
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedOpname = null;
    }

    public function confirmApprove(int $id): void
    {
        if (! auth()->user()?->hasPermission('inventory.transaction.stock-opname.approve')) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk menyetujui Stok Opname.', type: 'error');

            return;
        }

        $this->approveTargetId = $id;
        $this->showApproveModal = true;
    }

    public function approve(): void
    {
        abort_unless(auth()->user()?->hasPermission('inventory.transaction.stock-opname.approve'), 403);
        if (! $this->approveTargetId) {
            return;
        }

        try {
            DB::transaction(fn () => app(StockOpnameService::class)->approve(StockOpnameModel::findOrFail($this->approveTargetId)));
            $this->dispatch('toast', message: 'Stok opname disetujui. Selisih stok sudah dibukukan sebagai penyesuaian.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }

        $this->showApproveModal = false;
        $this->approveTargetId = null;
    }

    public function confirmCancel(int $id): void
    {
        if (! auth()->user()?->canCancelTransactions()) {
            $this->dispatch('toast', message: 'Anda tidak memiliki izin untuk membatalkan Stok Opname.', type: 'error');

            return;
        }

        $this->cancelTargetId = $id;
        $this->showCancelModal = true;
    }

    public function closeCancel(): void
    {
        $this->showCancelModal = false;
        $this->cancelTargetId = null;
    }

    public function cancelOpname(): void
    {
        abort_unless(auth()->user()?->canCancelTransactions(), 403);
        if (! $this->cancelTargetId) {
            return;
        }

        try {
            DB::transaction(fn () => app(StockOpnameService::class)->cancel(StockOpnameModel::findOrFail($this->cancelTargetId)));
            $this->dispatch('toast', message: 'Stok opname dibatalkan dan penyesuaiannya dibalik.', type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }

        $this->closeCancel();
    }

    public function confirmDelete(int $id): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');

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

        $opname = StockOpnameModel::findOrFail($this->deleteTargetId);
        if ($opname->status !== StockOpnameModel::STATUS_DRAFT) {
            $this->dispatch('toast', message: 'Hanya stok opname berstatus Draf yang dapat dihapus.', type: 'error');
        } else {
            $opname->delete();
            $this->dispatch('toast', message: 'Draf stok opname berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
    }

    private function systemQuantity(int $productId): int
    {
        return (int) (StockBalance::where('warehouse_id', $this->warehouseId)->where('product_id', $productId)->value('quantity') ?? 0);
    }

    private function authorizeModule(): void
    {
        abort_unless(auth()->user()?->canAccessModule('inventory.transaction.stock-opname'), 403);
    }

    private function resetForm(): void
    {
        $this->reset(['showModal', 'editingId', 'warehouseId', 'notes', 'items', 'itemSearch']);
        $this->date = now()->toDateString();
        $this->resetErrorBag();
    }

    private function generateCode(): string
    {
        $prefix = 'OPN-'.now()->format('ym').'-';
        $last = StockOpnameModel::withTrashed()->where('opname_no', 'like', $prefix.'%')->orderByDesc('opname_no')->value('opname_no');

        return $prefix.str_pad((string) ($last ? (int) substr($last, strlen($prefix)) + 1 : 1), 3, '0', STR_PAD_LEFT);
    }

    public function render()
    {
        $visibleItems = collect($this->items)
            ->when($this->itemSearch, fn ($items) => $items->filter(fn (array $item) => str_contains(
                mb_strtolower($item['sku'].' '.$item['name']),
                mb_strtolower($this->itemSearch),
            )));

        return view('livewire.inventory.inventory-transaction.stock-opname', [
            'opnames' => StockOpnameModel::query()
                ->with('warehouse')
                ->withCount(['items as difference_count' => fn (Builder $query) => $query->where('difference', '!=', 0)])
                ->when($this->statusFilter, fn (Builder $query) => $query->where('status', $this->statusFilter))
                ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('date', '>=', $this->dateFrom))
                ->when($this->dateTo, fn (Builder $query) => $query->whereDate('date', '<=', $this->dateTo))
                ->when($this->search, fn (Builder $query) => $query->where('opname_no', 'like', '%'.$this->search.'%'))
                ->latest('date')->latest('id')
                ->paginate($this->perPage),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
            'visibleItems' => $visibleItems,
        ]);
    }
}
