<div>
    {{-- FILTER BAR --}}
    <div
        class="flex flex-col md:flex-row items-center justify-between space-y-2 md:space-y-0 md:space-x-2 my-2 dark:bg-zinc-900">

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
                    placeholder="Cari SKU, produk, warehouse..." />
            </div>

            {{-- Warehouse --}}
            <select wire:model.live="warehouseFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Gudang</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>

            {{-- Category --}}
            <select wire:model.live="categoryFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">
                        {{ $category->desc }}
                    </option>
                @endforeach
            </select>

            {{-- Per Page --}}
            <select wire:model.live="perPage"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 w-full sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>

            {{-- Show Zero Balance --}}
            <label class="flex items-center gap-2 text-sm dark:text-gray-300 cursor-pointer whitespace-nowrap">
                <input type="checkbox" wire:model.live="showZeroBalance"
                    class="w-4 h-4 rounded border-gray-600 dark:bg-zinc-800 text-blue-600">
                Show Zero Balance
            </label>
        </div>
    </div>

    @if ($showZeroBalance && !$warehouseFilter)
        <div
            class="mt-3 rounded-lg border border-yellow-500/40 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200 px-4 py-3 text-sm">
            Pilih warehouse dulu untuk menampilkan zero balance.
        </div>
    @endif

    {{-- TABLE --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-sm font-bold text-white uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 w-12">No.</th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('sku')">
                        <div class="flex items-center gap-1">
                            SKU
                            @if ($sortField === 'sku')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('name')">
                        <div class="flex items-center gap-1">
                            Nama Produk
                            @if ($sortField === 'name')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4">Kategori</th>

                    <th class="px-4 py-4">Gudang</th>

                    <th class="px-4 py-4 cursor-pointer select-none text-right" wire:click="sortBy('quantity')">
                        <div class="flex items-center justify-end gap-1">
                            Quantity
                            @if ($sortField === 'quantity')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>

            <tbody class="dark:bg-zinc-950 text-sm text-white">
                @forelse($stockBalances as $index => $row)
                    @if ($isZeroMode)
                        @php
                            $product = $row;
                            $balance = $product->stockBalances->first();
                            $quantity = $balance?->quantity ?? 0;
                            $warehouseName = $selectedWarehouse?->desc ?? '-';
                        @endphp
                    @else
                        @php
                            $product = $row->product;
                            $quantity = $row->quantity;
                            $warehouseName = $row->warehouse?->name ?? '-';
                        @endphp
                    @endif

                    <tr class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800">
                        <td class="px-4 py-4 text-gray-500">
                            {{ $stockBalances->firstItem() + $index }}
                        </td>

                        <td class="px-4 py-4 font-mono font-medium text-gray-900 dark:text-white">
                            {{ $product?->sku ?? '-' }}
                        </td>

                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            {{ $product?->name ?? '-' }}
                        </td>

                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            {{ $product?->category?->name ?? '-' }}
                        </td>

                        <td class="px-4 py-4 text-gray-900 dark:text-white">
                            {{ $warehouseName }}
                        </td>

                        <td class="px-4 py-4 text-right font-semibold text-gray-900 dark:text-white">
                            {{ $this->formatStockQuantity($product, (int) $quantity) }}
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

                                    <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                        <li>
                                            <button
                                                wire:click="openDetail({{ $product?->id ?? 0 }}, {{ $isZeroMode ? ($warehouseFilter ?: 0) : $row->warehouse_id ?? 0 }})"
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
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400 dark:text-gray-500">
                            Tidak ada data stok balance ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $stockBalances->links() }}
    </div>

    {{-- DETAIL STOCK BOOKING MODAL --}}
    @if ($showDetailModal && $selectedStock)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-hidden bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[min(80vh,calc(100dvh-2rem))] overflow-y-auto">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b dark:border-zinc-700">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Detail Stok Booking</h2>
                        <p class="text-sm text-gray-400 font-mono mt-0.5">
                            {{ $selectedStock['product_sku'] }} - {{ $selectedStock['product_name'] }}
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

                {{-- Body --}}
                <div class="px-6 py-5 space-y-6">

                    {{-- Info Header --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">SKU</span>
                                <span class="font-mono font-medium text-gray-800 dark:text-white">
                                    {{ $selectedStock['product_sku'] }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Produk</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedStock['product_name'] }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Kategori</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedStock['category_name'] }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Gudang</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedStock['warehouse_name'] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Table Booking --}}
                    <div class="overflow-x-auto rounded-lg border dark:border-zinc-700">
                        <table class="w-full text-sm text-left">
                            <thead
                                class="bg-gray-50 dark:bg-zinc-800 text-gray-500 dark:text-gray-300 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 w-8">No.</th>
                                    <th class="px-4 py-3">SO Kode</th>
                                    <th class="px-4 py-3">Kustomer</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3 text-right">Jumlah Pesanan</th>
                                    <th class="px-4 py-3 text-right">Qty Booking</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>

                            <tbody class="dark:bg-zinc-900 divide-y divide-gray-100 dark:divide-zinc-700">
                                @forelse ($stockBookings as $index => $booking)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-3 text-gray-400">
                                            {{ $index + 1 }}
                                        </td>

                                        <td class="px-4 py-3 font-mono text-gray-800 dark:text-white">
                                            {{ $booking['so_code'] }}
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            {{ $booking['customer_name'] }}
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            {{ $booking['date'] }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            {{ number_format($booking['qty_order'], 0, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-white">
                                            {{ number_format($booking['qty_booking'], 0, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3">
                                            @if ($booking['status'] === 'Approved')
                                                <span class="text-sm px-2.5 py-0.5 rounded bg-blue-700 text-white">Disetujui
                                                </span>
                                            @else
                                                <span class="text-sm px-2.5 py-0.5 rounded bg-zinc-600 text-white">
                                                    {{ $booking['status'] }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-gray-400">
                                            Tidak ada stok booking.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Summary --}}
                    <div class="flex justify-end">
                        <div class="space-y-2 text-sm w-full max-w-xs">
                            <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                <span>Total Booking</span>
                                <span>
                                    {{ number_format(collect($stockBookings)->sum('qty_booking'), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
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
</div>
