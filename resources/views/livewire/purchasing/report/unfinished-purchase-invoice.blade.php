<div>
    <div class="flex flex-col gap-4">
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Daftar Faktur Belum Selesai</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Hanya faktur Posted dengan sisa tagihan lebih dari nol.</p>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari faktur / PO / supplier"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white xl:col-span-2">
            <select wire:model.live="supplierFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="">Semua supplier</option>
                @foreach ($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach
            </select>
            <select wire:model.live="paymentStatusFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="">Semua status bayar</option>
                @foreach ($paymentStatuses as $status)<option value="{{ $status }}">{{ $status }}</option>@endforeach
            </select>
            <select wire:model.live="dueFilter" class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                <option value="">Semua jatuh tempo</option><option value="overdue">Sudah lewat tempo</option><option value="not_due">Belum lewat tempo</option>
            </select>
            <select wire:model.live="perPage" class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
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
                    <th class="px-4 py-3">No.</th><th wire:click="sortBy('code')" class="cursor-pointer px-4 py-3">Kode Faktur</th>
                    <th class="px-4 py-3">Faktur Supplier</th><th wire:click="sortBy('date')" class="cursor-pointer px-4 py-3">Tanggal</th>
                    <th wire:click="sortBy('due_date')" class="cursor-pointer px-4 py-3">Jatuh Tempo</th><th class="px-4 py-3">Supplier</th><th class="px-4 py-3">PO</th>
                    <th wire:click="sortBy('grand_total')" class="cursor-pointer px-4 py-3 text-right">Total</th><th class="px-4 py-3 text-right">Terbayar</th>
                    <th wire:click="sortBy('remaining_amount')" class="cursor-pointer px-4 py-3 text-right">Sisa</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                @forelse ($invoices as $index => $invoice)
                    @php($overdue = $invoice->due_date && $invoice->due_date->isPast())
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                        <td class="px-4 py-3 text-gray-400">{{ $invoices->firstItem() + $index }}</td>
                        <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $invoice->code }}</td>
                        <td class="px-4 py-3">{{ $invoice->supplier_invoice_number ?: '-' }}</td><td class="px-4 py-3">{{ $invoice->date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 {{ $overdue ? 'font-semibold text-red-600 dark:text-red-400' : '' }}">{{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $invoice->supplier?->name ?? '-' }}</td><td class="px-4 py-3 font-mono">{{ $invoice->purchaseOrder?->code ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td><td class="px-4 py-3 text-right">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-amber-600 dark:text-amber-400">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">{{ $invoice->payment_status }}</span></td>
                        <td class="px-4 py-3"><a href="{{ route('purchases.transaction.purchase-invoice.print', $invoice) }}" target="_blank" class="text-blue-600 hover:underline dark:text-blue-400">Cetak</a></td>
                    </tr>
                @empty
                    <tr><td colspan="12" class="px-4 py-10 text-center text-gray-400">Tidak ada faktur yang belum selesai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $invoices->links() }}</div>
</div>
