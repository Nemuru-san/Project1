<?php

namespace App\Livewire\Users;

use App\Models\Role;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class RoleUser extends Component
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

    public string $name = '';

    public array $selectedPermissions = [];

    public array $permissionGroups = [
        'Platform' => [
            'dashboard' => 'Dashboard',
        ],

        'Purchasing - Master' => [
            'purchases.master.supplier' => 'Supplier',
        ],

        'Purchasing - Transaction' => [
            'purchases.transaction.purchase-order' => 'Pesanan Pembelian',
            'purchases.transaction.good-receive' => 'Penerimaan Barang',
            'purchases.transaction.purchase-invoice' => 'Faktur Pembelian',
        ],

        'Purchasing - Return' => [
            'purchases.return.purchase-return' => 'Retur Pembelian',
            'purchases.return.purchase-return-invoice' => 'Faktur Retur Pembelian',
        ],

        'Purchasing - Report' => [
            'purchases.report.po-outstanding' => 'PO Outstanding',
            'purchases.report.invoice-outstanding' => 'Invoice Outstanding',
        ],

        'Inventory - Master' => [
            'inventory.product.productMaster' => 'Master Produk',
            'inventory.product.productCategory' => 'Kategori Produk',
            'inventory.product.uom' => 'Satuan',
            'inventory.product.warehouse' => 'Gudang',
        ],

        'Inventory - Transaction' => [
            'inventory.transaction.transfer-stock' => 'Transfer Stok',
            'inventory.transaction.adjustment-in' => 'Penyesuaian Stok Masuk',
            'inventory.transaction.adjustment-out' => 'Penyesuaian Stok Keluar',
        ],

        'Inventory - Report' => [
            'inventory.report.stock-balance' => 'Saldo Stok',
            'inventory.report.stock-card' => 'Kartu Stok',
            'inventory.report.stock-movement' => 'Pergerakan Stok',
        ],

        'Sales - Master' => [
            'sales.master.customer' => 'Pelanggan',
        ],

        'Sales - Transaction' => [
            'sales.transaction.sales-order' => 'Pesanan Penjualan',
            'sales.transaction.delivery-order' => 'Surat Jalan',
            'sales.transaction.sales-invoice' => 'Faktur Penjualan',
        ],

        'Sales - Report' => [
            'sales.report.po-outstanding' => 'PO Outstanding',
            'sales.report.invoice-outstanding' => 'Invoice Outstanding',
        ],

        'Finance - Transaction' => [
            'finance.transaction.ap-payment' => 'Pembayaran Utang',
            'finance.transaction.expense' => 'Pengeluaran',
            'finance.transaction.ar-payment' => 'Pembayaran Piutang',
        ],

        'Pengguna' => [
            'user.action.user' => 'Pengguna',
            'user.action.role' => 'Peran Pengguna',
        ],
    ];

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->whereNull('deleted_at')
                    ->ignore($this->editingId),
            ],
            'selectedPermissions' => 'array',
            'selectedPermissions.*' => 'string',
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama role wajib diisi.',
        'name.unique' => 'Nama role sudah digunakan.',
    ];

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
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $role = Role::findOrFail($id);

        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions ?? [];
        $this->showModal = true;
    }

    public function toggleFullAccess(): void
    {
        $this->selectedPermissions = in_array('*', $this->selectedPermissions, true)
            ? []
            : ['*'];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'name' => $validated['name'],
            'permissions' => array_values($this->selectedPermissions),
        ];

        if ($this->editingId) {
            Role::findOrFail($this->editingId)->update($data);

            $this->dispatch('toast', message: 'Peran berhasil diperbarui.', type: 'success');
        } else {
            Role::create($data);

            $this->dispatch('toast', message: 'Peran berhasil ditambahkan.', type: 'success');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $role = Role::withCount('users')->findOrFail($id);

        if ($role->trashed()) {
            return;
        }

        if ($role->users_count > 0) {
            $this->dispatch('toast', message: 'Peran masih dipakai pengguna dan tidak dapat dihapus.', type: 'error');

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

        $role = Role::withCount('users')->findOrFail($this->deleteTargetId);

        if ($role->users_count > 0) {
            $this->showDeleteModal = false;
            $this->deleteTargetId = null;

            $this->dispatch('toast', message: 'Peran masih dipakai pengguna dan tidak dapat dihapus.', type: 'error');

            return;
        }

        $role->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'Peran berhasil dihapus.', type: 'success');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->selectedPermissions = [];

        $this->resetValidation();
    }

    public function render()
    {
        $query = Role::withCount('users');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        $roles = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.users.role-user', [
            'roles' => $roles,
        ]);
    }
}
