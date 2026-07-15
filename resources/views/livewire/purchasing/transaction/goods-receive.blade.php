<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- FILTER BAR --}}
    <div
        class="flex flex-col md:flex-row items-center justify-between space-y-2 md:space-y-0 md:space-x-2 my-2 dark:bg-zinc-900">
        <p class="dark:text-white text-base font-semibold">Data Tabel Penerimaan Barang</p>

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
                    class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5 placeholder-gray-400"
                    placeholder="Cari GR, PO, supplier..." />
            </div>

            <select wire:model.live="statusFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Status</option>
                <option value="Draft">Draf</option>
                <option value="Received">Diterima</option>
                <option value="Cancelled">Dibatalkan</option>
            </select>

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
                    <th class="px-4 py-4 w-12">No.</th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('code')">
                        <div class="flex items-center gap-1">
                            Kode GR
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

                    <th class="px-4 py-4">Kode PO</th>
                    <th class="px-4 py-4">Pemasok</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>

            <tbody class="dark:bg-zinc-950 text-base text-white">
                @forelse($goodsReceives as $index => $gr)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $gr->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-4 py-4 text-gray-500">
                            {{ $goodsReceives->firstItem() + $index }}
                        </td>

                        <td class="px-4 py-4 font-mono font-medium text-gray-900 dark:text-white">
                            {{ $gr->code }}
                        </td>

                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            {{ $gr->date?->format('d/m/Y') }}
                        </td>

                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            {{ $gr->purchaseOrder?->code ?? '-' }}
                        </td>

                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            {{ $gr->supplier?->name ?? '-' }}
                        </td>

                        <td class="px-4 py-4">
                            @if ($gr->trashed())
                                <span class="text-sm font-normal px-2.5 py-0.5 rounded dark:bg-red-700 dark:text-white">
                                    Terhapus
                                </span>
                            @elseif ($gr->status === 'Draft')
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-gray-200 text-gray-700 dark:bg-zinc-600 dark:text-white">Draf
                                </span>
                            @elseif ($gr->status === 'Received')
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-700 dark:text-white">
                                    Diterima
                                </span>
                            @elseif ($gr->status === 'Cancelled')
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-red-100 text-red-700 dark:bg-red-700 dark:text-white">Dibatalkan
                                </span>
                            @else
                                <span
                                    class="text-sm font-normal px-2.5 py-0.5 rounded bg-gray-200 text-gray-700 dark:bg-zinc-600 dark:text-white">
                                    {{ $gr->status }}
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
                                    @if ($gr->trashed())
                                        <div class="px-4 py-2 text-sm text-gray-400">
                                            Data sudah terhapus
                                        </div>
                                    @else
                                        @php
                                            $locked = $gr->status !== 'Draft';
                                        @endphp

                                        <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                            @if ($gr->status === 'Draft')
                                                <li>
                                                    <button wire:click="confirmReceive({{ $gr->id }})"
                                                        @click="open = false"
                                                        class="flex items-center gap-2 w-full py-2 px-4 text-green-700 hover:bg-green-700 hover:text-white dark:text-green-300 dark:hover:bg-green-700 dark:hover:text-white cursor-pointer">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Diterima
                                                    </button>
                                                </li>
                                            @endif

                                            <li>
                                                <a href="{{ route('purchases.transaction.good-receive.print', $gr->id) }}"
                                                    target="_blank" @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M6 9V4h12v5M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v6H6v-6z" />
                                                    </svg>Cetak
                                                </a>
                                            </li>

                                            <li>
                                                <button wire:click="openDetail({{ $gr->id }})"
                                                    @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>Detail
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    @if (!$locked) wire:click="openEdit({{ $gr->id }})" @endif
                                                    @click="open = false" @disabled($locked)
                                                    class="flex items-center gap-2 w-full py-2 px-4 {{ $locked ? 'opacity-40 cursor-not-allowed text-gray-400 dark:text-gray-500' : 'hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer' }}">
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
                                            <button
                                                @if (!$locked) wire:click="confirmDelete({{ $gr->id }})" @endif
                                                @click="open = false" @disabled($locked)
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base {{ $locked ? 'opacity-40 cursor-not-allowed text-gray-400 dark:text-gray-500' : 'text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white cursor-pointer' }}">
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
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                            Tidak ada data goods receive ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $goodsReceives->links() }}
    </div>

    {{-- SHOW MODAL --}}
    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-full mx-auto h-[90vh] flex flex-col overflow-hidden"
                @click.outside="$wire.showModal = false">

                <div
                    class="flex items-center justify-between px-8 py-6 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">
                        {{ $editingId ? 'Edit Transaksi Goods Receive' : 'Buat Transaksi Goods Receive' }}
                    </h3>

                    <button wire:click="$set('showModal', false)"
                        class="text-gray-400 hover:text-white cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-8 py-6">
                    <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                GR No
                            </label>
                            <input type="text" wire:model="code"
                                class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed"
                                placeholder="Auto Generated" disabled>
                        </div>

                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Tanggal
                            </label>
                            <input type="date" wire:model="date"
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white @error('date') border-red-500 @else border-gray-300 @enderror">
                            @error('date')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                PO No
                            </label>
                            <select wire:model.live="purchase_order_id" @disabled($editingId)
                                class="bg-gray-50 border text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white @error('purchase_order_id') border-red-500 @else border-gray-300 @enderror">
                                <option value="">-- Pilih PO Disetujui / Diterima Sebagian --</option>
                                @foreach ($purchaseOrders as $po)
                                    <option value="{{ $po->id }}">
                                        {{ $po->code }} - {{ $po->supplier?->name ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('purchase_order_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Pemasok
                            </label>
                            <input type="text" wire:model="supplier_name"
                                class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed"
                                placeholder="Otomatis dari PO" disabled>
                        </div>
                    </div>

                    <div class="w-full mt-6">
                        <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                            Note
                        </label>
                        <textarea wire:model="note" rows="4"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white"
                            placeholder="Masukkan catatan atau keterangan tambahan..."></textarea>
                    </div>

                    {{-- DETAIL PRODUK --}}
                    <div class="mt-12">
                        <div class="w-full grid grid-cols-1 sm:grid-cols-2 gap-6 mb-4 items-center">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Detail Produk</h3>
                            </div>
                        </div>

                        @error('items')
                            <p class="text-red-500 text-sm mb-3">{{ $message }}</p>
                        @enderror

                        <div class="overflow-x-auto">
                            <table
                                class="w-full text-base text-left text-gray-900 dark:text-white my-2 min-w-425 whitespace-nowrap border-collapse border border-gray-300 dark:border-zinc-600">
                                <thead
                                    class="text-base font-bold text-gray-900 uppercase bg-gray-200 dark:bg-zinc-700 dark:text-white">
                                    <tr>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Tidak
                                        </th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                            Kode Produk</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                            Nama Produk</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                            Kategori</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                            Satuan</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Qty
                                            Pesanan</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Qty
                                            Outstanding</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Qty
                                            Diterima</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Gudang</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($items as $index => $item)
                                        <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                            <td
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-medium text-gray-900 dark:text-white">
                                                {{ $index + 1 }}
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $item['product_sku'] }}
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $item['product_name'] }}
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $item['category_name'] }}
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $item['unit_name'] }}
                                            </td>

                                            <td
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-gray-900 dark:text-white">
                                                {{ $item['qty_order'] }}
                                            </td>

                                            <td
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-gray-900 dark:text-white">
                                                {{ $item['qty_outstanding'] }}
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="text" inputmode="numeric" autocomplete="off"
                                                    x-data="{
                                                        display: '{{ number_format($item['qty_received'] ?? 0, 0, ',', '.') }}'
                                                    }" x-model="display"
                                                    @input="
                                                        let raw = display.replace(/\./g, '').replace(/\D/g, '');
                                                        display = raw === '' ? '' : Number(raw).toLocaleString('id-ID');
                                                        $wire.set('items.{{ $index }}.qty_received', raw === '' ? 0 : Number(raw));
                                                    "
                                                    class="bg-gray-50 border text-gray-900 text-xs rounded-lg block w-24 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white @error('items.' . $index . '.qty_received') border-red-500 @else border-gray-300 @enderror">

                                                @error('items.' . $index . '.qty_received')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <select wire:model="items.{{ $index }}.warehouse_id"
                                                    class="bg-gray-50 border text-gray-900 text-xs rounded-lg block w-full p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white @error('items.' . $index . '.warehouse_id') border-red-500 @else border-gray-300 @enderror">
                                                    <option value="">-- Pilih Gudang --</option>
                                                    @foreach ($warehouses as $warehouse)
                                                        <option value="{{ $warehouse->id }}">
                                                            {{ $warehouse->desc }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                @error('items.' . $index . '.warehouse_id')
                                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-8 text-center text-gray-400">
                                                Pilih PO Disetujui / Diterima Sebagian terlebih dahulu.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-2 p-4 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-white dark:bg-zinc-900">
                    <button wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                        Batal
                    </button>

                    <button wire:click="save" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="save">
                            {{ $editingId ? 'Update' : 'Simpan' }}
                        </span>
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
                <p class="text-sm text-gray-400 mb-6">
                    Yakin ingin menghapus Penerimaan Barang ini?
                </p>

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

    {{-- DETAIL MODAL --}}
    @if ($showDetailModal && $selectedGR)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b dark:border-zinc-700">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Detail Penerimaan Barang</h2>
                        <p class="text-sm text-gray-400 font-mono mt-0.5">{{ $selectedGR->code }}</p>
                    </div>

                    <button wire:click="closeDetail"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5 space-y-6">

                    {{-- Info Header --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Kode GR</span>
                                <span class="font-mono font-medium text-gray-800 dark:text-white">
                                    {{ $selectedGR->code }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Tanggal</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedGR->date?->format('d F Y') }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Kode PO</span>
                                <span class="font-mono text-gray-800 dark:text-white">
                                    {{ $selectedGR->purchaseOrder?->code ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Pemasok</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedGR->supplier?->name ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Status</span>
                                <span>
                                    @php
                                        $statusClass = match ($selectedGR->status) {
                                            'Draft' => 'bg-zinc-600 text-white',
                                            'Received' => 'bg-green-700 text-white',
                                            'Cancelled' => 'bg-red-700 text-white',
                                            default => 'bg-zinc-600 text-white',
                                        };
                                    @endphp

                                    <span class="text-sm px-2.5 py-0.5 rounded {{ $statusClass }}">
                                        {{ ucfirst($selectedGR->status) }}
                                    </span>
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Dibuat Oleh</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedGR->creator?->name ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Catatan</span>
                                <span class="text-gray-800 dark:text-white text-right max-w-xs">
                                    {{ $selectedGR->note ?: '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Tabel Item --}}
                    <div class="overflow-x-auto rounded-lg border dark:border-zinc-700">
                        <table class="w-full text-sm text-left">
                            <thead
                                class="bg-gray-50 dark:bg-zinc-800 text-gray-500 dark:text-gray-300 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 w-8">No.</th>
                                    <th class="px-4 py-3">Produk</th>
                                    <th class="px-4 py-3">Kategori</th>
                                    <th class="px-4 py-3">Satuan</th>
                                    <th class="px-4 py-3">Gudang</th>
                                    <th class="px-4 py-3 text-right">Jumlah Pesanan</th>
                                    <th class="px-4 py-3 text-right">Jumlah Diterimad</th>
                                    {{-- <th class="px-4 py-3 text-right">Qty Outstanding</th> --}}
                                    <th class="px-4 py-3 text-right">Jumlah Dasar</th>
                                </tr>
                            </thead>

                            <tbody class="dark:bg-zinc-900 divide-y divide-gray-100 dark:divide-zinc-700">
                                @forelse($selectedGR->items as $i => $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-3 text-gray-400">
                                            {{ $i + 1 }}
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            <div class="font-medium">
                                                {{ $item->product?->name ?? '-' }}
                                            </div>
                                            <div class="text-xs text-gray-400 font-mono">
                                                {{ $item->product?->sku ?? '' }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            {{ $item->product?->category?->name ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            {{ $item->unit?->name ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            {{ $item->warehouse?->name ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            {{ number_format($item->qty_order, 0, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            {{ number_format($item->qty_received, 0, ',', '.') }}
                                        </td>

                                        {{-- <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            {{ number_format($item->qty_outstanding, 0, ',', '.') }}
                                        </td> --}}

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            {{ number_format($item->qty_base, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-6 text-center text-gray-400">
                                            Tidak ada item.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Ubah Status --}}
                    @if (in_array($selectedGR->status, ['Draft', 'Received'], true))
                        <div class="border-t dark:border-zinc-700 pt-5">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                Ubah Status
                            </h4>

                            <div class="flex items-center gap-3">
                                <select wire:model="selectedStatus"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-zinc-800 dark:border-zinc-600 dark:text-white w-48">
                                    <option value="Draft">Draf</option>
                                    <option value="Received">Diterima</option>
                                    <option value="Cancelled">Dibatalkan</option>
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

                {{-- Footer --}}
                <div class="px-6 py-4 border-t dark:border-zinc-700 flex justify-end">
                    <button wire:click="closeDetail"
                        class="px-5 py-2 rounded-lg bg-zinc-700 hover:bg-zinc-600 text-white text-sm font-medium transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showReceiveModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-green-900 rounded-full">
                        <svg class="w-5 h-5 text-green-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h3 class="text-base font-semibold dark:text-white">
                        Terima Penerimaan Barang?
                    </h3>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    Setelah Penerimaan Barang diterima, stok produk akan bertambah sesuai qty diterima.
                    Data tidak bisa diedit atau dihapus lagi.
                </p>

                <div class="flex justify-end gap-2">
                    <button wire:click="cancelReceive"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 cursor-pointer">
                        Batal
                    </button>

                    <button wire:click="receive" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg bg-green-700 text-white hover:bg-green-800 disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="receive">
                            Ya, Terima
                        </span>
                        <span wire:loading wire:target="receive">
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
