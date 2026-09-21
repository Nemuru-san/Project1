@php
    $rp = fn ($v) => ($v < 0 ? '(' : '').'Rp '.number_format(abs($v), 0, ',', '.').($v < 0 ? ')' : '');
@endphp
<div>
    <x-filter.card title="Filter Laba Rugi" description="Pendapatan, harga pokok penjualan, dan beban pada periode yang dipilih.">
        <x-filter.date-range />
    </x-filter.card>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
            Pendapatan bersih: <strong class="block text-lg">{{ $rp($revenue['total']) }}</strong>
        </div>
        <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
            Laba kotor: <strong class="block text-lg {{ $grossProfit < 0 ? 'text-red-600' : '' }}">{{ $rp($grossProfit) }}</strong>
        </div>
        <div class="rounded-lg px-4 py-3 text-sm {{ $netIncome < 0 ? 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300' : 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300' }}">
            Laba bersih: <strong class="block text-lg">{{ $rp($netIncome) }}</strong>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Akun</th><th class="px-4 py-3 text-right">Jumlah</th></tr>
            </thead>
            @foreach ([['label' => 'Pendapatan', 'data' => $revenue], ['label' => 'Harga Pokok Penjualan', 'data' => $cogs]] as $group)
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    <tr class="bg-gray-50 dark:bg-zinc-800/60"><td colspan="3" class="px-4 py-2 font-semibold text-gray-900 dark:text-white">{{ $group['label'] }}</td></tr>
                    @forelse ($group['data']['rows'] as $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                            <td class="px-4 py-2 font-mono">{{ $row['code'] }}</td>
                            <td class="px-4 py-2">{{ $row['name'] }}</td>
                            <td class="px-4 py-2 text-right">{{ $rp($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-2 text-gray-400">Tidak ada transaksi.</td></tr>
                    @endforelse
                    <tr class="font-semibold text-gray-900 dark:text-white">
                        <td colspan="2" class="px-4 py-2 text-right">Total {{ $group['label'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $rp($group['data']['total']) }}</td>
                    </tr>
                </tbody>
            @endforeach
            <tbody>
                <tr class="bg-gray-100 font-bold text-gray-900 dark:bg-zinc-800 dark:text-white">
                    <td colspan="2" class="px-4 py-3 text-right">Laba Kotor</td>
                    <td class="px-4 py-3 text-right {{ $grossProfit < 0 ? 'text-red-600' : '' }}">{{ $rp($grossProfit) }}</td>
                </tr>
            </tbody>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                <tr class="bg-gray-50 dark:bg-zinc-800/60"><td colspan="3" class="px-4 py-2 font-semibold text-gray-900 dark:text-white">Beban Operasional</td></tr>
                @forelse ($expense['rows'] as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                        <td class="px-4 py-2 font-mono">{{ $row['code'] }}</td>
                        <td class="px-4 py-2">{{ $row['name'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $rp($row['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-2 text-gray-400">Tidak ada transaksi.</td></tr>
                @endforelse
                <tr class="font-semibold text-gray-900 dark:text-white">
                    <td colspan="2" class="px-4 py-2 text-right">Total Beban</td>
                    <td class="px-4 py-2 text-right">{{ $rp($expense['total']) }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="bg-gray-100 font-bold text-gray-900 dark:bg-zinc-800 dark:text-white">
                    <td colspan="2" class="px-4 py-3 text-right">Laba (Rugi) Bersih</td>
                    <td class="px-4 py-3 text-right {{ $netIncome < 0 ? 'text-red-600' : 'text-green-600' }}">{{ $rp($netIncome) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
