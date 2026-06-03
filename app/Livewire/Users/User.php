<?php

namespace App\Livewire\Users;

use App\Models\Role;
use App\Models\User as ModelsUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class User extends Component
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
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public int|string|null $role_id = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',

            'email' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'email')
                    ->whereNull('deleted_at')
                    ->ignore($this->editingId),
            ],

            'password' => [
                $this->editingId ? 'nullable' : 'required',
                'string',
                'min:8',
                'confirmed',
            ],

            'role_id' => 'required|exists:roles,id',
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama user wajib diisi.',
        'email.required' => 'Email wajib diisi.',
        // 'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email sudah digunakan.',
        'password.required' => 'Password wajib diisi.',
        'password.min' => 'Password minimal 8 karakter.',
        'password.confirmed' => 'Konfirmasi password tidak sama.',
        'role_id.required' => 'Role wajib dipilih.',
        'role_id.exists' => 'Role tidak valid.',
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
        $user = ModelsUser::findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_id = $user->role_id ?? '';
        $this->password = '';
        $this->password_confirmation = '';

        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
        ];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingId) {
            ModelsUser::findOrFail($this->editingId)->update($data);

            $this->dispatch('toast', message: 'User berhasil diperbarui.', type: 'success');
        } else {
            ModelsUser::create($data);

            $this->dispatch('toast', message: 'User berhasil ditambahkan.', type: 'success');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $user = ModelsUser::findOrFail($id);

        if ($user->trashed()) {
            return;
        }

        if ($user->id === Auth::id()) {
            $this->dispatch('toast', message: 'User yang sedang login tidak bisa dihapus.', type: 'error');
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

        $user = ModelsUser::findOrFail($this->deleteTargetId);

        if ($user->id === Auth::id()) {
            $this->showDeleteModal = false;
            $this->deleteTargetId = null;

            $this->dispatch('toast', message: 'User yang sedang login tidak bisa dihapus.', type: 'error');
            return;
        }

        $user->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'User berhasil dihapus.', type: 'success');
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->role_id = '';

        $this->resetValidation();
    }

    public function render()
    {
        $query = ModelsUser::with('role');

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhereHas('role', function ($roleQuery) {
                        $roleQuery->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        $users = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $roles = Role::orderBy('name')->get();

        return view('livewire.users.user', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }
}
