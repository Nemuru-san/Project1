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
        <p class="dark:text-white text-lg font-semibold">Data Tabel Master Product</p>
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
                    placeholder="Cari kode, nama, kategori, brand..." />
            </div>
            {{-- Status Filter --}}
            <select wire:model.live="statusFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-6 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Status</option>
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
            </select>
            {{-- Per Page --}}
            <select wire:model.live="perPage"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-6 py-2.5 w-full sm:w-auto">
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
                class="inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 border border-transparent text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Product
            </button>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-lg font-bold text-white uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('prd_code')">
                        <div class="flex items-center gap-1">Kode Product
                            @if ($sortField === 'prd_code')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('prd_name')">
                        <div class="flex items-center gap-1">Nama Product
                            @if ($sortField === 'prd_name')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4">Deskripsi</th>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('prd_category')">
                        <div class="flex items-center gap-1">Kategori
                            @if ($sortField === 'prd_category')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('prd_brand')">
                        <div class="flex items-center gap-1">Brand
                            @if ($sortField === 'prd_brand')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4">Unit</th>
                    <th class="px-4 py-4">Barcode</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Dibuat Oleh</th>
                    <th class="px-4 py-4">Actions</th>
                </tr>
            </thead>
            <tbody class="dark:bg-zinc-950 text-base">
                @forelse ($products as $product)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $product->trashed() ? 'opacity-60' : '' }}">
                        <th scope="row"
                            class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white uppercase">
                            {{ $product->prd_code }}
                        </th>
                        <td class="px-4 py-4">{{ $product->prd_name }}</td>
                        <td class="px-4 py-4 max-w-xs truncate">{{ $product->prd_desc ?? '-' }}</td>
                        <td class="px-4 py-4">{{ $product->prd_category ?? '-' }}</td>
                        <td class="px-4 py-4">{{ $product->prd_brand ?? '-' }}</td>
                        <td class="px-4 py-4">{{ $product->prd_unit ?? '-' }}</td>
                        <td class="px-4 py-4">{{ $product->prd_barcode ?? '-' }}</td>
                        <td class="px-4 py-4">
                            {{-- @if ($product->trashed())
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded dark:bg-red-700 dark:text-white">Dihapus</span> --}}
                            @if ($product->status)
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded dark:bg-green-700 dark:text-white">Aktif</span>
                            @else
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded dark:bg-red-700 dark:text-white">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">{{ $product->created_by ?? '-' }}</td>
                        <td class="px-4 py-4">
                            @if ($product->trashed())
                                <div class="flex gap-2">
                                    <button wire:click="restore({{ $product->id }})"
                                        class="text-xs px-2 py-1 rounded bg-yellow-600 text-white hover:bg-yellow-700">
                                        Pulihkan
                                    </button>
                                    <button wire:click="forceDelete({{ $product->id }})"
                                        wire:confirm="Hapus permanen? Data tidak bisa dikembalikan!"
                                        class="text-xs px-2 py-1 rounded bg-red-700 text-white hover:bg-red-800">
                                        Hapus Permanen
                                    </button>
                                </div>
                            @else
                                <div class="inline-block" x-data="{
                                    open: false,
                                    top: 0,
                                    left: 0,
                                    toggle($el) {
                                        const rect = $el.getBoundingClientRect();
                                        this.top = rect.bottom + window.scrollY;
                                        this.left = rect.left + window.scrollX - 128;
                                        this.open = !this.open;
                                    }
                                }">
                                    <button @click="toggle($el)" @click.outside="open = false"
                                        class="inline-flex items-center p-0.5 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
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
                                                <button wire:click="openEdit({{ $product->id }})"
                                                    @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
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
                                            <button wire:click="confirmDelete({{ $product->id }})"
                                                @click="open = false"
                                                wire:confirm="Yakin ingin menghapus product ini?"
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                        <td colspan="10" class="text-center py-8 text-gray-400 dark:text-gray-500">
                            Tidak ada data product.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div class="mt-4">
        {{ $products->links() }}
    </div>
</div>
