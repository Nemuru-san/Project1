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
        <p class="dark:text-white text-base font-semibold">Data Tabel Purchase Order</p>
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
                    placeholder="Cari kode, nama, kontak..." />
            </div>
            {{-- Status Filter --}}
            <select wire:model.live="statusFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Status</option>
                <option value="Draft">Draft</option>
                <option value="Approved">Approved</option>
                <option value="Partially Received">Partially Received</option>
                <option value="Received">Received</option>
            </select>
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
            {{-- Export --}}
            {{-- <button wire:click="export"
                class="inline-flex items-center gap-2 text-white bg-indigo-600 hover:bg-indigo-700 border border-transparent text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v6m0 0l-3-3m3 3l3-3M12 3v9" />
                </svg>
                Export CSV
            </button> --}}
            {{-- Tambah --}}
            <button wire:click="openCreate"
                class="inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 border border-transparent text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap cursor-pointer sm:w-auto w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Transaksi
            </button>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-lg font-bold text-white uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 w-12">No</th>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('code')">
                        <div class="flex items-center gap-1">
                            Kode PO
                            @if ($sortField === 'code')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('date')">
                        <div class="flex items-center gap-1">
                            Tanggal
                            @if ($sortField === 'date')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4">Supplier</th>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('nett')">
                        <div class="flex items-center gap-1">
                            Nett
                            @if ($sortField === 'nett')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody class="dark:bg-zinc-950 text-base text-white">
                @forelse($purchaseOrders as $index => $po)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $po->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-4 py-4 text-gray-500">{{ $purchaseOrders->firstItem() + $index }}</td>
                        <td class="px-4 py-4 font-mono font-medium text-gray-900 dark:text-white">{{ $po->code }}
                        </td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white">{{ $po->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white">{{ $po->supplier?->name ?? '-' }}</td>
                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            Rp {{ number_format($po->nett, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4">
                            @if ($po->trashed())
                                <span class="text-sm font-normal px-2.5 py-0.5 rounded dark:bg-red-700 dark:text-white">
                                    Terhapus
                                </span>
                            @elseif ($po->status === 'Draft')
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-gray-200 text-gray-700 dark:bg-zinc-600 dark:text-white">
                                    Draft
                                </span>
                            @elseif ($po->status === 'Approved')
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-700 dark:text-white">
                                    Approved
                                </span>
                            @elseif ($po->status === 'Received')
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-700 dark:text-white">
                                    Received
                                </span>
                            @elseif ($po->status === 'Partially Received')
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-yellow-100 text-yellow-700 dark:bg-yellow-600 dark:text-white">
                                    Partially Received
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
                                    this.top = rect.bottom + window.scrollY;
                                    this.left = rect.left + window.scrollX - 128;
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

                                    @php $locked = in_array($po->status, ['Approved', 'Received', 'Partially Received']); @endphp

                                    @if ($po->trashed())
                                        <div class="px-4 py-2 text-sm text-gray-400">
                                            Data sudah terhapus
                                        </div>
                                    @else
                                        <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button wire:click="openDetail({{ $po->id }})"
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

                                            <li>
                                                <button
                                                    @if (!$locked) wire:click="openEdit({{ $po->id }})" @endif
                                                    @click="open = false" @disabled($locked)
                                                    class="flex items-center gap-2 w-full py-2 px-4 {{ $locked ? 'opacity-40 cursor-not-allowed' : 'hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer' }}">
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
                                            <button
                                                @if (!$locked) wire:click="confirmDelete({{ $po->id }})" @endif
                                                @click="open = false" @disabled($locked)
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base {{ $locked ? 'opacity-40 cursor-not-allowed text-gray-400 dark:text-gray-500' : 'text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white cursor-pointer' }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Delete
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                            Tidak ada data purchase order ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $purchaseOrders->links() }}
    </div>

    @if ($showModal)
        <div x-data="{ showAddProductModal: false }"
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-full mx-auto h-[90vh] flex flex-col overflow-hidden">

                {{-- Header --}}
                <div
                    class="flex items-center justify-between px-8 py-6 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">
                        {{ $editId ? 'Edit Purchase Order' : 'Tambah Purchase Order' }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-white cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-8 py-6">

                    {{-- Form Header --}}
                    <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">PO No</label>
                            <input type="text"
                                class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed"
                                placeholder="Auto Generated" disabled>
                        </div>

                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Date</label>
                            <input type="date" wire:model="date"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                            @error('date')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label
                                class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Supplier</label>
                            <select wire:model="id_supplier"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                <option value="">-- Pilih Supplier --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @error('id_supplier')
                                <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Tax (PPN
                                11%)</label>
                            <label class="inline-flex items-center cursor-pointer">
                                <span
                                    class="select-none text-base font-medium text-gray-600 dark:text-gray-400">No</span>
                                <div class="relative mx-3">
                                    <input type="checkbox" wire:model.live="tax" class="sr-only peer">
                                    <div
                                        class="w-9 h-5 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600">
                                    </div>
                                </div>
                                <span
                                    class="select-none text-base font-medium text-gray-600 dark:text-gray-400">Yes</span>
                            </label>
                        </div>
                    </div>

                    <div class="w-full mt-6">
                        <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Note</label>
                        <textarea wire:model="purchase_note" rows="3"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white"
                            placeholder="Masukkan catatan atau keterangan tambahan..."></textarea>
                    </div>

                    {{-- Tabel Items --}}
                    <div class="mt-12">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Detail Produk</h3>
                        </div>
                        @error('items')
                            <p class="text-red-500 text-xs mb-2">{{ $message }}</p>
                        @enderror

                        <div class="overflow-x-auto">
                            <table
                                class="w-full text-base text-left text-gray-900 dark:text-white border-collapse border border-gray-300 dark:border-zinc-600 min-w-max whitespace-nowrap">
                                <thead
                                    class="text-sm font-bold text-gray-900 uppercase bg-gray-200 dark:bg-zinc-700 dark:text-white">
                                    <tr>
                                        <th
                                            class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-center w-14">
                                            <button type="button" @click="showAddProductModal = true"
                                                class="inline-flex items-center justify-center w-10 h-10 rounded-sm bg-blue-600 text-white hover:bg-blue-700">
                                                <span class="text-xl font-semibold">+</span>
                                            </button>
                                        </th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">No
                                        </th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Kode
                                            Produk</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Nama
                                            Produk</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                            Kategori</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                            Satuan</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Qty
                                            Order</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Price
                                        </th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Disc
                                            (Rp)</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Sub
                                            Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($items as $i => $item)
                                        <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                            <td
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-center">
                                                <button type="button" wire:click="removeItem({{ $i }})"
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
                                                {{ $i + 1 }}</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $item['product_code'] }}</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $item['product_name'] }}</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $item['category'] }}</td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <select wire:model.live="items.{{ $i }}.id_price"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-36 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                                    @foreach ($item['prices'] as $p)
                                                        <option value="{{ $p['id'] }}">{{ $p['unit_name'] }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="1"
                                                    wire:model.live="items.{{ $i }}.qty"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-24 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0"
                                                    wire:model.live="items.{{ $i }}.price"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-28 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                            </td>
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="number" min="0"
                                                    wire:model.live="items.{{ $i }}.disc"
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-28 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                            </td>
                                            <td
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-medium">
                                                Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-8 text-center text-gray-400 text-sm">
                                                Belum ada produk. Klik tombol <strong>+</strong> untuk menambahkan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div
                        class="mt-6 p-4 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-gray-700">
                        <h4 class="mb-4 text-base font-bold text-gray-900 dark:text-white">Detail Harga</h4>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label
                                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Gross</label>
                                <input type="text" value="Rp {{ number_format($gross, 0, ',', '.') }}"
                                    class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed"
                                    disabled>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Total
                                    Diskon</label>
                                <input type="text" value="Rp {{ number_format($totalDisc, 0, ',', '.') }}"
                                    class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed"
                                    disabled>
                            </div>
                            <div>
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">PPN
                                    (11%)</label>
                                <input type="text" value="Rp {{ number_format($ppn, 0, ',', '.') }}"
                                    class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed"
                                    disabled>
                            </div>
                            <div>
                                <label
                                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Nett</label>
                                <input type="text" value="Rp {{ number_format($nett, 0, ',', '.') }}"
                                    class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed"
                                    disabled>
                            </div>
                        </div>
                    </div>

                    {{-- Modal Tambah Produk --}}
                    <div x-show="showAddProductModal" x-cloak
                        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4 py-6">
                        <div
                            class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 shadow-xl h-[85vh] flex flex-col">

                            <div
                                class="flex items-center justify-between border-b border-gray-200 dark:border-zinc-700 px-6 py-4 shrink-0">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Detail
                                        Produk</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Cari produk dan pilih dari
                                        daftar.</p>
                                </div>
                                <button type="button" @click="showAddProductModal = false"
                                    class="cursor-pointer text-gray-400 hover:text-white">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                                {{-- Search & Filter --}}
                                <div class="grid gap-4 sm:grid-cols-[1fr_auto] items-end">
                                    <div class="relative">
                                        <input type="text" wire:model.live.debounce.300ms="searchProduct"
                                            placeholder="Cari produk (nama / kode)..."
                                            class="w-full bg-gray-50 border border-gray-300 text-gray-900 rounded-lg p-2.5 pl-10 dark:bg-zinc-800 dark:border-gray-600 dark:text-white text-sm">
                                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.5 5.5a7.5 7.5 0 0 0 10.5 10.5Z" />
                                        </svg>
                                    </div>
                                    <select wire:model.live="filterCategory"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg p-2.5 w-full sm:w-60 dark:bg-zinc-800 dark:border-gray-600 dark:text-white text-sm">
                                        <option value="">Semua Kategori</option>
                                        @foreach ($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Tabel produk --}}
                                <div class="overflow-x-auto rounded-xl border border-gray-300 dark:border-zinc-600">
                                    <table
                                        class="w-full text-left text-sm text-gray-900 dark:text-white border-collapse">
                                        <thead
                                            class="bg-gray-100 font-semibold uppercase text-xs dark:bg-zinc-800 dark:text-gray-200">
                                            <tr>
                                                <th class="border border-gray-300 dark:border-zinc-700 px-4 py-3">Kode
                                                </th>
                                                <th class="border border-gray-300 dark:border-zinc-700 px-4 py-3">Nama
                                                    Produk</th>
                                                <th class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                    Kategori</th>
                                                <th
                                                    class="border border-gray-300 dark:border-zinc-700 px-4 py-3 text-center">
                                                    Pilih</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($products as $product)
                                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                        {{ $product->sku }}</td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                        {{ $product->name }}</td>
                                                    <td class="border border-gray-300 dark:border-zinc-700 px-4 py-3">
                                                        {{ $product->category?->name ?? '-' }}</td>
                                                    <td
                                                        class="border border-gray-300 dark:border-zinc-700 px-4 py-3 text-center">
                                                        <button type="button"
                                                            wire:click="addProduct({{ $product->id }})"
                                                            @click="showAddProductModal = false"
                                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                                                            Pilih
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400">
                                                        Produk tidak ditemukan.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Pagination --}}
                                <div class="mt-2">
                                    {{ $products->links() }}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Footer --}}
                <div
                    class="flex justify-end gap-2 p-6 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-white dark:bg-zinc-900">
                    <button wire:click="closeModal"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                        Batal
                    </button>
                    <button wire:click="save" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                    @if ($errors->any())
                        <div class="text-red-400 text-xs mr-auto max-w-md">
                            <ul>
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    @endif

    @if ($showDetail && $selectedPO)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">

                <div class="flex items-center justify-between px-6 py-4 border-b dark:border-zinc-700">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Detail Purchase Order</h2>
                        <p class="text-sm text-gray-400 font-mono mt-0.5">{{ $selectedPO->code }}</p>
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
                                <span class="text-gray-400">Kode PO</span>
                                <span
                                    class="font-mono font-medium text-gray-800 dark:text-white">{{ $selectedPO->code }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Tanggal</span>
                                <span
                                    class="text-gray-800 dark:text-white">{{ $selectedPO->date->format('d F Y') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Supplier</span>
                                <span
                                    class="text-gray-800 dark:text-white">{{ $selectedPO->supplier?->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Dibuat Oleh</span>
                                <span
                                    class="text-gray-800 dark:text-white">{{ $selectedPO->user?->name ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Status</span>
                                <span>
                                    @php
                                        $statusClass = match ($selectedPO->status) {
                                            'Draft' => 'bg-zinc-600 text-white',
                                            'Approved' => 'bg-blue-700 text-white',
                                            'Received' => 'bg-green-700 text-white',
                                            'Tagihan' => 'bg-yellow-600 text-white',
                                            'Bayar Full' => 'bg-emerald-700 text-white',
                                            'Bayar Setengah' => 'bg-orange-600 text-white',
                                            default => 'bg-zinc-600 text-white',
                                        };
                                    @endphp
                                    <span class="text-sm px-2.5 py-0.5 rounded {{ $statusClass }}">
                                        {{ $selectedPO->status }}
                                    </span>
                                </span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Pajak</span>
                                <span
                                    class="text-gray-800 dark:text-white">{{ $selectedPO->tax ? 'Ya' : 'Tidak' }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Catatan</span>
                                <span class="text-gray-800 dark:text-white text-right max-w-xs">
                                    {{ $selectedPO->purchase_note ?: '-' }}
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
                                    <th class="px-4 py-3 text-right">Diskon</th>
                                    <th class="px-4 py-3 text-right">Total Harga</th>
                                </tr>
                            </thead>
                            <tbody class="dark:bg-zinc-900 divide-y divide-gray-100 dark:divide-zinc-700">
                                @forelse($selectedPO->items as $i => $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            <div class="font-medium">{{ $item->product?->name ?? '-' }}</div>
                                            <div class="text-xs text-gray-400 font-mono">
                                                {{ $item->product?->sku ?? '' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            {{ $item->qty }}</td>
                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            Rp {{ number_format($item->disc, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            Rp {{ number_format($item->total_harga, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">Tidak ada item.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end">
                        <div class="space-y-2 text-sm w-full max-w-xs">
                            <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                <span>Gross</span>
                                <span>Rp {{ number_format($selectedPO->gross, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                <span>Total Harga</span>
                                <span>Rp {{ number_format($selectedPO->total_price, 0, ',', '.') }}</span>
                            </div>
                            <div
                                class="flex justify-between font-bold text-base text-gray-800 dark:text-white border-t dark:border-zinc-700 pt-2">
                                <span>Nett</span>
                                <span>Rp {{ number_format($selectedPO->nett, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    @if (in_array($selectedPO->status, ['Draft', 'Approved']))
                        <div class="border-t dark:border-zinc-700 pt-5">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Ubah Status</h4>
                            <div class="flex items-center gap-3">
                                <select wire:model="selectedStatus"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-zinc-800 dark:border-zinc-600 dark:text-white w-48">
                                    <option value="Draft">Draft</option>
                                    <option value="Approved">Approved</option>
                                </select>
                                <button wire:click="updateStatus" wire:loading.attr="disabled"
                                    class="px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium disabled:opacity-50 cursor-pointer">
                                    <span wire:loading.remove wire:target="updateStatus">Simpan Status</span>
                                    <span wire:loading wire:target="updateStatus">Menyimpan...</span>
                                </button>
                            </div>
                            @error('selectedStatus')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
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

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-6 w-full max-w-sm">
                <h3 class="text-lg font-semibold dark:text-white mb-2">Hapus Purchase Order?</h3>
                <p class="text-sm text-gray-400 mb-6">Data akan dipindahkan ke tempat sampah.</p>
                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
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
