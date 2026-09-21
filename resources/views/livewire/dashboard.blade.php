@php
    $rp = fn ($v) => ($v < 0 ? '-' : '').'Rp '.number_format(abs($v), 0, ',', '.');
    $card = 'rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900';
    $growth = ($canSales && ($salesLastMonth ?? 0) > 0) ? round((($salesThisMonth - $salesLastMonth) / $salesLastMonth) * 100) : null;
@endphp
<div class="flex flex-col gap-4">
    {{-- KPI --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if ($canSales)
            <div class="{{ $card }}">
                <p class="text-sm text-gray-500 dark:text-gray-400">Penjualan bulan ini</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $rp($salesThisMonth) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @if ($growth !== null)
                        <span class="{{ $growth >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ $growth >= 0 ? '+' : '' }}{{ $growth }}%</span> vs bulan lalu
                    @else
                        Bulan lalu: {{ $rp($salesLastMonth) }}
                    @endif
                </p>
            </div>
            <div class="{{ $card }}">
                <p class="text-sm text-gray-500 dark:text-gray-400">Piutang belum lunas</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $rp($receivable) }}</p>
                <p class="mt-1 text-xs {{ $overdueInvoices ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $overdueInvoices }} faktur lewat jatuh tempo</p>
            </div>
        @endif
        @if ($canPurchase)
            <div class="{{ $card }}">
                <p class="text-sm text-gray-500 dark:text-gray-400">Utang belum lunas</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $rp($payable) }}</p>
                <p class="mt-1 text-xs {{ $overduePurchase ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $overduePurchase }} faktur lewat jatuh tempo</p>
            </div>
        @endif
        @if ($canBank)
            <div class="{{ $card }}">
                <p class="text-sm text-gray-500 dark:text-gray-400">Saldo kas & bank</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $rp($cashAndBank) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dari jurnal yang sudah diposting</p>
            </div>
        @endif
        @if ($canFinance)
            <div class="{{ $card }}">
                <p class="text-sm text-gray-500 dark:text-gray-400">Laba bersih bulan ini</p>
                <p class="mt-1 text-2xl font-bold {{ $netIncomeThisMonth < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">{{ $rp($netIncomeThisMonth) }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><a href="{{ route('finance.report.profit-loss') }}" wire:navigate class="hover:underline">Lihat laporan laba rugi &rarr;</a></p>
            </div>
        @endif
    </div>

    @if (! $canSales && ! $canPurchase && ! $canBank && ! $canFinance && $pending->isEmpty())
        <div class="{{ $card }} text-center text-sm text-gray-500 dark:text-gray-400">
            Selamat datang, {{ auth()->user()->name }}. Gunakan menu di samping untuk membuka modul yang tersedia untuk Anda.
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        {{-- Grafik penjualan 6 bulan --}}
        @if ($canSales)
            @php $max = max(1, max(array_column($salesTrend, 'total'))); @endphp
            <div class="{{ $card }} xl:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Penjualan 6 bulan terakhir</h3>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Faktur terkonfirmasi</span>
                </div>
                <div class="flex h-48 items-end gap-3">
                    @foreach ($salesTrend as $month)
                        <div class="flex h-full flex-1 flex-col items-center justify-end gap-1" title="{{ $month['label'] }}: {{ $rp($month['total']) }}">
                            <span class="text-[10px] text-gray-500 dark:text-gray-400">{{ $month['total'] ? number_format($month['total'] / 1000000, 1, ',', '.').' jt' : '' }}</span>
                            <div class="w-full rounded-t-md bg-blue-500/80 transition-all dark:bg-blue-400/80" style="height: {{ max(2, round($month['total'] / $max * 100)) }}%"></div>
                            <span class="text-xs text-gray-600 dark:text-gray-300">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Perlu tindakan --}}
        @if ($pending->isNotEmpty())
            <div class="{{ $card }}">
                <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">Perlu tindakan</h3>
                <ul class="divide-y divide-gray-100 dark:divide-zinc-800">
                    @foreach ($pending as $item)
                        <li>
                            <a href="{{ route($item['route']) }}" wire:navigate class="flex items-center justify-between gap-3 py-2.5 text-sm hover:text-blue-600 dark:hover:text-blue-400">
                                <span class="text-gray-700 dark:text-gray-200">{{ $item['label'] }}</span>
                                <span class="inline-flex min-w-8 justify-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $item['count'] ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:text-gray-400' }}">{{ $item['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @if ($canSales)
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            {{-- Faktur terbaru --}}
            <div class="{{ $card }} xl:col-span-2 overflow-x-auto">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Faktur penjualan terbaru</h3>
                    <a href="{{ route('sales.transaction.salesInvoice') }}" wire:navigate class="text-xs text-blue-600 hover:underline dark:text-blue-400">Lihat semua</a>
                </div>
                <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400">
                        <tr><th class="py-2 pr-3">No. Faktur</th><th class="py-2 pr-3">Tanggal</th><th class="py-2 pr-3">Pelanggan</th><th class="py-2 pr-3 text-right">Total</th><th class="py-2 text-right">Sisa</th><th class="py-2 pl-3">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @forelse ($recentInvoices as $invoice)
                            <tr>
                                <td class="py-2 pr-3 font-mono text-gray-900 dark:text-white">{{ $invoice->invoice_no }}</td>
                                <td class="py-2 pr-3">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                                <td class="py-2 pr-3">{{ $invoice->customer?->name ?? '-' }}</td>
                                <td class="py-2 pr-3 text-right">{{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                                <td class="py-2 text-right {{ $invoice->amount_due > 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ number_format($invoice->amount_due, 0, ',', '.') }}</td>
                                <td class="py-2 pl-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $invoice->status === \App\Models\SalesInvoice::STATUS_CONFIRMED ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-gray-300' }}">{{ $invoice->status === \App\Models\SalesInvoice::STATUS_CONFIRMED ? 'Terkonfirmasi' : 'Draf' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-gray-400">Belum ada faktur penjualan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Produk terlaris --}}
            <div class="{{ $card }}">
                <h3 class="mb-3 font-semibold text-gray-900 dark:text-white">Produk terlaris bulan ini</h3>
                @php $topMax = max(1, (int) ($topProducts->max('total') ?? 0)); @endphp
                <ul class="space-y-3">
                    @forelse ($topProducts as $product)
                        <li>
                            <div class="flex items-center justify-between text-sm">
                                <span class="truncate text-gray-700 dark:text-gray-200">{{ $product->name }}</span>
                                <span class="ml-2 shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ number_format($product->qty, 0, ',', '.') }} pcs</span>
                            </div>
                            <div class="mt-1 h-1.5 w-full rounded-full bg-gray-100 dark:bg-zinc-800">
                                <div class="h-1.5 rounded-full bg-emerald-500" style="width: {{ round($product->total / $topMax * 100) }}%"></div>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-gray-400">Belum ada penjualan bulan ini.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    @endif
</div>
