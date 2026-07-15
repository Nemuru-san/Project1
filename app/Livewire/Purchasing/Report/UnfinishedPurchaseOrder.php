<?php

namespace App\Livewire\Purchasing\Report;

use App\Models\GoodsReceive;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class UnfinishedPurchaseOrder extends Component
{
    use WithPagination;

    public string $search = '';

    public string $supplierFilter = '';

    public string $statusFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 10;

    public string $sortField = 'date';

    public string $sortDirection = 'desc';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'supplierFilter', 'statusFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['code', 'date', 'status'], true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $field
            ? ($this->sortDirection === 'asc' ? 'desc' : 'asc')
            : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'supplierFilter', 'statusFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function render()
    {
        $query = PurchaseOrder::query()
            ->with([
                'supplier',
                'items' => fn ($query) => $query->withSum([
                    'goodsReceiveItems as received_qty' => fn ($query) => $query->whereHas(
                        'goodsReceive',
                        fn ($query) => $query->where('status', GoodsReceive::STATUS_RECEIVED)
                    ),
                ], 'qty_received'),
            ])
            ->whereIn('status', [
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])
            ->when($this->search, function ($query) {
                $search = '%'.$this->search.'%';
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', $search)
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', $search));
                });
            })
            ->when($this->supplierFilter, fn ($query) => $query->where('supplier_id', $this->supplierFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->dateFrom, fn ($query) => $query->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('date', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection);

        $purchaseOrders = $query->paginate($this->perPage);
        $purchaseOrders->setCollection($purchaseOrders->getCollection()->map(function (PurchaseOrder $purchaseOrder) {
            $ordered = $purchaseOrder->items->sum(fn ($item) => (int) $item->qty);
            $received = $purchaseOrder->items->sum(fn ($item) => min((int) $item->qty, (int) ($item->received_qty ?? 0)));

            $purchaseOrder->setAttribute('ordered_qty', $ordered);
            $purchaseOrder->setAttribute('received_qty', $received);
            $purchaseOrder->setAttribute('outstanding_qty', max(0, $ordered - $received));

            return $purchaseOrder;
        }));

        return view('livewire.purchasing.report.unfinished-purchase-order', [
            'purchaseOrders' => $purchaseOrders,
            'suppliers' => Supplier::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED],
        ]);
    }
}
