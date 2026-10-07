<div>
    <x-filter.card title="Filter Kartu Stok" description="Pilih produk, gudang, jenis, dan rentang tanggal untuk menampilkan kartu stok. Klik baris untuk melihat rinciannya.">
        <x-filter.select model="productFilter" label="Produk" class="sm:col-span-2 xl:min-w-[18rem] xl:w-auto xl:flex-1">
            <option value="">-- Pilih produk --</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
            @endforeach
        </x-filter.select>
        <x-filter.select model="warehouseFilter" label="Gudang">
            <option value="">Semua gudang</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
            @endforeach
        </x-filter.select>
        <x-filter.select model="typeFilter" label="Jenis">
            <option value="">Semua</option>
            <option value="in">Masuk</option>
            <option value="out">Keluar</option>
        </x-filter.select>
        <x-filter.date-range />
        <x-filter.per-page :options="[25, 50, 100]" :show-reset="filled($productFilter) || filled($warehouseFilter) || filled($typeFilter) || filled($dateFrom) || filled($dateTo)" />
    </x-filter.card>

    @if (! $productFilter)
        <div class="rounded-lg border border-blue-300 bg-blue-50 px-4 py-8 text-center text-sm text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
            Pilih produk untuk menampilkan kartu stok.
        </div>
    @else
        <div class="mb-3 grid grid-cols-2 gap-3 lg:grid-cols-3">
            <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                Saldo awal: <strong class="block text-lg">{{ number_format($openingBalance, 0, ',', '.') }}</strong>
            </div>
            <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-950/30 dark:text-green-300">
                Total masuk: <strong class="block text-lg">{{ number_format($totalIn, 0, ',', '.') }}</strong>
            </div>
            <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950/30 dark:text-red-300">
                Total keluar: <strong class="block text-lg">{{ number_format($totalOut, 0, ',', '.') }}</strong>
            </div>
            <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                Saldo akhir periode: <strong class="block text-lg">{{ number_format($closingBalance, 0, ',', '.') }}</strong>
            </div>
            <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                QOH saat ini: <strong class="block text-lg">{{ $availability['quantity_on_hand_display'] }}</strong>
            </div>
            <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-950/30 dark:text-green-300">
                AFS saat ini: <strong class="block text-lg {{ $availability['available_for_sales'] < 0 ? 'text-red-600' : '' }}">{{ $availability['available_for_sales_display'] }}</strong>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">No. Transaksi</th><th class="px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Keterangan</th><th class="px-4 py-3 text-right">Masuk</th><th class="px-4 py-3 text-right">Keluar</th>
                        <th class="px-4 py-3 text-right">Saldo</th>
                    </tr>
                </thead>
                @forelse ($movements as $movement)
                    @php $isIn = $movement['quantity_in'] > 0; @endphp
                    <tbody wire:key="stock-card-{{ $movement['key'] }}" x-data="{ open: false }"
                        class="border-b border-gray-200 dark:border-zinc-700">
                        <tr tabindex="0" role="button" :aria-expanded="open.toString()"
                            @click="open = !open" @keydown.enter.prevent="open = !open" @keydown.space.prevent="open = !open"
                            class="cursor-pointer hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 dark:hover:bg-zinc-800/60">
                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-gray-900 dark:text-white">
                                <svg class="mr-1 inline h-3 w-3 text-gray-400 transition-transform motion-reduce:transition-none" :class="open && 'rotate-90'"
                                    fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M7 5l6 5-6 5V5z" />
                                </svg>{{ $movement['reference'] }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $isIn ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' }}">{{ $isIn ? 'Masuk' : 'Keluar' }}</span>
                                <span class="ml-1 text-xs text-gray-500 dark:text-gray-400">{{ $movement['type'] }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $movement['description'] }}</td>
                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ $movement['quantity_in'] ? number_format($movement['quantity_in'], 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $movement['quantity_out'] ? number_format($movement['quantity_out'], 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($movement['balance'], 0, ',', '.') }}</td>
                        </tr>
                        <tr x-show="open" x-cloak>
                            <td colspan="7" class="bg-gray-50 px-4 py-3 dark:bg-zinc-800/40">
                                <p class="mb-2 text-xs font-semibold text-gray-700 dark:text-gray-200">
                                    Rincian {{ $movement['reference'] }}{{ $baseUnitName ? ' (qty dasar: '.$baseUnitName.')' : '' }}
                                </p>
                                <table class="w-full rounded-lg border border-gray-200 bg-white text-left text-sm dark:border-zinc-700 dark:bg-zinc-900">
                                    <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                                        <tr>
                                            <th class="px-3 py-2">Gudang</th><th class="px-3 py-2 text-right">Qty Transaksi</th>
                                            <th class="px-3 py-2 text-right">Qty Dasar</th><th class="px-3 py-2">Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                        @foreach ($movement['details'] as $detail)
                                            @php $detailQty = $detail['quantity_in'] ?: $detail['quantity_out']; @endphp
                                            <tr>
                                                <td class="px-3 py-2">{{ $detail['warehouse_name'] }}</td>
                                                <td class="px-3 py-2 text-right">
                                                    {{ $detail['unit_quantity'] !== null ? number_format($detail['unit_quantity'], 0, ',', '.').' '.($detail['unit_name'] ?? '') : '-' }}
                                                </td>
                                                <td class="px-3 py-2 text-right {{ $isIn ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $isIn ? '+' : '-' }}{{ number_format($detailQty, 0, ',', '.') }}
                                                </td>
                                                <td class="px-3 py-2">{{ $detail['note'] ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Tidak ada mutasi stok pada filter ini. Ubah rentang tanggal atau jenis.</td></tr>
                    </tbody>
                @endforelse
            </table>
        </div>
        <div class="mt-4">{{ $movements->links() }}</div>
    @endif
</div>
