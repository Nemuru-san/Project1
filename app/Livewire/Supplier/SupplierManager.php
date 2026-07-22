<?php

namespace App\Livewire\Supplier;

use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierManager extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';

    public int $perPage = 10;

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showTrashed = false;

    // Form state
    public ?int $supplierId = null;

    public string $code = '';

    public string $name = '';

    public string $address = '';

    public string $contact = '';

    // Modal state
    public bool $showModal = false;

    public bool $showDeleteModal = false;

    public ?int $deleteTargetId = null;

    protected function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:suppliers,code,'.($this->supplierId ?? 'NULL').',id,deleted_at,NULL',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'contact' => 'required|string|max:100',
        ];
    }

    protected $messages = [
        'code.required' => 'Kode supplier wajib diisi.',
        'code.unique' => 'Kode supplier sudah digunakan.',
        'name.required' => 'Nama supplier wajib diisi.',
        'address.required' => 'Alamat wajib diisi.',
        'contact.required' => 'Kontak wajib diisi.',
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
        $supplier = Supplier::findOrFail($id);

        $this->supplierId = $supplier->id;
        $this->code = $supplier->code;
        $this->name = $supplier->name;
        $this->address = $supplier->address;
        $this->contact = $supplier->contact;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'contact' => $this->contact,
        ];

        if ($this->supplierId) {
            Supplier::findOrFail($this->supplierId)->update($data);

            $this->dispatch('toast', message: 'Supplier berhasil diperbarui.', type: 'success');
        } else {
            $data['created_by'] = Auth::user()->name ?? 'system';

            Supplier::create($data);

            $this->dispatch('toast', message: 'Supplier berhasil ditambahkan.', type: 'success');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->trashed()) {
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

        Supplier::findOrFail($this->deleteTargetId)->delete();

        $this->showDeleteModal = false;
        $this->deleteTargetId = null;

        $this->dispatch('toast', message: 'Supplier berhasil dihapus.', type: 'success');
    }

    public function export(): StreamedResponse
    {
        $suppliers = Supplier::query()
            ->when($this->showTrashed, fn ($q) => $q->withTrashed())
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%')
                        ->orWhere('contact', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        $filename = 'suppliers_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($suppliers) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Kode', 'Nama', 'Alamat', 'Kontak', 'Status', 'Dibuat Oleh', 'Tanggal Dibuat']);

            foreach ($suppliers as $s) {
                fputcsv($handle, [
                    $s->code,
                    $s->name,
                    $s->address,
                    $s->contact,
                    $s->trashed() ? 'Terhapus' : 'Aktif',
                    $s->created_by,
                    $s->created_at?->format('d/m/Y'),
                ]);
            }

            fclose($handle);
        }, $filename);
    }

    private function resetForm(): void
    {
        $this->supplierId = null;
        $this->code = '';
        $this->name = '';
        $this->address = '';
        $this->contact = '';

        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Supplier::query();

        if ($this->showTrashed) {
            $query->withTrashed();
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%')
                    ->orWhere('contact', 'like', '%'.$this->search.'%');
            });
        }

        $suppliers = $query
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.supplier.supplier-manager', compact('suppliers'));
    }
}
