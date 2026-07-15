<div>
    <div class="flex flex-col gap-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Daftar PO Belum Selesai</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Hanya PO Approved dan Partially Received yang masih memiliki sisa penerimaan.</p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari PO / supplier"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white xl:col-span-2">
            <select wire:model.live="supplierFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="">Semua supplier</option>
                @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
            </select>
            <select wire:model.live="statusFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach
            </select>
            <select wire:model.live="perPage" class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="10">10 / hal</option><option value="25">25 / hal</option><option value="50">50 / hal</option>
            </select>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Rentang tanggal</span>
            <input wire:model.live="dateFrom" type="date" title="Tanggal mulai" aria-label="Tanggal mulai"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
            <span class="hidden sm:inline text-gray-400">s.d.</span>
            <input wire:model.live="dateTo" type="date" title="Tanggal akhir" aria-label="Tanggal akhir"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
            <button wire:click="resetFilters" type="button"
                class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-zinc-800 cursor-pointer">
                Bersihkan Filter
            </button>
        </div>
    </div>

    <div class="mt-4 overflow-x-auto">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-100 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3">No.</th>
                    <th wire:click="sortBy('code')" class="cursor-pointer px-4 py-3">Kode PO</th>
                    <th wire:click="sortBy('date')" class="cursor-pointer px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Supplier</th>
                    <th class="px-4 py-3 text-right">Dipesan</th>
                    <th class="px-4 py-3 text-right">Diterima</th>
                    <th class="px-4 py-3 text-right">Sisa</th>
                    <th wire:click="sortBy('status')" class="cursor-pointer px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Nilai PO</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse ($purchaseOrders as $index => $po)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                        <td class="px-4 py-3 text-gray-400">{{ $purchaseOrders->firstItem() + $index }}</td>
                        <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $po->code }}</td>
                        <td class="px-4 py-3">{{ $po->date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $po->supplier?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($po->ordered_qty, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($po->received_qty, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-amber-600 dark:text-amber-400">{{ number_format($po->outstanding_qty, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ $po->status }}</span></td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($po->nett, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><a href="{{ route('purchases.transaction.purchase-order.print', $po) }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Cetak</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-4 py-10 text-center text-gray-400">Tidak ada PO yang belum selesai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $purchaseOrders->links() }}</div>
</div>
