<?php

namespace App\Livewire\Users;

use App\Support\CompanyProfile as CompanyProfileStore;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CompanyProfile extends Component
{
    public array $profile = [];

    public function mount(): void
    {
        $current = CompanyProfileStore::get();

        $this->profile = [
            'name' => $current['name'] ?? '',
            'address' => $current['address'] ?? '',
            'city' => $current['city'] ?? '',
            'phone' => $current['phone'] ?? '',
            'email' => $current['email'] ?? '',
            'tax_number' => $current['tax_number'] ?? '',
            'bank' => [
                'name' => $current['bank']['name'] ?? '',
                'account_number' => $current['bank']['account_number'] ?? '',
                'account_holder' => $current['bank']['account_holder'] ?? '',
            ],
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->canAccessModule('user.setting.company-profile'), 403);

        $this->validate([
            'profile.name' => ['required', 'string', 'max:150'],
            'profile.address' => ['nullable', 'string', 'max:500'],
            'profile.city' => ['nullable', 'string', 'max:100'],
            'profile.phone' => ['nullable', 'regex:/^[0-9+\-\s()]+$/', 'max:50'],
            'profile.email' => ['nullable', 'email', 'max:150'],
            'profile.tax_number' => ['nullable', 'string', 'max:50'],
            'profile.bank.name' => ['nullable', 'string', 'max:100'],
            'profile.bank.account_number' => ['nullable', 'regex:/^[0-9\-\s]+$/', 'max:50'],
            'profile.bank.account_holder' => ['nullable', 'string', 'max:150'],
        ], [
            'profile.name.required' => 'Nama perusahaan wajib diisi.',
            'profile.phone.regex' => 'Telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'profile.email.email' => 'Format email tidak valid.',
            'profile.bank.account_number.regex' => 'Nomor rekening hanya boleh berisi angka.',
        ]);

        CompanyProfileStore::save($this->profile, Auth::id());

        $this->dispatch('toast', message: 'Profil perusahaan berhasil disimpan. Kop dokumen cetak sudah memakai data terbaru.', type: 'success');
    }

    public function render()
    {
        return view('livewire.users.company-profile');
    }
}
