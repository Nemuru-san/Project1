<div x-data="{ toastMsg: '', toastType: '', showAddProductModal: false }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- FILTER BAR --}}
    <div
        class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 my-4 dark:bg-zinc-900">
        <p class="dark:text-white text-base font-semibold">Data Tabel Unit of Measure (UOM)</p>
        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
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
                    placeholder="Cari kode, nama..." />
            </div>
            <select wire:model.live="perPage"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 w-full sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>
            <label class="flex items-center gap-2 text-sm dark:text-gray-300 cursor-pointer whitespace-nowrap">
                <input type="checkbox" wire:model.live="showTrashed"
                    class="w-4 h-4 rounded border-gray-600 dark:bg-zinc-800 text-blue-600">
                Tampilkan Terhapus
            </label>
            <button wire:click="openCreate"
                class="inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap cursor-pointer sm:w-auto w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Data
            </button>
        </div>
    </div>

    {{-- ═══════════════════════ TABLE ═══════════════════════ --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-lg font-bold uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 w-12">No</th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('adjustment_no')">
                        <div class="flex items-center gap-1">
                            ADO No
                            @if ($sortField === 'adjustment_no')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('date')">
                        <div class="flex items-center gap-1">
                            Date
                            @if ($sortField === 'date')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4">Warehouse</th>
                    <th class="px-4 py-4">Notes</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>

            <tbody class="dark:bg-zinc-950 text-base">
                @forelse($adjustments as $index => $adjustment)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $adjustment->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-4 py-4 text-gray-500">
                            {{ $adjustments->firstItem() + $index }}
                        </td>

                        <td class="px-4 py-4 font-mono font-medium text-gray-900 dark:text-white">
                            {{ $adjustment->adjustment_no }}
                        </td>

                        <td class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $adjustment->date?->format('d/m/Y') }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $adjustment->warehouse?->name ?? '-' }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $adjustment->notes ?: '-' }}
                        </td>

                        <td class="px-4 py-4">
                            @if ($adjustment->trashed())
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-red-700 text-white">Terhapus</span>
                            @elseif ($adjustment->status === 'draft')
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-gray-600 text-white">Draft</span>
                            @elseif ($adjustment->status === 'approved')
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-blue-700 text-white">Approved</span>
                            @else
                                <span class="text-sm font-normal px-2.5 py-0.5 rounded bg-zinc-600 text-white">
                                    {{ ucfirst($adjustment->status) }}
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

                                    @if ($adjustment->trashed())
                                        <div class="px-4 py-2 text-sm text-gray-400">
                                            Data sudah terhapus
                                        </div>
                                    @else
                                        <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                            @if ($adjustment->status === 'draft')
                                                <li>
                                                    <button wire:click="confirmApprove({{ $adjustment->id }})"
                                                        @click="open = false"
                                                        class="flex items-center gap-2 w-full py-2 px-4 text-blue-700 hover:bg-blue-600 hover:text-white dark:text-blue-300 dark:hover:bg-blue-600 dark:hover:text-white cursor-pointer">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Approve
                                                    </button>
                                                </li>
                                            @endif
                                            <li>
                                                <button wire:click="openDetail({{ $adjustment->id }})"
                                                    @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Detail
                                                </button>
                                            </li>
                                            @if ($adjustment->status === 'draft')
                                                <li>
                                                    <button wire:click="edit({{ $adjustment->id }})"
                                                        @click="open = false"
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
                                            @else
                                                <li>
                                                    <button type="button" disabled
                                                        class="flex items-center gap-2 w-full py-2 px-4 text-gray-400 cursor-not-allowed opacity-60">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                        Edit
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>

                                        <div class="py-1">
                                            @if ($adjustment->status === 'draft')
                                                <button wire:click="confirmDelete({{ $adjustment->id }})"
                                                    @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 text-base text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Delete
                                                </button>
                                            @else
                                                <button type="button" disabled
                                                    class="flex items-center gap-2 w-full py-2 px-4 text-base text-gray-400 cursor-not-allowed opacity-60">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Delete
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                            Tidak ada data adjustment out ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $adjustments->links() }}
    </div>

    {{-- CREATE / EDIT MODAL --}}
    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-7xl mx-auto max-h-[80vh] flex flex-col overflow-hidden">

                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">
                        Adjustment Out
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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 text-sm font-medium dark:text-white">ADO No</label>
                            <input wire:model.defer="adjustment_no" type="text" disabled
                                placeholder="autogenerated"
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white border-gray-300" />
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium dark:text-white">Date</label>
                            <input wire:model="date" type="date"
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white border-gray-300" />
                            @error('date')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium dark:text-white">Warehouse</label>
                            <select wire:model.live="warehouse_id"
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white border-gray-300">
                                <option value="">Pilih Gudang</option>
                                @foreach ($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                            @error('warehouse_id')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block mb-1 text-sm font-medium dark:text-white">Notes</label>
                            <input wire:model="notes" type="text"
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white border-gray-300"
                                placeholder="Keterangan tambahan..." />
                        </div>
                    </div>

                    <div class="overflow-x-auto mt-6">
                        <table
                            class="w-full text-base text-left text-gray-900 dark:text-white border-collapse border border-gray-300 dark:border-zinc-600 min-w-max whitespace-nowrap">
                            <thead
                                class="text-sm font-bold text-gray-900 uppercase bg-gray-200 dark:bg-zinc-700 dark:text-white">
                                <tr>
                                    <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-center w-14">
                                        <button type="button" @click="showAddProductModal = true"
                                            class="inline-flex items-center justify-center w-10 h-10 rounded-sm bg-blue-600 text-white hover:bg-blue-700">
                                            <span class="text-xl font-semibold">+</span>
                                        </button>
                                    </th>
                                    <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Kode
                                        Produk</th>
                                    <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Nama
                                        Produk</th>
                                    <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Qty</th>
                                    <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">UOM</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($items as $index => $item)
                                    <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                        <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-center">
                                            <button type="button" wire:click="removeItem({{ $index }})"
                                                class="text-red-500 hover:text-red-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </td>

                                        <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                            {{ $item['sku'] ?? '-' }}
                                        </td>

                                        <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                            {{ $item['name'] ?? '-' }}
                                        </td>

                                        <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                            <input type="text" inputmode="numeric" autocomplete="off"
                                                placeholder="Qty" x-data="{
                                                    display: '{{ number_format($item['qty'] ?? 0, 0, ',', '.') }}'
                                                }" x-model="display"
                                                @input="
                                                    let raw = display.replace(/\./g, '').replace(/\D/g, '');
                                                    display = raw === '' ? '' : Number(raw).toLocaleString('id-ID');
                                                    $wire.set('items.{{ $index }}.qty', raw === '' ? 0 : Number(raw));
                                                "
                                                @disabled(($item['stock_available'] ?? 0) <= 0)
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-24 p-2 disabled:bg-gray-200 disabled:cursor-not-allowed dark:bg-zinc-800 dark:border-gray-600 dark:text-white dark:disabled:bg-zinc-700">

                                            @if (($item['stock_available'] ?? 0) <= 0)
                                                <p class="mt-1 text-xs text-red-500">Stock kosong, qty tidak bisa
                                                    diisi.</p>
                                            @endif
                                        </td>

                                        <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                            <select wire:model.live="items.{{ $index }}.unit_id"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-36 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                                <option value="">Pilih Satuan</option>
                                                @foreach ($item['unit_options'] ?? [] as $unit)
                                                    <option value="{{ $unit['unit_id'] }}">
                                                        {{ $unit['unit_name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5"
                                            class="border border-gray-300 dark:border-zinc-600 px-4 py-6 text-center text-gray-500">
                                            Belum ada produk dipilih.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @error('items')
                            <p class="mt-2 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ADD PRODUCT MODAL --}}
                    <div x-show="showAddProductModal" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                        <div
                            class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl w-full max-w-4xl mx-auto max-h-[85vh] overflow-hidden">
                            <div
                                class="flex items-start justify-between px-6 py-4 border-b border-gray-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Detail
                                        Produk</h3>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Cari produk dan pilih dari
                                        daftar.</p>
                                </div>

                                <button type="button" @click="showAddProductModal = false"
                                    class="text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-6 space-y-6">
                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Cari Produk
                                        </label>
                                        <input wire:model.live.debounce.300ms="productSearch" type="text"
                                            placeholder="Cari kode atau nama produk"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-3 dark:bg-zinc-800 dark:border-gray-600 dark:text-white" />
                                    </div>

                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                            Kategori Produk
                                        </label>
                                        <select wire:model.live="categoryFilter"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-3 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                            <option value="">Semua Kategori</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table
                                        class="w-full text-sm text-left text-gray-900 dark:text-white border-collapse border border-gray-300 dark:border-zinc-600">
                                        <thead
                                            class="bg-gray-100 dark:bg-zinc-800 text-xs uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                            <tr>
                                                <th
                                                    class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-center">
                                                    Pilih</th>
                                                <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Kode
                                                </th>
                                                <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3">Nama
                                                    Produk</th>
                                                <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                    Kategori</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse ($products as $product)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                                    <td
                                                        class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-center">
                                                        <input wire:model.live="selectedProductIds" type="checkbox"
                                                            value="{{ $product->id }}"
                                                            class="h-4 w-4 rounded border-gray-300 text-blue-600" />
                                                    </td>
                                                    <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                        {{ $product->sku ?? '-' }}
                                                    </td>
                                                    <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                        {{ $product->name }}
                                                    </td>
                                                    <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                        {{ $product->category?->name ?? '-' }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4"
                                                        class="border border-gray-300 dark:border-zinc-600 px-4 py-6 text-center text-gray-500">
                                                        Produk tidak ditemukan.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div
                                class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
                                <button type="button" @click="showAddProductModal = false"
                                    class="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-zinc-700 dark:text-gray-200 dark:hover:bg-zinc-800">
                                    Batal
                                </button>

                                <button type="button" wire:click="addSelectedProducts"
                                    @click="if ($wire.selectedProductIds.length > 0) showAddProductModal = false"
                                    class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                                    Simpan
                                </button>
                            </div>
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

    {{-- APPROVE MODAL --}}
    @if ($showApproveModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-blue-900 rounded-full">
                        <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h3 class="text-base font-semibold dark:text-white">
                        Approve Adjustment Out?
                    </h3>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    Setelah Adjustment Out di-approve, stock produk akan berkurang dari warehouse yang dipilih.
                    Data tidak bisa diedit atau dihapus.
                </p>

                <div class="flex justify-end gap-2">
                    <button wire:click="cancelApprove"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 cursor-pointer">
                        Batal
                    </button>

                    <button wire:click="approve" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="approve">Ya, Approve</span>
                        <span wire:loading wire:target="approve">Meng-approve...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showDetail && $selectedAdjustment)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">

                <div class="flex items-center justify-between px-6 py-4 border-b dark:border-zinc-700">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Detail Adjustment Out</h2>
                        <p class="text-sm text-gray-400 font-mono mt-0.5">
                            {{ $selectedAdjustment->adjustment_no }}
                        </p>
                    </div>

                    <button wire:click="closeDetail"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">ADO No</span>
                                <span class="font-mono font-medium text-gray-800 dark:text-white">
                                    {{ $selectedAdjustment->adjustment_no }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Tanggal</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedAdjustment->date?->format('d F Y') }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Warehouse</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedAdjustment->warehouse?->name ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Dibuat Oleh</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedAdjustment->creator?->name ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Status</span>
                                <span>
                                    @if ($selectedAdjustment->trashed())
                                        <span class="text-sm px-2.5 py-0.5 rounded bg-red-700 text-white">
                                            Terhapus
                                        </span>
                                    @elseif ($selectedAdjustment->status === 'draft')
                                        <span class="text-sm px-2.5 py-0.5 rounded bg-gray-600 text-white">
                                            Draft
                                        </span>
                                    @elseif ($selectedAdjustment->status === 'approved')
                                        <span class="text-sm px-2.5 py-0.5 rounded bg-blue-700 text-white">
                                            Approved
                                        </span>
                                    @endif
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Type</span>
                                <span class="text-gray-800 dark:text-white">Adjustment Out</span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Catatan</span>
                                <span class="text-gray-800 dark:text-white text-right max-w-xs">
                                    {{ $selectedAdjustment->notes ?: '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border dark:border-zinc-700">
                        <table class="w-full text-sm text-left">
                            <thead
                                class="bg-gray-50 dark:bg-zinc-800 text-gray-500 dark:text-gray-300 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 w-8">No</th>
                                    <th class="px-4 py-3">Produk</th>
                                    <th class="px-4 py-3 text-right">Qty</th>
                                    <th class="px-4 py-3">UOM</th>
                                    <th class="px-4 py-3 text-right">Conversion</th>
                                    <th class="px-4 py-3 text-right">Qty Base</th>
                                </tr>
                            </thead>

                            <tbody class="dark:bg-zinc-900 divide-y divide-gray-100 dark:divide-zinc-700">
                                @forelse($selectedAdjustment->items as $i => $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            <div class="font-medium">{{ $item->product?->name ?? '-' }}</div>
                                            <div class="text-xs text-gray-400 font-mono">
                                                {{ $item->product?->sku ?? '-' }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            {{ (int) $item->qty }}
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            {{ $item->unit?->name ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            {{ (int) $item->conversion }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            {{ (int) ($item->qty * $item->conversion) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                                            Tidak ada item.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="px-6 py-4 border-t dark:border-zinc-700 flex justify-end">
                    <button wire:click="closeDetail"
                        class="px-5 py-2 rounded-lg bg-zinc-700 hover:bg-zinc-600 text-white text-sm font-medium transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- DELETE MODAL --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold dark:text-white">Hapus Adjustment Out?</h3>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Data Adjustment Out akan dipindahkan ke trash.
                </p>

                <div class="flex justify-end gap-2 mt-6">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:text-gray-300">
                        Batal
                    </button>

                    <button wire:click="delete"
                        class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
