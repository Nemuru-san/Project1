<?php

namespace App\Livewire\Users;

use App\Models\ActivityLog as ActivityLogModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLog extends Component
{
    use WithPagination;

    public string $search = '';

    public string $userFilter = '';

    public string $subjectFilter = '';

    public string $eventFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $perPage = 25;

    public ?ActivityLogModel $selectedLog = null;

    public function mount(): void
    {
        $this->dateFrom = now()->subDays(6)->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'userFilter', 'subjectFilter', 'eventFilter', 'dateFrom', 'dateTo', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'userFilter', 'subjectFilter', 'eventFilter']);
        $this->dateFrom = now()->subDays(6)->toDateString();
        $this->dateTo = now()->toDateString();
        $this->resetPage();
    }

    public function openDetail(int $id): void
    {
        $this->selectedLog = ActivityLogModel::with('user')->findOrFail($id);
    }

    public function closeDetail(): void
    {
        $this->selectedLog = null;
    }

    /**
     * Nama tampilan untuk jenis data (nama class model).
     */
    public static function subjectLabels(): array
    {
        return [
            'PurchaseOrder' => 'Pesanan Pembelian', 'GoodsReceive' => 'Penerimaan Barang',
            'PurchaseInvoice' => 'Faktur Pembelian', 'APPayment' => 'Pembayaran Utang',
            'PurchaseReturn' => 'Retur Pembelian', 'PurchaseReturnInvoice' => 'Faktur Retur Pembelian',
            'SalesCanvas' => 'Penjualan Kanvas', 'PreOrder' => 'Pesanan Awal', 'SalesOrder' => 'Pesanan Penjualan',
            'DeliveryOrder' => 'Surat Jalan', 'SalesInvoice' => 'Faktur Penjualan', 'SalesReturn' => 'Retur Penjualan',
            'SalesReturnInvoice' => 'Faktur Retur Penjualan', 'ArPayment' => 'Pembayaran Piutang',
            'ArDpPayment' => 'Penerimaan DP', 'Expense' => 'Pengeluaran', 'StockAdjustment' => 'Penyesuaian Stok',
            'StockTransfer' => 'Transfer Stok', 'StockOpname' => 'Stok Opname', 'Product' => 'Produk',
            'Customer' => 'Pelanggan', 'Supplier' => 'Supplier', 'Salesman' => 'Tenaga Penjualan',
            'Warehouse' => 'Gudang', 'ChartOfAccount' => 'Daftar Akun', 'BankAccount' => 'Rekening Bank',
            'User' => 'Pengguna', 'Role' => 'Peran Pengguna', 'Setting' => 'Pengaturan',
        ];
    }

    public function render()
    {
        return view('livewire.users.activity-log', [
            'logs' => ActivityLogModel::query()
                ->with('user')
                ->when($this->userFilter, fn (Builder $query) => $query->where('user_id', $this->userFilter))
                ->when($this->subjectFilter, fn (Builder $query) => $query->where('subject_type', $this->subjectFilter))
                ->when($this->eventFilter, fn (Builder $query) => $query->where('event', $this->eventFilter))
                ->when($this->dateFrom, fn (Builder $query) => $query->whereDate('created_at', '>=', $this->dateFrom))
                ->when($this->dateTo, fn (Builder $query) => $query->whereDate('created_at', '<=', $this->dateTo))
                ->when($this->search, fn (Builder $query) => $query->where('subject_label', 'like', '%'.$this->search.'%'))
                ->latest('created_at')->latest('id')
                ->paginate($this->perPage),
            'users' => User::withTrashed()->orderBy('name')->get(['id', 'name']),
            'subjectLabels' => self::subjectLabels(),
        ]);
    }
}
