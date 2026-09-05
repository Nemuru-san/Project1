<?php

namespace App\Livewire\Sales\SalesMaster;

use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\Salesman as SalesmanModel;
use App\Models\SalesmanTarget;
use App\Models\User;
use Carbon\Carbon;
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

    public bool $isActive = true;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public bool $showTargetModal = false;

    public ?int $deleteTargetId = null;

    public string $targetMonth = '';

    public ?int $targetSalesmanId = null;

    public string $targetSalesmanName = '';

    public int $targetAmount = 0;

    public function mount(): void
    {
        $this->targetMonth = now()->format('Y-m');
    }

    protected function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('salesmen', 'code')->ignore($this->salesmanId)],
            'name' => ['required', 'string', 'max:255'],
            'login' => ['required', 'string', 'max:255', Rule::unique('users', 'email')->ignore($this->loginUserId)],
            'password' => [$this->loginUserId ? 'nullable' : 'required', 'string', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => [$this->loginUserId ? 'nullable' : 'required', 'string'],
            'isActive' => ['boolean'],
        ];
    }

    protected $messages = [
        'code.required' => 'Kode tenaga penjual wajib diisi.',
        'code.unique' => 'Kode tenaga penjual sudah digunakan.',
        'name.required' => 'Nama tenaga penjual wajib diisi.',
        'login.required' => 'Email atau nama pengguna untuk masuk wajib diisi.',
        'login.unique' => 'Email atau nama pengguna untuk masuk sudah digunakan.',
        'password.required' => 'Kata sandi untuk masuk wajib diisi.',
        'password.min' => 'Kata sandi minimal 8 karakter.',
        'password.same' => 'Konfirmasi kata sandi tidak sama.',
        'passwordConfirmation.required' => 'Konfirmasi kata sandi wajib diisi.',
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

    public function updatedTargetMonth(): void
    {
        $this->resetPage();
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
        $this->isActive = $salesman->is_active;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function openTarget(int $id): void
    {
        $this->validate(['targetMonth' => ['required', 'date_format:Y-m']]);

        $salesman = SalesmanModel::findOrFail($id);
        $target = SalesmanTarget::query()
            ->where('salesman_id', $salesman->id)
            ->whereDate('target_month', $this->targetMonth.'-01')
            ->first();

        $this->targetSalesmanId = $salesman->id;
        $this->targetSalesmanName = $salesman->name;
        $this->targetAmount = (int) ($target?->target_amount ?? 0);
        $this->resetErrorBag('targetAmount');
        $this->showTargetModal = true;
    }

    public function saveTarget(): void
    {
        $this->validate([
            'targetMonth' => ['required', 'date_format:Y-m'],
            'targetSalesmanId' => ['required', 'integer', 'exists:salesmen,id'],
            'targetAmount' => ['required', 'integer', 'min:1'],
        ], [
            'targetAmount.required' => 'Nominal target wajib diisi.',
            'targetAmount.min' => 'Nominal target harus lebih dari Rp 0.',
        ]);

        $target = SalesmanTarget::query()
            ->where('salesman_id', $this->targetSalesmanId)
            ->whereDate('target_month', $this->targetMonth.'-01')
            ->first() ?? new SalesmanTarget([
                'salesman_id' => $this->targetSalesmanId,
                'target_month' => $this->targetMonth.'-01',
            ]);
        $target->target_amount = $this->targetAmount;
        $target->updated_by = Auth::id();
        $target->created_by ??= Auth::id();
        $target->save();

        $this->showTargetModal = false;
        $this->dispatch('toast', message: 'Target bulanan salesman berhasil disimpan.', type: 'success');
    }

    public function deleteTarget(): void
    {
        if (! $this->targetSalesmanId) {
            return;
        }

        SalesmanTarget::query()
            ->where('salesman_id', $this->targetSalesmanId)
            ->whereDate('target_month', $this->targetMonth.'-01')
            ->delete();

        $this->showTargetModal = false;
        $this->dispatch('toast', message: 'Target bulanan salesman berhasil dihapus.', type: 'success');
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
        ]);
        $this->isActive = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $period = preg_match('/^\d{4}-\d{2}$/', $this->targetMonth)
            ? Carbon::createFromFormat('Y-m', $this->targetMonth)->startOfMonth()
            : now()->startOfMonth();
        $periodEnd = $period->copy()->endOfMonth();

        $salesmen = SalesmanModel::query()
            ->with([
                'user',
                'creator',
                'monthlyTargets' => fn ($query) => $query->whereDate('target_month', $period->toDateString()),
            ])
            ->withSum([
                'salesOrders as monthly_sales_total' => fn ($query) => $query->whereHas(
                    'salesInvoice',
                    fn (Builder $invoice) => $invoice
                        ->where('status', SalesInvoice::STATUS_CONFIRMED)
                        ->whereBetween('invoice_date', [$period->toDateString(), $periodEnd->toDateString()]),
                ),
            ], 'grand_total')
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

        return view('livewire.sales.sales-master.sales-man', compact('salesmen'));
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
