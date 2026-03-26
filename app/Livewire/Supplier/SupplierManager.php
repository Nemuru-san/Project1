<?php

namespace App\Livewire\Supplier;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierManager extends Component
{
    use WithPagination;

    // Table state
    public string $search = '';
    public string $statusFilter = '';
    public int $perPage = 10;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Form state
    public ?int $supplierId = null;
    public string $code = '';
    public string $name = '';
    public string $address = '';
    public string $contact = '';
    public bool $status = true;

    // Modal state
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $deleteTargetId = null;
    public bool $showTrashed = false;

    protected function rules(): array
    {
        return [
            'code'    => 'required|string|max:50|unique:suppliers,code,' . ($this->supplierId ?? 'NULL') . ',id,deleted_at,NULL',
            'name'    => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'contact' => 'required|string|max:100',
            'status'  => 'boolean',
        ];
    }

    protected $messages = [
        'code.required'    => 'Kode supplier wajib diisi.',
        'code.unique'      => 'Kode supplier sudah digunakan.',
        'name.required'    => 'Nama supplier wajib diisi.',
        'address.required' => 'Alamat wajib diisi.',
        'contact.required' => 'Kontak wajib diisi.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
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
        $this->code       = $supplier->code;
        $this->name       = $supplier->name;
        $this->address    = $supplier->address;
        $this->contact    = $supplier->contact;
        $this->status     = $supplier->status;
        $this->showModal  = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'code'    => $this->code,
            'name'    => $this->name,
            'address' => $this->address,
            'contact' => $this->contact,
            'status'  => $this->status,
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
        $this->deleteTargetId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Supplier::findOrFail($this->deleteTargetId)->delete();
        $this->showDeleteModal = false;
        $this->deleteTargetId = null;
        $this->dispatch('toast', message: 'Supplier berhasil dihapus.', type: 'success');
    }

    public function restore(int $id): void
    {
        Supplier::withTrashed()->findOrFail($id)->restore();
        $this->dispatch('toast', message: 'Supplier berhasil dipulihkan.', type: 'success');
    }

    public function forceDelete(int $id): void
    {
        Supplier::withTrashed()->findOrFail($id)->forceDelete();
        $this->dispatch('toast', message: 'Supplier berhasil dihapus permanen.', type: 'success');
    }

    public function export(): StreamedResponse
    {
        $suppliers = Supplier::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->when($this->statusFilter !== '', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->showTrashed, fn($q) => $q->onlyTrashed())
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();

        $filename = 'suppliers_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($suppliers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Kode', 'Nama', 'Alamat', 'Kontak', 'Status', 'Dibuat Oleh', 'Tanggal Dibuat']);
            foreach ($suppliers as $s) {
                fputcsv($handle, [
                    $s->code,
                    $s->name,
                    $s->address,
                    $s->contact,
                    $s->status ? 'Aktif' : 'Nonaktif',
                    $s->created_by,
                    $s->created_at->format('d/m/Y'),
                ]);
            }
            fclose($handle);
        }, $filename);
    }

    private function resetForm(): void
    {
        $this->supplierId = null;
        $this->code       = '';
        $this->name       = '';
        $this->address    = '';
        $this->contact    = '';
        $this->status     = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        $suppliers = Supplier::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
                ->orWhere('contact', 'like', "%{$this->search}%"))
            ->when($this->statusFilter !== '', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->showTrashed, fn($q) => $q->onlyTrashed(), fn($q) => $q)
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.supplier.supplier-manager', compact('suppliers'));
    }
}
