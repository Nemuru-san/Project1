<?php

namespace App\Livewire\Users;

use App\Models\Role;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
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

    /**
     * Daftar izin modul & otorisasi (lihat config/permissions.php).
     */
    #[Computed]
    public function permissionGroups(): array
    {
        return config('permissions.groups');
    }

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
            'selectedPermissions.*' => [
                'string',
                Rule::in(array_merge(['*'], $this->availablePermissionKeys())),
            ],
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama peran wajib diisi.',
        'name.unique' => 'Nama peran sudah digunakan.',
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
        $legacyPermissionMap = [
            'purchases.report.po-outstanding' => 'purchases.report.unfinished-purchase-order',
            'purchases.report.invoice-outstanding' => 'purchases.report.unfinished-purchase-invoice',
            'sales.transaction.sales-order' => 'sales.transaction.salesOrder',
        ];

        $allowedPermissions = array_merge(['*'], $this->availablePermissionKeys());
        $this->selectedPermissions = collect($role->permissions ?? [])
            ->map(fn (string $permission) => $legacyPermissionMap[$permission] ?? $permission)
            ->filter(fn (string $permission) => in_array($permission, $allowedPermissions, true))
            ->unique()
            ->values()
            ->all();
        $this->showModal = true;
    }

    public function toggleFullAccess(): void
    {
        $this->selectedPermissions = in_array('*', $this->selectedPermissions, true)
            ? []
            : ['*'];
    }

    public function togglePermission(string $permission): void
    {
        if (in_array('*', $this->selectedPermissions, true) || ! in_array($permission, $this->availablePermissionKeys(), true)) {
            return;
        }

        if (in_array($permission, $this->selectedPermissions, true)) {
            $this->selectedPermissions = collect($this->selectedPermissions)
                ->reject(fn (string $selected) => $selected === $permission || str_starts_with($selected, $permission.'.'))
                ->values()
                ->all();

            return;
        }

        $parentPermission = collect($this->availablePermissionKeys())
            ->filter(fn (string $candidate) => $candidate !== $permission && str_starts_with($permission, $candidate.'.'))
            ->sortByDesc(fn (string $candidate) => strlen($candidate))
            ->first();

        $this->selectedPermissions = collect([
            ...$this->selectedPermissions,
            $parentPermission,
            $permission,
        ])->filter()->unique()->values()->all();
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
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');

            return;
        }

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

    private function availablePermissionKeys(): array
    {
        return collect($this->permissionGroups)
            ->flatMap(fn (array $permissions) => array_keys($permissions))
            ->values()
            ->all();
    }

    public function resetFilters(): void
    {
        $this->reset(['search']);
        $this->resetPage();
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
