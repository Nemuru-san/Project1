<div>
    <x-filter.card title="Filter Kartu Stok" description="Pilih produk, gudang, dan rentang tanggal untuk menampilkan kartu stok.">
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
        <x-filter.date-range />
        <x-filter.per-page :options="[25, 50, 100]" :show-reset="filled($productFilter) || filled($warehouseFilter) || filled($dateFrom) || filled($dateTo)" />
    </x-filter.card>

    @if (! $productFilter)
        <div class="rounded-lg border border-blue-300 bg-blue-50 px-4 py-8 text-center text-sm text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
            Pilih produk untuk menampilkan kartu stok.
        </div>
    @else
        <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                QOH awal periode: <strong class="block text-lg">{{ number_format($openingBalance, 0, ',', '.') }}</strong>
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
                        <th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Referensi</th><th class="px-4 py-3">Jenis Transaksi</th>
                        <th class="px-4 py-3">Gudang</th><th class="px-4 py-3 text-right">Masuk</th><th class="px-4 py-3 text-right">Keluar</th>
                        <th class="px-4 py-3 text-right">Saldo</th><th class="px-4 py-3">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($movements as $movement)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-mono text-gray-900 dark:text-white">{{ $movement['reference'] }}</td>
                            <td class="px-4 py-3">{{ $movement['type'] }}</td><td class="px-4 py-3">{{ $movement['warehouse_name'] }}</td>
                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ $movement['quantity_in'] ? number_format($movement['quantity_in'], 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $movement['quantity_out'] ? number_format($movement['quantity_out'], 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($movement['balance'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3">{{ $movement['note'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">Tidak ada mutasi stok pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $movements->links() }}</div>
    @endif
</div>
