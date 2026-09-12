<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    <x-filter.card title="Filter Pengguna" description="Temukan pengguna berdasarkan nama, email, atau peran.">
        <x-slot:actions>
            <x-filter.add-button wire:click="openCreate">Tambah Pengguna</x-filter.add-button>
        </x-slot:actions>
        <x-filter.search placeholder="Cari nama, email, role..." />
        <x-filter.per-page :show-reset="filled($search)" />
        <x-filter.checkbox model="showTrashed" />
    </x-filter.card>

    {{-- TABLE --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-sm font-bold uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 w-12">No.</th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('name')">
                        <div class="flex items-center gap-1">
                            Nama
                            @if ($sortField === 'name')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('email')">
                        <div class="flex items-center gap-1">
                            Email
                            @if ($sortField === 'email')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4">Peran</th>
                    <th class="px-4 py-4">Hak Akses</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>

            <tbody class="dark:bg-zinc-950 text-sm">
                @forelse ($users as $index => $user)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $user->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-4 py-4 text-gray-500">
                            {{ $users->firstItem() + $index }}
                        </td>

                        <td class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $user->name }}

                            @if ($user->id === auth()->id())
                                <span class="ml-2 text-xs px-2 py-0.5 rounded bg-blue-700 text-white">
                                    Login
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            {{ $user->email }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $user->role?->name ?? '-' }}
                        </td>

                        <td class="px-4 py-4">
                            @if (in_array('*', $user->role?->permissions ?? [], true))
                                <span class="text-sm text-blue-400">Akses Penuh</span>
                            @else
                                <span class="text-sm text-gray-300">
                                    {{ count($user->role?->permissions ?? []) }} permission
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            @if ($user->trashed())
                                <span class="text-sm font-normal px-2.5 py-0.5 rounded bg-red-700 text-white">
                                    Terhapus
                                </span>
                            @else
                                <span class="text-sm font-normal px-2.5 py-0.5 rounded bg-green-700 text-white">
                                    Aktif
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            @if ($user->trashed())
                                <div class="px-4 py-2 text-sm text-gray-400">
                                    Data sudah terhapus
                                </div>
                            @else
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

                                    <div x-show="open" x-cloak
                                        :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                                        class="z-50 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600">
                                        <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button wire:click="openEdit({{ $user->id }})" @click="open = false"
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
                                            <button wire:click="confirmDelete({{ $user->id }})"
                                                @disabled(!auth()->user()->isSuperAdmin() || $user->id === auth()->id()) @click="open = false"
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base {{ $user->id === auth()->id() ? 'opacity-40 cursor-not-allowed text-gray-400 dark:text-gray-500' : 'text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white' }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 011 1v3M4 7h16" />
                                                </svg>Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-400 dark:text-gray-500">
                            Tidak ada data user.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div class="mt-4">
        {{ $users->links() }}
    </div>

    {{-- CREATE / EDIT MODAL --}}
    @if ($showModal)
        <div
            class="fixed inset-0 z-40 flex items-start justify-center overflow-hidden bg-black/50 backdrop-blur-sm p-4">
            <div
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-2xl mx-auto max-h-[min(80vh,calc(100dvh-2rem))] flex flex-col overflow-hidden">

                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">
                        {{ $editingId ? 'Ubah Pengguna' : 'Tambah Pengguna' }}
                    </h3>

                    <button wire:click="$set('showModal', false)"
                        class="text-gray-400 hover:text-white cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium dark:text-white">
                                Nama Pengguna
                            </label>

                            <input wire:model="name" type="text"
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white @error('name') border-red-500 @else border-gray-300 @enderror"
                                placeholder="Masukkan nama user">

                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium dark:text-white">Email
                            </label>

                            <input wire:model="email" type="email"
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white @error('email') border-red-500 @else border-gray-300 @enderror"
                                placeholder="email@example.com">

                            @error('email')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium dark:text-white">Peran
                            </label>

                            <select wire:model="role_id"
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white @error('role_id') border-red-500 @else border-gray-300 @enderror">
                                <option value="">-- Pilih Peran --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>

                            @error('role_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium dark:text-white">
                                Password {{ $editingId ? '(kosongkan jika tidak diubah)' : '' }}
                            </label>

                            <input wire:model="password" type="password"
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white @error('password') border-red-500 @else border-gray-300 @enderror"
                                placeholder="Minimal 8 karakter">

                            @error('password')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium dark:text-white">
                                Konfirmasi Password
                            </label>

                            <input wire:model="password_confirmation" type="password"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white"
                                placeholder="Ulangi password">
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-2 p-6 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-white dark:bg-zinc-900">
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

    {{-- DELETE CONFIRM MODAL --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-sm p-6">
                <h3 class="text-lg font-semibold dark:text-white mb-2">Konfirmasi Hapus</h3>
                <p class="text-sm text-gray-400 mb-6">Pengguna akan dipindahkan ke tempat sampah.</p>

                <div class="flex justify-end gap-2">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                        Batal
                    </button>

                    <button wire:click="delete" wire:loading.attr="disabled" @disabled(!auth()->user()->isSuperAdmin())
                        class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white disabled:opacity-50">
                        <span wire:loading.remove wire:target="delete">Hapus</span>
                        <span wire:loading wire:target="delete">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
