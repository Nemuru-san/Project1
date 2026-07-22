<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- FILTER BAR --}}
    <div
        class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 my-4 dark:bg-zinc-900">

        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
            {{-- Search --}}
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg aria-hidden="true" class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5 placeholder-gray-400"
                    placeholder="Cari nama, bank, nomor rekening..." />
            </div>

            {{-- Per Page --}}
            <select wire:model.live="perPage"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 w-full sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>

            {{-- Show Trashed --}}
            <label class="flex items-center gap-2 text-sm dark:text-gray-300 cursor-pointer whitespace-nowrap">
                <input type="checkbox" wire:model.live="showTrashed"
                    class="w-4 h-4 rounded border-gray-600 dark:bg-zinc-800 text-blue-600">
                Tampilkan Terhapus
            </label>

            {{-- Tambah --}}
            <button wire:click="openCreate"
                class="inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 border border-transparent text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap cursor-pointer sm:ml-auto sm:w-auto w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Rekening Bank
            </button>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-sm font-bold uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('name')">
                        <div class="flex items-center gap-1">
                            Nama
                            @if ($sortField === 'name')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('bank_name')">
                        <div class="flex items-center gap-1">
                            Bank
                            @if ($sortField === 'bank_name')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4">No. Rekening</th>
                    <th class="px-4 py-4">Pemilik</th>
                    <th class="px-4 py-4">CoA</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>

            <tbody class="dark:bg-zinc-950 text-sm">
                @forelse ($bankAccounts as $bank)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $bank->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $bank->name }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $bank->bank_name ?: '-' }}
                        </td>

                        <td class="px-4 py-4 font-mono">
                            {{ $bank->account_number ?: '-' }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $bank->account_holder ?: '-' }}
                        </td>

                        <td class="px-4 py-4">
                            @if ($bank->chartOfAccount)
                                <span class="font-mono">{{ $bank->chartOfAccount->code }}</span>
                                -
                                {{ $bank->chartOfAccount->name }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            @if ($bank->trashed())
                                <span class="text-sm font-normal px-2.5 py-0.5 rounded bg-red-700 text-white">
                                    Terhapus
                                </span>
                            @elseif (!$bank->is_active)
                                <span class="text-sm font-normal px-2.5 py-0.5 rounded bg-yellow-600 text-white">
                                    Nonaktif
                                </span>
                            @else
                                <span class="text-sm font-normal px-2.5 py-0.5 rounded bg-green-700 text-white">
                                    Aktif
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            <div class="inline-block" x-data="{
                                open: false,
                                top: 0,
                                left: 0,
                                toggle($el) {
                                    const rect = $el.getBoundingClientRect();
                            
                                    this.top = rect.bottom + 6;
                                    this.left = rect.left - 128;
                            
                                    this.open = !this.open;
                                }
                            }">
                                <button @click="toggle($el)" @click.outside="open = false"
                                    class="inline-flex items-center p-0.5 text-md font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100 cursor-pointer"
                                    type="button">
                                    <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </button>

                                <div x-show="open" x-cloak :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                                    class="z-50 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600">
                                    @if ($bank->trashed())
                                        <div class="px-4 py-2 text-sm text-gray-400">
                                            Data sudah terhapus
                                        </div>
                                    @else
                                        <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button wire:click="openEdit({{ $bank->id }})" @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>Ubah
                                                </button>
                                            </li>
                                        </ul>

                                        <div class="py-1">
                                            <button wire:click="confirmDelete({{ $bank->id }})" @disabled(! auth()->user()->isSuperAdmin())
                                                @click="open = false"
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white cursor-pointer">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>Hapus
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-400 dark:text-gray-500">
                            Tidak ada data bank account.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div class="mt-4">
        {{ $bankAccounts->links() }}
    </div>

    {{-- MODAL CREATE/EDIT --}}
    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-hidden bg-black/50 p-4 backdrop-blur-sm" x-data>
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-2xl mx-auto p-6 max-h-[min(80vh,calc(100dvh-2rem))] overflow-y-auto"
                @click.outside="$wire.showModal = false">

                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-semibold dark:text-white">
                        {{ $bankAccountId ? 'Ubah Rekening Bank' : 'Tambah Rekening Bank' }}
                    </h3>
                    <button wire:click="$set('showModal', false)"
                        class="text-gray-400 hover:text-white cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium dark:text-gray-300 mb-1">
                            Nama Akun <span class="text-red-500">*</span>
                        </label>
                        <input wire:model="name" type="text" placeholder="BCA Operasional / Cash Kantor"
                            class="w-full text-sm dark:bg-zinc-700 border border-gray-600 dark:text-white rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror" />
                        @error('name')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CoA --}}
                    <div>
                        <label class="block text-sm font-medium dark:text-gray-300 mb-1">
                            Daftar Akun <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="chart_of_account_id"
                            class="w-full text-sm dark:bg-zinc-700 border border-gray-600 dark:text-white rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 @error('chart_of_account_id') border-red-500 @enderror">
                            <option value="">-- Pilih Akun CoA --</option>
                            @foreach ($chartAccounts as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->code }} - {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('chart_of_account_id')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Bank Name --}}
                    <div>
                        <label class="block text-sm font-medium dark:text-gray-300 mb-1">
                            Nama Bank
                        </label>
                        <input wire:model="bank_name" type="text" placeholder="BCA / Mandiri / BRI"
                            class="w-full text-sm dark:bg-zinc-700 border border-gray-600 dark:text-white rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 @error('bank_name') border-red-500 @enderror" />
                        @error('bank_name')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Account Number --}}
                    <div>
                        <label class="block text-sm font-medium dark:text-gray-300 mb-1">
                            Nomor Rekening
                        </label>
                        <input wire:model="account_number" type="text" placeholder="1234567890"
                            class="w-full text-sm dark:bg-zinc-700 border border-gray-600 dark:text-white rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 @error('account_number') border-red-500 @enderror" />
                        @error('account_number')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Account Holder --}}
                    <div>
                        <label class="block text-sm font-medium dark:text-gray-300 mb-1">
                            Pemilik Rekening
                        </label>
                        <input wire:model="account_holder" type="text" placeholder="PT Alisan Plastik"
                            class="w-full text-sm dark:bg-zinc-700 border border-gray-600 dark:text-white rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 @error('account_holder') border-red-500 @enderror" />
                        @error('account_holder')
                            <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Is Active --}}
                    <div class="flex items-center">
                        <label class="flex items-center gap-2 text-sm dark:text-gray-300 cursor-pointer mt-5">
                            <input type="checkbox" wire:model="is_active"
                                class="w-4 h-4 rounded border-gray-600 dark:bg-zinc-800 text-blue-600">
                            Akun aktif
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                        Batal
                    </button>
                    <button wire:click="save" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL DELETE CONFIRM --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-red-900 rounded-full">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold dark:text-white">Hapus Rekening Bank?</h3>
                </div>
                <p class="text-sm text-gray-400 mb-5">
                    Data akan dipindahkan ke tempat sampah. Data yang sudah dipakai Pembayaran Utang tidak bisa dihapus.
                </p>
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                        Batal
                    </button>
                    <button wire:click="delete" @disabled(! auth()->user()->isSuperAdmin())
                        class="px-4 py-2 text-sm rounded-lg bg-red-700 text-white hover:bg-red-800 cursor-pointer">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
