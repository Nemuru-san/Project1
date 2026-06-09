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
        <p class="dark:text-white text-base font-semibold">Data Tabel Role User</p>

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
                    class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg block w-full pl-10 p-2.5 placeholder-gray-400"
                    placeholder="Cari role..." />
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
                class="inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap cursor-pointer sm:w-auto w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Role
            </button>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-lg font-bold uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 w-12">No</th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('name')">
                        <div class="flex items-center gap-1">
                            Role Name
                            @if ($sortField === 'name')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4">Permission</th>
                    <th class="px-4 py-4">Users</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Actions</th>
                </tr>
            </thead>

            <tbody class="dark:bg-zinc-950 text-base">
                @forelse ($roles as $index => $role)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $role->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-4 py-4 text-gray-500">
                            {{ $roles->firstItem() + $index }}
                        </td>

                        <td class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $role->name }}

                            @if (in_array('*', $role->permissions ?? [], true))
                                <span class="ml-2 text-xs px-2 py-0.5 rounded bg-blue-700 text-white">
                                    Full Access
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            @if (in_array('*', $role->permissions ?? [], true))
                                <span class="text-sm text-blue-400">Semua Module</span>
                            @else
                                <span class="text-sm text-gray-300">
                                    {{ count($role->permissions ?? []) }} permission
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            {{ $role->users_count }}
                        </td>

                        <td class="px-4 py-4">
                            @if ($role->trashed())
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
                            @if ($role->trashed())
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
                                                <button wire:click="openEdit({{ $role->id }})" @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Edit
                                                </button>
                                            </li>
                                        </ul>

                                        <div class="py-1">
                                            <button wire:click="confirmDelete({{ $role->id }})"
                                                @click="open = false"
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 011 1v3M4 7h16" />
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-gray-400 dark:text-gray-500">
                            Tidak ada data role.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div class="mt-4">
        {{ $roles->links() }}
    </div>

    {{-- CREATE / EDIT MODAL --}}
    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-5xl mx-auto max-h-[90vh] flex flex-col overflow-hidden">

                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">
                        {{ $editingId ? 'Edit Role' : 'Tambah Role' }}
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
                    <div class="space-y-5">
                        <div>
                            <label class="block mb-1 text-sm font-medium dark:text-white">
                                Nama Role
                            </label>

                            <input wire:model="name" type="text"
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white @error('name') border-red-500 @else border-gray-300 @enderror"
                                placeholder="Contoh: Purchasing, Inventory, Super Admin">

                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h4
                                    class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Permission Module
                                </h4>

                                <button type="button" wire:click="toggleFullAccess"
                                    class="text-sm px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white">
                                    {{ in_array('*', $selectedPermissions, true) ? 'Batalkan Full Access' : 'Full Access' }}
                                </button>
                            </div>

                            @if (in_array('*', $selectedPermissions, true))
                                <div
                                    class="mb-4 rounded-lg border border-blue-700 bg-blue-900/20 px-4 py-3 text-sm text-blue-300">
                                    Role ini punya akses ke semua module.
                                </div>
                            @endif

                            <div
                                class="{{ in_array('*', $selectedPermissions, true) ? 'opacity-40 pointer-events-none' : '' }} space-y-4">
                                @foreach ($permissionGroups as $group => $permissions)
                                    <div
                                        class="rounded-lg border border-gray-200 dark:border-zinc-700 overflow-hidden">
                                        <div class="px-4 py-2 bg-gray-100 dark:bg-zinc-900">
                                            <p class="text-sm font-semibold text-gray-700 dark:text-white">
                                                {{ $group }}
                                            </p>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 p-4">
                                            @foreach ($permissions as $key => $label)
                                                <label
                                                    class="flex items-center gap-2 text-sm dark:text-gray-300 cursor-pointer">
                                                    <input type="checkbox" wire:model="selectedPermissions"
                                                        value="{{ $key }}"
                                                        class="w-4 h-4 rounded border-gray-600 dark:bg-zinc-800 text-blue-600">

                                                    <span>{{ $label }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @error('selectedPermissions')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
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
                <p class="text-sm text-gray-400 mb-6">Role akan dipindahkan ke trash.</p>

                <div class="flex justify-end gap-2">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                        Batal
                    </button>

                    <button wire:click="delete" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white disabled:opacity-50">
                        <span wire:loading.remove wire:target="delete">Hapus</span>
                        <span wire:loading wire:target="delete">Menghapus...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
