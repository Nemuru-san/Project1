<div>
    <x-filter.card title="Filter Buku Besar" description="Pilih akun dan rentang tanggal untuk menampilkan mutasi dan saldo berjalan.">
        <x-filter.select model="accountFilter" label="Akun" class="sm:col-span-2 xl:min-w-[18rem] xl:w-auto xl:flex-1">
            <option value="">-- Pilih akun --</option>
            @foreach ($accounts as $item)
                <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->name }}</option>
            @endforeach
        </x-filter.select>
        <x-filter.date-range />
        <x-filter.per-page :options="[25, 50, 100]" :show-reset="filled($accountFilter)" />
    </x-filter.card>

    @if (! $account)
        <div class="rounded-lg border border-blue-300 bg-blue-50 px-4 py-8 text-center text-sm text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
            Pilih akun untuk menampilkan buku besar.
        </div>
    @else
        <div class="mb-3 grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                Saldo awal: <strong class="block text-lg">Rp {{ number_format($openingBalance, 0, ',', '.') }}</strong>
            </div>
            <div class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-950/30 dark:text-green-300">
                Total debit: <strong class="block text-lg">Rp {{ number_format($totalDebit, 0, ',', '.') }}</strong>
            </div>
            <div class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-950/30 dark:text-red-300">
                Total kredit: <strong class="block text-lg">Rp {{ number_format($totalCredit, 0, ',', '.') }}</strong>
            </div>
            <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                Saldo akhir ({{ $account->normal_balance === 'Debit' ? 'D' : 'K' }}): <strong class="block text-lg {{ $closingBalance < 0 ? 'text-red-600' : '' }}">Rp {{ number_format($closingBalance, 0, ',', '.') }}</strong>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">No. Jurnal</th><th class="px-4 py-3">Sumber</th>
                        <th class="px-4 py-3">Keterangan</th><th class="px-4 py-3 text-right">Debit</th><th class="px-4 py-3 text-right">Kredit</th>
                        <th class="px-4 py-3 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($lines as $line)
                        <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                            <td class="px-4 py-3">{{ $line['date']->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-mono text-gray-900 dark:text-white">{{ $line['code'] }}</td>
                            <td class="px-4 py-3">{{ $line['source'] ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $line['description'] ?: '-' }}</td>
                            <td class="px-4 py-3 text-right text-green-600 dark:text-green-400">{{ $line['debit'] ? number_format($line['debit'], 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">{{ $line['credit'] ? number_format($line['credit'], 0, ',', '.') : '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white">{{ number_format($line['balance'], 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">Tidak ada mutasi pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $lines->links() }}</div>
    @endif
</div>
