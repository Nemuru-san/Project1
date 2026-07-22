<?php

namespace App\Livewire\Sales\SalesMaster;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Role;
use App\Models\Salesman as SalesmanModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class SalesMan extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showTrashed = false;

    public ?int $salesmanId = null;

    public string $code = '';

    public string $name = '';

    public ?int $loginUserId = null;

    public string $login = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public ?int $defaultCustomerId = null;

    public ?int $defaultCustomerAddressId = null;

    public bool $isActive = true;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('salesmen', 'code')->ignore($this->salesmanId)],
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', Rule::unique('users', 'email')->ignore($this->loginUserId)],
            'password' => [$this->loginUserId ? 'nullable' : 'required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => [$this->loginUserId ? 'nullable' : 'required', 'string'],
            'defaultCustomerId' => ['nullable', 'integer', 'exists:customers,id'],
            'defaultCustomerAddressId' => [
                'nullable',
                'integer',
                Rule::exists('customer_addresses', 'id')->where(
                    fn ($query) => $query
                        ->where('customer_id', $this->defaultCustomerId)
                        ->whereNull('deleted_at')
                ),
            ],
            'isActive' => ['boolean'],
        ];
    }

    protected $messages = [
        'code.required' => 'Kode salesman wajib diisi.',
        'code.unique' => 'Kode salesman sudah digunakan.',
        'name.required' => 'Nama salesman wajib diisi.',
        'login.required' => 'Email atau username login wajib diisi.',
        'login.unique' => 'Email atau username login sudah digunakan.',
        'password.required' => 'Password login wajib diisi.',
        'password.min' => 'Password minimal 8 karakter.',
        'password.same' => 'Konfirmasi password tidak sama.',
        'passwordConfirmation.required' => 'Konfirmasi password wajib diisi.',
        'defaultCustomerId.exists' => 'Customer tidak ditemukan.',
        'defaultCustomerAddressId.exists' => 'Alamat harus berasal dari customer default yang dipilih.',
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

    public function updatedDefaultCustomerId(): void
    {
        $this->defaultCustomerAddressId = null;
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, ['code', 'name', 'created_at'], true)) {
            return;
        }

        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
        $this->sortField = $field;
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $salesman = SalesmanModel::with('user')->findOrFail($id);

        $this->salesmanId = $salesman->id;
        $this->code = $salesman->code;
        $this->name = $salesman->name;
        $this->loginUserId = $salesman->user_id;
        $this->login = $salesman->user?->email ?? '';
        $this->password = '';
        $this->passwordConfirmation = '';
        $this->defaultCustomerId = $salesman->default_customer_id;
        $this->defaultCustomerAddressId = $salesman->default_customer_address_id;
        $this->isActive = $salesman->is_active;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->code = strtoupper(trim($this->code));
        $this->name = trim($this->name);
        $this->login = trim($this->login);
        $this->validate();

        $message = DB::transaction(function (): string {
            $role = $this->salesmanRole();
            $user = $this->loginUserId
                ? User::withTrashed()->findOrFail($this->loginUserId)
                : new User;

            $user->fill([
                'name' => $this->name,
                'email' => $this->login,
                'role_id' => $role->id,
            ]);

            if ($this->password !== '') {
                $user->password = Hash::make($this->password);
            }

            $user->save();

            if ($this->isActive) {
                $user->restore();
            } elseif (! $user->trashed()) {
                $user->delete();
            }

            $data = [
                'code' => $this->code,
                'name' => $this->name,
                'user_id' => $user->id,
                'default_customer_id' => $this->defaultCustomerId,
                'default_customer_address_id' => $this->defaultCustomerAddressId,
                'is_active' => $this->isActive,
            ];

            if ($this->salesmanId) {
                SalesmanModel::findOrFail($this->salesmanId)->update($data);

                return 'Salesman dan akun loginnya berhasil diperbarui.';
            }

            $data['created_by'] = Auth::id();
            SalesmanModel::create($data);

            return 'Salesman dan akun login ERP berhasil dibuat.';
        });

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        SalesmanModel::findOrFail($id);
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');

            return;
        }

        if ($this->deleteTargetId === null) {
            return;
        }

        DB::transaction(function () {
            $salesman = SalesmanModel::with('user')->findOrFail($this->deleteTargetId);
            $salesman->user?->delete();
            $salesman->delete();
        });
        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Salesman berhasil dihapus.', type: 'success');
    }

    public function restore(int $id): void
    {
        DB::transaction(function () use ($id) {
            $salesman = SalesmanModel::onlyTrashed()->with('user')->findOrFail($id);

            if ($salesman->is_active) {
                $salesman->user?->restore();
            }

            $salesman->restore();
        });

        $this->dispatch('toast', message: 'Salesman dan akun loginnya berhasil dipulihkan.', type: 'success');
    }

    private function resetForm(): void
    {
        $this->reset([
            'salesmanId',
            'code',
            'name',
            'loginUserId',
            'login',
            'password',
            'passwordConfirmation',
            'defaultCustomerId',
            'defaultCustomerAddressId',
        ]);
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $salesmen = SalesmanModel::query()
            ->with(['user', 'defaultCustomer', 'defaultCustomerAddress', 'creator'])
            ->when($this->showTrashed, fn (Builder $query) => $query->withTrashed())
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->where('code', 'like', '%'.$this->search.'%')
                        ->orWhere('name', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', fn (Builder $user) => $user->where('email', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $customers = Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);

        $customerAddresses = CustomerAddress::query()
            ->where('customer_id', $this->defaultCustomerId)
            ->orderByDesc('is_primary')
            ->orderBy('label')
            ->get(['id', 'code', 'label', 'address']);

        return view('livewire.sales.sales-master.sales-man', compact(
            'salesmen',
            'customers',
            'customerAddresses',
        ));
    }

    private function salesmanRole(): Role
    {
        $defaultPermissions = [
            'dashboard',
            'sales.master.customer',
            'sales.transaction.salesCanvas',
            'sales.transaction.salesPreOrder',
            'sales.transaction.salesOrder',
            'sales.transaction.delivery-order',
            'sales.transaction.sales-invoice',
        ];

        $role = Role::withTrashed()->where('name', 'Salesman')->first();

        if ($role) {
            $role->restore();
            $role->permissions = array_values(array_unique([
                ...($role->permissions ?? []),
                ...$defaultPermissions,
            ]));
            $role->save();

            return $role;
        }

        return Role::create([
            'name' => 'Salesman',
            'permissions' => $defaultPermissions,
        ]);
    }
}
