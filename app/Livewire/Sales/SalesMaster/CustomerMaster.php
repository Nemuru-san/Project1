<?php

namespace App\Livewire\Sales\SalesMaster;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerPic;
use Illuminate\Support\Facades\DB;
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

    public ?int $deleteTargetId = null;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $tax_number = '';

    public string $notes = '';

    public bool $is_active = true;

    /** @var array<int, array<string, mixed>> */
    public array $pics = [];

    /** @var array<int, array<string, mixed>> */
    public array $addresses = [];

    public function mount(): void
    {
        $this->resetForm();
    }

    protected function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('customers', 'code')->ignore($this->editingId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'addresses.*.label' => ['required', 'string', 'max:255'],
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
        'code.required' => 'Kode customer wajib diisi.',
        'code.unique' => 'Kode customer sudah digunakan.',
        'name.required' => 'Nama customer wajib diisi.',
        'email.email' => 'Format email customer tidak valid.',
        'pics.required' => 'Minimal satu PIC wajib diisi.',
        'pics.min' => 'Minimal satu PIC wajib diisi.',
        'pics.*.name.required' => 'Nama PIC wajib diisi.',
        'pics.*.email.email' => 'Format email PIC tidak valid.',
        'addresses.required' => 'Minimal satu alamat wajib diisi.',
        'addresses.min' => 'Minimal satu alamat wajib diisi.',
        'addresses.*.code.required' => 'Kode alamat wajib diisi.',
        'addresses.*.label.required' => 'Label alamat wajib diisi.',
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
        $this->notes = $customer->notes ?? '';
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
        $this->code = strtoupper(trim($this->code));
        foreach ($this->addresses as $index => $address) {
            $this->addresses[$index]['code'] = strtoupper(trim((string) ($address['code'] ?? '')));
        }

        $this->ensurePrimaryRows();
        $validated = $this->validate();
        $this->validateAddressCodes();

        DB::transaction(function () use ($validated): void {
            $customerData = collect($validated)->only([
                'code', 'name', 'phone', 'email', 'tax_number', 'notes', 'is_active',
            ])->all();

            if ($this->editingId) {
                $customer = Customer::findOrFail($this->editingId);
                $customer->update($customerData);
            } else {
                $customerData['created_by'] = auth()->id();
                $customer = Customer::create($customerData);
            }

            $this->syncPics($customer, $validated['pics']);
            $this->syncAddresses($customer, $validated['addresses']);
        });

        $message = $this->editingId
            ? 'Customer berhasil diperbarui.'
            : 'Customer berhasil ditambahkan.';

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

    public function delete(): void
    {
        if ($this->deleteTargetId) {
            Customer::findOrFail($this->deleteTargetId)->delete();
            $this->dispatch('toast', message: 'Customer berhasil dihapus.', type: 'success');
        }

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
    }

    public function restore(int $id): void
    {
        Customer::onlyTrashed()->findOrFail($id)->restore();
        $this->dispatch('toast', message: 'Customer berhasil dipulihkan.', type: 'success');
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
            unset($row['id']);

            if ($id) {
                $address = $customer->addresses()->whereKey($id)->firstOrFail();
                $address->update($row);
                $keptIds[] = $address->id;
            } else {
                $keptIds[] = $customer->addresses()->create($row)->id;
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
            throw ValidationException::withMessages(['pics' => 'Hanya satu PIC yang dapat dijadikan utama.']);
        }

        if (collect($this->addresses)->where('is_primary', true)->count() > 1) {
            throw ValidationException::withMessages(['addresses' => 'Hanya satu alamat yang dapat dijadikan utama.']);
        }
    }

    private function validateAddressCodes(): void
    {
        $codes = collect($this->addresses)->pluck('code')->map(fn ($code) => strtolower((string) $code));

        if ($codes->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'addresses' => 'Kode alamat tidak boleh duplikat dalam customer yang sama.',
            ]);
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->name = '';
        $this->phone = '';
        $this->email = '';
        $this->tax_number = '';
        $this->notes = '';
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
        $query = Customer::query()->withCount(['pics', 'addresses']);

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
            'customers' => $query
                ->orderBy($this->sortField, $this->sortDirection)
                ->paginate($this->perPage),
        ]);
    }
}
