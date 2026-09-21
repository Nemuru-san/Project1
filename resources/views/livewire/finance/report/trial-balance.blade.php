@php $rp = fn ($v) => $v ? number_format($v, 0, ',', '.') : '-'; @endphp
<div>
    <x-filter.card title="Filter Neraca Saldo" description="Saldo awal, mutasi, dan saldo akhir seluruh akun pada periode yang dipilih.">
        <x-filter.date-range />
        <x-filter.checkbox model="hideZero" label="Sembunyikan akun tanpa saldo" />
    </x-filter.card>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th rowspan="2" class="px-4 py-2 align-bottom">Kode</th>
                    <th rowspan="2" class="px-4 py-2 align-bottom">Nama Akun</th>
                    <th colspan="2" class="border-b border-gray-200 px-4 py-2 text-center dark:border-zinc-700">Saldo Awal</th>
                    <th colspan="2" class="border-b border-gray-200 px-4 py-2 text-center dark:border-zinc-700">Mutasi</th>
                    <th colspan="2" class="border-b border-gray-200 px-4 py-2 text-center dark:border-zinc-700">Saldo Akhir</th>
                </tr>
                <tr>
                    <th class="px-4 py-2 text-right">Debit</th><th class="px-4 py-2 text-right">Kredit</th>
                    <th class="px-4 py-2 text-right">Debit</th><th class="px-4 py-2 text-right">Kredit</th>
                    <th class="px-4 py-2 text-right">Debit</th><th class="px-4 py-2 text-right">Kredit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse ($rows as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                        <td class="px-4 py-2 font-mono text-gray-900 dark:text-white">{{ $row['code'] }}</td>
                        <td class="px-4 py-2">{{ $row['name'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $rp($row['opening_debit']) }}</td>
                        <td class="px-4 py-2 text-right">{{ $rp($row['opening_credit']) }}</td>
                        <td class="px-4 py-2 text-right text-green-600 dark:text-green-400">{{ $rp($row['debit']) }}</td>
                        <td class="px-4 py-2 text-right text-red-600 dark:text-red-400">{{ $rp($row['credit']) }}</td>
                        <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-white">{{ $rp($row['closing_debit']) }}</td>
                        <td class="px-4 py-2 text-right font-semibold text-gray-900 dark:text-white">{{ $rp($row['closing_credit']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">Belum ada jurnal yang diposting pada periode ini.</td></tr>
                @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot class="bg-gray-100 text-xs font-semibold uppercase text-gray-900 dark:bg-zinc-800 dark:text-white">
                    <tr>
                        <td colspan="2" class="px-4 py-3">Total</td>
                        <td class="px-4 py-3 text-right">{{ $rp($totals['opening_debit']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $rp($totals['opening_credit']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $rp($totals['debit']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $rp($totals['credit']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $rp($totals['closing_debit']) }}</td>
                        <td class="px-4 py-3 text-right">{{ $rp($totals['closing_credit']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
    @if ($rows->isNotEmpty() && $totals['closing_debit'] !== $totals['closing_credit'])
        <p class="mt-3 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700 dark:bg-red-950/30 dark:text-red-300">
            Total debit dan kredit tidak seimbang (selisih Rp {{ number_format(abs($totals['closing_debit'] - $totals['closing_credit']), 0, ',', '.') }}). Periksa jurnal yang tidak balance.
        </p>
    @endif
</div>
