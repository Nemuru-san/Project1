<div>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Jumlah SO', $summary['count'], 'text-blue-600'], ['Qty Pesanan', number_format($summary['ordered'], 0, ',', '.'), 'text-gray-900 dark:text-white'], ['Sudah Dikirim', number_format($summary['shipped'], 0, ',', '.'), 'text-green-600'], ['Qty Belum Dikirim', number_format($summary['outstanding'], 0, ',', '.'), 'text-amber-600']] as [$label, $value, $color])
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-xs font-medium uppercase text-gray-400">{{ $label }}</p>
                <p class="mt-2 text-2xl font-bold {{ $color }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-5 flex flex-col gap-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Daftar SO Belum Selesai</h2>
            <p class="text-sm text-gray-500">Sales Order terkonfirmasi yang masih memiliki qty belum dikirim.</p>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari SO / pelanggan"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white xl:col-span-2">
            <select wire:model.live="customerFilter"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="">Semua pelanggan</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="warehouseFilter"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="">Semua gudang</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
            <select wire:model.live="deliveryFilter"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="">Semua status kirim</option>
                <option value="pending">Menunggu Pengiriman</option>
                <option value="partial">Dikirim Sebagian</option>
            </select>
            <select wire:model.live="perPage"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>
        </div>
        <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center"><span
                class="text-sm font-medium text-gray-600 dark:text-gray-300">Rentang tanggal SO</span><input
                wire:model.live="dateFrom" type="date" aria-label="Tanggal mulai"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white"><span
                class="hidden text-gray-400 sm:inline">s.d.</span><input wire:model.live="dateTo" type="date"
                aria-label="Tanggal akhir"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white"><button
                wire:click="resetFilters" type="button"
                class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2.5 text-sm hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-zinc-800">Bersihkan
                Filter</button></div>
    </div>

    <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
        <table class="w-full min-w-[1150px] text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3">No.</th>
                    <th wire:click="sortBy('order_no')" class="cursor-pointer px-4 py-3">Nomor SO</th>
                    <th wire:click="sortBy('date')" class="cursor-pointer px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Pelanggan</th>
                    <th class="px-4 py-3">Sumber</th>
                    <th class="px-4 py-3 text-right">Pesanan</th>
                    <th class="px-4 py-3 text-right">Dikirim</th>
                    <th class="px-4 py-3 text-right">Sisa</th>
                    <th class="px-4 py-3 text-center">Surat Jalan</th>
                    <th class="px-4 py-3 text-center">Umur</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($salesOrders as $index => $order)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                        <td class="px-4 py-3 text-gray-400">{{ $salesOrders->firstItem() + $index }}</td>
                        <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $order->order_no }}
                        </td>
                        <td class="px-4 py-3">{{ $order->date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $order->customer?->name }}</td>
                        <td class="px-4 py-3">
                            {{ $order->preOrder?->pre_order_no ?? ($order->salesCanvas?->canvas_no ?? 'Manual') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($order->ordered_qty, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-green-600">
                            {{ number_format($order->shipped_qty, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-amber-600">
                            {{ number_format($order->outstanding_qty, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center">{{ $order->delivery_orders_count }}</td>
                        <td class="px-4 py-3 text-center">{{ $order->age_days }} hari</td>
                        <td class="px-4 py-3">
                            @if ((int) $order->shipped_qty > 0)
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs text-blue-700">Dikirim
                                Sebagian</span>@else<span
                                    class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-700">Menunggu
                                    Pengiriman</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-block" x-data="{ open: false, top: 0, left: 0, toggle(el) { const r = el.getBoundingClientRect();
                                    this.top = r.bottom + 6;
                                    this.left = Math.max(8, r.right - 192);
                                    this.open = !this.open } }"><button @click="toggle($el)"
                                    @click.outside="open=false"
                                    class="cursor-pointer p-0.5 text-gray-500 hover:text-gray-800 dark:text-gray-400"><svg
                                        class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg></button>
                                <div x-cloak x-show="open" :style="`position:fixed;top:${top}px;left:${left}px`"
                                    class="z-50 w-48 rounded bg-white py-1 text-left shadow dark:bg-gray-700"><a
                                        href="{{ route('sales.transaction.salesOrder', ['order' => $order->id]) }}"
                                        wire:navigate
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600"><svg
                                            class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.5 12C3.7 8 7.5 5 12 5s8.3 3 9.5 7c-1.2 4-5 7-9.5 7s-8.3-3-9.5-7z" />
                                        </svg>Rincian SO</a><a href="{{ route('sales.transaction.deliveryOrder') }}"
                                        wire:navigate
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-blue-600 hover:bg-blue-600 hover:text-white"><svg
                                            class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 7h11v10H3V7Zm11 3h4l3 3v4h-7" />
                                        </svg>Surat Jalan</a></div>
                            </div>
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="12" class="px-4 py-10 text-center text-gray-400">Tidak ada Sales Order yang
                            belum selesai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $salesOrders->links() }}</div>
</div>
