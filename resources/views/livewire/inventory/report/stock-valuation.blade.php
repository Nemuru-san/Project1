<div>
    <x-filter.card title="Filter Nilai Persediaan" description="Nilai persediaan dihitung dari stok saat ini dikali harga pokok rata-rata (moving average) tiap produk.">
        <x-filter.search placeholder="Cari kode atau nama produk..." />
        <x-filter.select model="warehouseFilter" label="Gudang">
            <option value="">Semua gudang</option>
            @foreach ($warehouses as $warehouse)
                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
            @endforeach
        </x-filter.select>
        <x-filter.select model="categoryFilter" label="Kategori">
            <option value="">Semua kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </x-filter.select>
        <x-filter.checkbox model="showZeroBalance" label="Tampilkan stok 0" />
        <x-filter.per-page :options="[25, 50, 100]" :show-reset="filled($search) || filled($warehouseFilter) || filled($categoryFilter) || $showZeroBalance" />
    </x-filter.card>

    <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
            Jumlah produk: <strong class="block text-lg">{{ number_format($productCount, 0, ',', '.') }}</strong>
        </div>
        <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-950/30 dark:text-green-300">
            Total nilai persediaan: <strong class="block text-lg">Rp {{ number_format($totalValue, 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3">Kode</th>
                    <th class="px-4 py-3">Produk</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3 text-right">Qty (satuan dasar)</th>
                    <th class="px-4 py-3 text-right">Harga Pokok Rata-rata</th>
                    <th class="px-4 py-3 text-right">Nilai Persediaan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse ($rows as $row)
                    <tr wire:key="valuation-{{ $row->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                        <td class="px-4 py-3 font-mono text-gray-900 dark:text-white">{{ $row->sku }}</td>
                        <td class="px-4 py-3">{{ $row->name }}</td>
                        <td class="px-4 py-3">{{ $row->category_name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right {{ $row->quantity < 0 ? 'text-red-600' : '' }}">
                            {{ number_format($row->quantity, 0, ',', '.') }} {{ $row->unit_name }}
                        </td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($row->average_cost, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">Rp {{ number_format($row->stock_value, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Tidak ada data persediaan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $rows->links() }}</div>
</div>
