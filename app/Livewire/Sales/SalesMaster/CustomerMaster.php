<?php

namespace App\Livewire\Sales\SalesMaster;

use App\Models\AddressCode;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerPic;
use App\Models\Salesman;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerMaster extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showTrashed = false;

    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public bool $showDetailModal = false;

    public ?int $deleteTargetId = null;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $tax_number = '';

    public ?int $credit_limit = null;

    public int $payment_terms_days = 30;

    public string $notes = '';

    public ?int $default_salesman_id = null;

    public bool $is_active = true;

    /** @var array<int, array<string, mixed>> */
    public array $pics = [];

    /** @var array<int, array<string, mixed>> */
    public array $addresses = [];

    public ?array $detailCustomer = null;

    public function mount(): void
    {
        $this->resetForm();
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'integer', 'min:0'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'default_salesman_id' => ['nullable', 'integer', Rule::exists('salesmen', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNull('deleted_at'))],
            'is_active' => ['boolean'],

            'pics' => ['required', 'array', 'min:1'],
            'pics.*.id' => ['nullable', 'integer'],
            'pics.*.name' => ['required', 'string', 'max:255'],
            'pics.*.position' => ['nullable', 'string', 'max:255'],
            'pics.*.phone' => ['nullable', 'string', 'max:50'],
            'pics.*.email' => ['nullable', 'email', 'max:255'],
            'pics.*.notes' => ['nullable', 'string', 'max:500'],
            'pics.*.is_primary' => ['boolean'],

            'addresses' => ['required', 'array', 'min:1'],
            'addresses.*.id' => ['nullable', 'integer'],
            'addresses.*.code' => ['required', 'string', 'max:50'],
            'addresses.*.label' => ['nullable', 'string', 'max:255'],
            'addresses.*.address_type' => ['required', Rule::in(['billing', 'shipping', 'both'])],
            'addresses.*.address' => ['required', 'string', 'max:1000'],
            'addresses.*.province' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['nullable', 'string', 'max:255'],
            'addresses.*.district' => ['nullable', 'string', 'max:255'],
            'addresses.*.postal_code' => ['nullable', 'string', 'max:20'],
            'addresses.*.country' => ['required', 'string', 'max:255'],
            'addresses.*.is_primary' => ['boolean'],
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama pelanggan wajib diisi.',
        'email.email' => 'Format email pelanggan tidak valid.',
        'pics.required' => 'Minimal satu kontak wajib diisi.',
        'pics.min' => 'Minimal satu kontak wajib diisi.',
        'pics.*.name.required' => 'Nama kontak wajib diisi.',
        'pics.*.email.email' => 'Format email kontak tidak valid.',
        'addresses.required' => 'Minimal satu alamat wajib diisi.',
        'addresses.min' => 'Minimal satu alamat wajib diisi.',
        'addresses.*.code.required' => 'Kode alamat wajib dipilih.',
        'addresses.*.address_type.required' => 'Tipe alamat wajib dipilih.',
        'addresses.*.address.required' => 'Alamat lengkap wajib diisi.',
        'addresses.*.country.required' => 'Negara wajib diisi.',
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
        if (! in_array($field, ['code', 'name', 'is_active', 'created_at'], true)) {
            return;
        }

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
        $customer = Customer::with(['pics', 'addresses'])->findOrFail($id);

        $this->editingId = $customer->id;
        $this->code = $customer->code;
        $this->name = $customer->name;
        $this->phone = $customer->phone ?? '';
        $this->email = $customer->email ?? '';
        $this->tax_number = $customer->tax_number ?? '';
        $this->credit_limit = $customer->credit_limit;
        $this->payment_terms_days = $customer->payment_terms_days;
        $this->notes = $customer->notes ?? '';
        $this->default_salesman_id = $customer->default_salesman_id;
        $this->is_active = $customer->is_active;
        $this->pics = $customer->pics->map(fn (CustomerPic $pic) => [
            'id' => $pic->id,
            'name' => $pic->name,
            'position' => $pic->position ?? '',
            'phone' => $pic->phone ?? '',
            'email' => $pic->email ?? '',
            'notes' => $pic->notes ?? '',
            'is_primary' => $pic->is_primary,
        ])->values()->all();
        $this->addresses = $customer->addresses->map(fn (CustomerAddress $address) => [
            'id' => $address->id,
            'code' => $address->code,
            'label' => $address->label,
            'address_type' => $address->address_type,
            'address' => $address->address,
            'province' => $address->province ?? '',
            'city' => $address->city ?? '',
            'district' => $address->district ?? '',
            'postal_code' => $address->postal_code ?? '',
            'country' => $address->country,
            'is_primary' => $address->is_primary,
        ])->values()->all();

        if ($this->pics === []) {
            $this->pics = [$this->blankPic(true)];
        }

        if ($this->addresses === []) {
            $this->addresses = [$this->blankAddress(true)];
        }

        $this->showModal = true;
    }

    public function addPic(): void
    {
        $this->pics[] = $this->blankPic($this->pics === []);
    }

    public function removePic(int $index): void
    {
        if (count($this->pics) <= 1 || ! isset($this->pics[$index])) {
            return;
        }

        $wasPrimary = (bool) ($this->pics[$index]['is_primary'] ?? false);
        unset($this->pics[$index]);
        $this->pics = array_values($this->pics);

        if ($wasPrimary) {
            $this->setPrimaryPic(0);
        }
    }

    public function setPrimaryPic(int $index): void
    {
        foreach ($this->pics as $key => $pic) {
            $this->pics[$key]['is_primary'] = $key === $index;
        }
    }

    public function addAddress(): void
    {
        $this->addresses[] = $this->blankAddress($this->addresses === []);
    }

    public function removeAddress(int $index): void
    {
        if (count($this->addresses) <= 1 || ! isset($this->addresses[$index])) {
            return;
        }

        $wasPrimary = (bool) ($this->addresses[$index]['is_primary'] ?? false);
        unset($this->addresses[$index]);
        $this->addresses = array_values($this->addresses);

        if ($wasPrimary) {
            $this->setPrimaryAddress(0);
        }
    }

    public function setPrimaryAddress(int $index): void
    {
        foreach ($this->addresses as $key => $address) {
            $this->addresses[$key]['is_primary'] = $key === $index;
        }
    }

    public function save(): void
    {
        $this->ensurePrimaryRows();
        $validated = $this->validate();
        $this->ensureUniqueAddressCodes();

        DB::transaction(function () use ($validated): void {
            $customerData = collect($validated)->only([
                'name', 'phone', 'email', 'tax_number', 'credit_limit', 'payment_terms_days',
                'default_salesman_id', 'notes', 'is_active',
            ])->all();

            if ($this->editingId) {
                $customer = Customer::findOrFail($this->editingId);
                $customer->update($customerData);
            } else {
                $customerData['code'] = 'AUTO-'.Str::uuid();
                $customerData['created_by'] = auth()->id();
                $customer = Customer::create($customerData);
                $customer->update(['code' => $this->customerCode($customer->id)]);
            }

            $this->syncPics($customer, $validated['pics']);
            $this->syncAddresses($customer, $validated['addresses']);
        });

        $message = $this->editingId
            ? 'Pelanggan berhasil diperbarui.'
            : 'Pelanggan berhasil ditambahkan.';

        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        Customer::findOrFail($id);
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function openDetail(int $id): void
    {
        $customer = Customer::withTrashed()
            ->with(['pics', 'addresses', 'defaultSalesman'])
            ->findOrFail($id);

        $this->detailCustomer = [
            'code' => $customer->code,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'tax_number' => $customer->tax_number,
            'credit_limit' => $customer->credit_limit,
            'payment_terms_days' => $customer->payment_terms_days,
            'notes' => $customer->notes,
            'default_salesman' => $customer->defaultSalesman?->name,
            'is_active' => $customer->is_active,
            'is_trashed' => $customer->trashed(),
            'created_at' => $customer->created_at?->format('d M Y H:i'),
            'pics' => $customer->pics->map(fn (CustomerPic $pic) => [
                'name' => $pic->name,
                'position' => $pic->position,
                'phone' => $pic->phone,
                'email' => $pic->email,
                'notes' => $pic->notes,
                'is_primary' => $pic->is_primary,
            ])->values()->all(),
            'addresses' => $customer->addresses->map(fn (CustomerAddress $address) => [
                'code' => $address->code,
                'label' => $address->label,
                'address_type' => $address->address_type,
                'address' => $address->address,
                'province' => $address->province,
                'city' => $address->city,
                'district' => $address->district,
                'postal_code' => $address->postal_code,
                'country' => $address->country,
                'is_primary' => $address->is_primary,
            ])->values()->all(),
        ];

        $this->showDetailModal = true;
    }

    public function delete(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $this->dispatch('toast', message: 'Hanya Super Admin yang dapat menghapus data.', type: 'error');

            return;
        }

        if ($this->deleteTargetId) {
            Customer::findOrFail($this->deleteTargetId)->delete();
            $this->dispatch('toast', message: 'Pelanggan berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
    }

    public function restore(int $id): void
    {
        Customer::onlyTrashed()->findOrFail($id)->restore();
        $this->dispatch('toast', message: 'Pelanggan berhasil dipulihkan.', type: 'success');
    }

    private function syncPics(Customer $customer, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $id = $row['id'] ?? null;
            unset($row['id']);

            if ($id) {
                $pic = $customer->pics()->whereKey($id)->firstOrFail();
                $pic->update($row);
                $keptIds[] = $pic->id;
            } else {
                $keptIds[] = $customer->pics()->create($row)->id;
            }
        }

        $customer->pics()->whereNotIn('id', $keptIds)->delete();
    }

    private function syncAddresses(Customer $customer, array $rows): void
    {
        $keptIds = [];

        foreach ($rows as $row) {
            $id = $row['id'] ?? null;
            $code = trim((string) ($row['code'] ?? ''));
            unset($row['id'], $row['code']);

            if ($id) {
                $address = $customer->addresses()->whereKey($id)->firstOrFail();
                $address->update($row + ['code' => $code]);
                $keptIds[] = $address->id;
            } else {
                $address = $customer->addresses()->create($row + ['code' => $code]);
                $keptIds[] = $address->id;
            }
        }

        $customer->addresses()->whereNotIn('id', $keptIds)->delete();
    }

    private function ensurePrimaryRows(): void
    {
        if (! collect($this->pics)->contains(fn (array $pic) => (bool) ($pic['is_primary'] ?? false))) {
            $this->setPrimaryPic(0);
        }

        if (! collect($this->addresses)->contains(fn (array $address) => (bool) ($address['is_primary'] ?? false))) {
            $this->setPrimaryAddress(0);
        }

        if (collect($this->pics)->where('is_primary', true)->count() > 1) {
            throw ValidationException::withMessages(['pics' => 'Hanya satu kontak yang dapat dijadikan utama.']);
        }

        if (collect($this->addresses)->where('is_primary', true)->count() > 1) {
            throw ValidationException::withMessages(['addresses' => 'Hanya satu alamat yang dapat dijadikan utama.']);
        }
    }

    /**
     * Kode alamat boleh diisi manual, tapi tidak boleh kembar dalam satu pelanggan.
     */
    private function ensureUniqueAddressCodes(): void
    {
        $seen = [];

        foreach ($this->addresses as $index => $address) {
            $code = trim((string) ($address['code'] ?? ''));

            if ($code === '') {
                continue;
            }

            $key = mb_strtoupper($code);

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    "addresses.{$index}.code" => 'Kode alamat tidak boleh sama dengan baris lain.',
                ]);
            }

            $seen[$key] = true;

            $usedByOther = CustomerAddress::query()
                ->where('customer_id', $this->editingId ?? 0)
                ->where('code', $code)
                ->when($address['id'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
                ->exists();

            if ($usedByOther) {
                throw ValidationException::withMessages([
                    "addresses.{$index}.code" => 'Kode alamat sudah dipakai alamat lain pada pelanggan ini.',
                ]);
            }
        }
    }

    private function customerCode(int $id): string
    {
        return 'CUST-'.str_pad((string) $id, 5, '0', STR_PAD_LEFT);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->name = '';
        $this->phone = '';
        $this->email = '';
        $this->tax_number = '';
        $this->credit_limit = null;
        $this->payment_terms_days = 30;
        $this->notes = '';
        $this->default_salesman_id = null;
        $this->is_active = true;
        $this->pics = [$this->blankPic(true)];
        $this->addresses = [$this->blankAddress(true)];
        $this->resetValidation();
    }

    private function blankPic(bool $isPrimary = false): array
    {
        return [
            'id' => null,
            'name' => '',
            'position' => '',
            'phone' => '',
            'email' => '',
            'notes' => '',
            'is_primary' => $isPrimary,
        ];
    }

    private function blankAddress(bool $isPrimary = false): array
    {
        return [
            'id' => null,
            'code' => '',
            'label' => '',
            'address_type' => 'both',
            'address' => '',
            'province' => '',
            'city' => '',
            'district' => '',
            'postal_code' => '',
            'country' => 'Indonesia',
            'is_primary' => $isPrimary,
        ];
    }

    public function render()
    {
        $query = Customer::query()->with('defaultSalesman')->withCount(['pics', 'addresses']);

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->search !== '') {
            $query->where(function ($inner): void {
                $inner->where('code', 'like', '%'.$this->search.'%')
                    ->orWhere('name', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            });
        }

        return view('livewire.sales.sales-master.customer-master', [
            'addressCodes' => AddressCode::query()
                ->where('is_active', true)
                ->orderBy('code')
                ->get(),
            'customers' => $query
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->perPage),
            'salesmen' => Salesman::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }
}
