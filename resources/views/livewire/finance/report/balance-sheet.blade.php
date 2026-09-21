@php
    $rp = fn ($v) => ($v < 0 ? '(' : '').'Rp '.number_format(abs($v), 0, ',', '.').($v < 0 ? ')' : '');
    $balanced = $assets['total'] === $totalLiabilitiesEquity;
@endphp
<div>
    <x-filter.card title="Filter Neraca" description="Posisi aset, kewajiban, dan ekuitas per tanggal tertentu.">
        <div class="min-w-0 xl:w-48">
            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Per tanggal</label>
            <input wire:model.live="asOf" type="date"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
        </div>
    </x-filter.card>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
            Total aset: <strong class="block text-lg">{{ $rp($assets['total']) }}</strong>
        </div>
        <div class="rounded-lg bg-gray-100 px-4 py-3 text-sm text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
            Kewajiban + ekuitas: <strong class="block text-lg">{{ $rp($totalLiabilitiesEquity) }}</strong>
        </div>
        <div class="rounded-lg px-4 py-3 text-sm {{ $balanced ? 'bg-green-50 text-green-700 dark:bg-green-950/30 dark:text-green-300' : 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300' }}">
            Status: <strong class="block text-lg">{{ $balanced ? 'Seimbang' : 'Selisih '.$rp($assets['total'] - $totalLiabilitiesEquity) }}</strong>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                    <tr><th colspan="2" class="px-4 py-3">Aset</th><th class="px-4 py-3 text-right">Jumlah</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($assets['rows'] as $row)
                        <tr><td class="px-4 py-2 font-mono">{{ $row['code'] }}</td><td class="px-4 py-2">{{ $row['name'] }}</td><td class="px-4 py-2 text-right">{{ $rp($row['amount']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-2 text-gray-400">Tidak ada saldo.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100 font-bold text-gray-900 dark:bg-zinc-800 dark:text-white">
                        <td colspan="2" class="px-4 py-3">Total Aset</td><td class="px-4 py-3 text-right">{{ $rp($assets['total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                    <tr><th colspan="2" class="px-4 py-3">Kewajiban</th><th class="px-4 py-3 text-right">Jumlah</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($liabilities['rows'] as $row)
                        <tr><td class="px-4 py-2 font-mono">{{ $row['code'] }}</td><td class="px-4 py-2">{{ $row['name'] }}</td><td class="px-4 py-2 text-right">{{ $rp($row['amount']) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-2 text-gray-400">Tidak ada saldo.</td></tr>
                    @endforelse
                    <tr class="font-semibold text-gray-900 dark:text-white"><td colspan="2" class="px-4 py-2">Total Kewajiban</td><td class="px-4 py-2 text-right">{{ $rp($liabilities['total']) }}</td></tr>
                </tbody>
                <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                    <tr><th colspan="3" class="px-4 py-3">Ekuitas</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                    @foreach ($equity['rows'] as $row)
                        <tr><td class="px-4 py-2 font-mono">{{ $row['code'] }}</td><td class="px-4 py-2">{{ $row['name'] }}</td><td class="px-4 py-2 text-right">{{ $rp($row['amount']) }}</td></tr>
                    @endforeach
                    <tr><td class="px-4 py-2 font-mono">-</td><td class="px-4 py-2">Laba (Rugi) Berjalan</td><td class="px-4 py-2 text-right {{ $currentEarnings < 0 ? 'text-red-600' : '' }}">{{ $rp($currentEarnings) }}</td></tr>
                    <tr class="font-semibold text-gray-900 dark:text-white"><td colspan="2" class="px-4 py-2">Total Ekuitas</td><td class="px-4 py-2 text-right">{{ $rp($totalEquity) }}</td></tr>
                </tbody>
                <tfoot>
                    <tr class="bg-gray-100 font-bold text-gray-900 dark:bg-zinc-800 dark:text-white">
                        <td colspan="2" class="px-4 py-3">Total Kewajiban + Ekuitas</td><td class="px-4 py-3 text-right">{{ $rp($totalLiabilitiesEquity) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
