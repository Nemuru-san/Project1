<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg=$event.detail.message;toastType=$event.detail.type;setTimeout(()=>toastMsg='',3500)">
    <div x-cloak x-show="toastMsg" :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[80] rounded-lg px-4 py-2 text-sm text-white"><span x-text="toastMsg"></span></div>

    <form wire:submit="save" x-on:keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
        class="my-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-gray-100 px-5 py-4 dark:border-zinc-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Identitas Perusahaan</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Dipakai pada kop dokumen cetak (faktur, surat jalan, nota, dan lain-lain).</p>
        </div>

        <div class="grid gap-5 p-5 sm:grid-cols-2">
            @foreach ([
                'name' => ['Nama Perusahaan', 'text'],
                'tax_number' => ['NPWP', 'text'],
                'phone' => ['Telepon', 'text'],
                'email' => ['Email', 'email'],
                'city' => ['Kota', 'text'],
            ] as $field => [$label, $type])
                <div>
                    <label for="company-{{ $field }}" class="mb-2 block text-sm font-medium dark:text-white">{{ $label }}</label>
                    <input id="company-{{ $field }}" wire:model="profile.{{ $field }}" type="{{ $type }}"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                    @error("profile.$field") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <div class="sm:col-span-2">
                <label for="company-address" class="mb-2 block text-sm font-medium dark:text-white">Alamat</label>
                <textarea id="company-address" wire:model="profile.address" rows="2"
                    class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></textarea>
                @error('profile.address') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="border-y border-gray-100 px-5 py-4 dark:border-zinc-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Rekening Pembayaran di Faktur</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kosongkan untuk memakai rekening bank aktif pertama dari master Rekening Bank.</p>
        </div>
        <div class="grid gap-5 p-5 sm:grid-cols-3">
            @foreach (['name' => 'Nama Bank', 'account_number' => 'Nomor Rekening', 'account_holder' => 'Atas Nama'] as $field => $label)
                <div>
                    <label for="company-bank-{{ $field }}" class="mb-2 block text-sm font-medium dark:text-white">{{ $label }}</label>
                    <input id="company-bank-{{ $field }}" wire:model="profile.bank.{{ $field }}" type="text"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                    @error("profile.bank.$field") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>

        <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-900">
            <button type="submit" wire:loading.attr="disabled"
                class="cursor-pointer rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Simpan Profil</span><span wire:loading wire:target="save">Menyimpan...</span>
            </button>
        </div>
    </form>
</div>
