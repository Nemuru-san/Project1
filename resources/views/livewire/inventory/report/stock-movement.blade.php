<div>
    <x-filter.card title="Filter Pergerakan Stok" description="Temukan pergerakan berdasarkan produk, gudang, atau rentang tanggal.">
        <x-filter.search placeholder="Cari produk / gudang" />
        <x-filter.select model="productFilter" label="Produk" class="xl:w-72">
            <option value="">Semua produk</option>
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
        <x-filter.per-page :options="[25, 50, 100]" :show-reset="filled($search) || filled($productFilter) || filled($warehouseFilter) || filled($dateFrom) || filled($dateTo)" />
    </x-filter.card>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr><th class="px-4 py-3">SKU</th><th class="px-4 py-3">Produk</th><th class="px-4 py-3">Gudang</th>
                    <th class="px-4 py-3 text-right">Saldo Awal</th><th class="px-4 py-3 text-right">Masuk</th>
                    <th class="px-4 py-3 text-right">Keluar</th><th class="px-4 py-3 text-right">QOH Akhir Periode</th><th class="px-4 py-3 text-right">QOH Saat Ini</th><th class="px-4 py-3 text-right">AFS Saat Ini</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                        <td class="px-4 py-3 font-mono text-gray-900 dark:text-white">{{ $row['product_sku'] }}</td><td class="px-4 py-3">{{ $row['product_name'] }}</td>
                        <td class="px-4 py-3">{{ $row['warehouse_name'] }}</td><td class="px-4 py-3 text-right">{{ number_format($row['opening_balance'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ number_format($row['quantity_in'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ number_format($row['quantity_out'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($row['ending_balance'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">{{ $row['current_qoh_display'] }}</td>
                        <td class="px-4 py-3 text-right font-bold {{ $row['available_for_sales'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $row['available_for_sales_display'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-10 text-center text-gray-400">Tidak ada pergerakan stok pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $rows->links() }}</div>
</div>
