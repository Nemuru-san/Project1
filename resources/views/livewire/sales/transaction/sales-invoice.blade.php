<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3500)">
    <div x-cloak x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[80] rounded-lg px-4 py-2 text-sm text-white shadow-lg"><span
            x-text="toastMsg"></span></div>

    <div class="my-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
        <div class="relative w-full sm:w-72"><svg class="pointer-events-none absolute left-3 top-3 h-5 w-5 text-gray-400"
                fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                    clip-rule="evenodd" />
            </svg><input wire:model.live.debounce.300ms="search" type="search"
                placeholder="Cari faktur, SO, atau pelanggan..."
                class="w-full rounded-lg border p-2.5 pl-10 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
        </div>
        <input wire:model.live="dateFrom" type="date" aria-label="Tanggal mulai"
            class="rounded-lg border p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
        <span class="hidden text-gray-400 sm:inline">s.d.</span>
        <input wire:model.live="dateTo" type="date" aria-label="Tanggal selesai"
            class="rounded-lg border p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
        <select wire:model.live="perPage"
            class="rounded-lg border px-8 py-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
            <option value="10">10 / hal</option>
            <option value="25">25 / hal</option>
            <option value="50">50 / hal</option>
        </select>
        @if ($search || $dateFrom || $dateTo)
            <button wire:click="resetFilters"
                class="cursor-pointer rounded-lg border px-4 py-2.5 text-sm dark:border-zinc-600 dark:text-gray-300">Reset</button>
        @endif
        <button wire:click="openCreate"
            class="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 sm:ml-auto"><span
                class="text-lg leading-none">+</span> Tambah Faktur Penjualan</button>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3">Nomor Faktur</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Pesanan Penjualan</th>
                    <th class="px-4 py-3">Pelanggan</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-right">Sisa Tagihan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($invoices as $invoice)
                    <tr wire:key="faktur-{{ $invoice->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $invoice->invoice_no }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $invoice->salesOrder?->order_no }}</td>
                        <td class="px-4 py-3">{{ $invoice->customer?->name }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-medium">Rp
                            {{ number_format($invoice->amount_due, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if ($invoice->status === \App\Models\SalesInvoice::STATUS_CONFIRMED)
                                <span
                                class="rounded-full bg-green-100 px-2.5 py-1 text-xs text-green-700">Dikonfirmasi</span>@else<span
                                    class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-700">Draf</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-block" x-data="{ open: false, top: 0, left: 0, toggle(el) { const r = el.getBoundingClientRect();
                                    this.top = r.bottom + 6;
                                    this.left = Math.max(8, r.right - 224);
                                    this.open = !this.open } }"><button type="button"
                                    @click="toggle($el)" @click.outside="open=false"
                                    aria-label="Buka aksi faktur penjualan"
                                    class="cursor-pointer rounded-lg p-0.5 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><svg
                                        class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg></button>
                                <div x-cloak x-show="open" :style="`position:fixed;top:${top}px;left:${left}px`"
                                    class="z-50 w-56 divide-y divide-gray-100 rounded bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                                    <ul class="whitespace-nowrap py-1 text-sm text-gray-700 dark:text-gray-200">
                                        <li><button wire:click="openDetail({{ $invoice->id }})" @click="open=false"
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><svg
                                                    class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.5 12C3.7 8 7.5 5 12 5s8.3 3 9.5 7c-1.2 4-5 7-9.5 7s-8.3-3-9.5-7z" />
                                                </svg>Rincian</button></li>
                                        <li><a href="{{ route('sales.transaction.salesInvoice.view', $invoice->id) }}"
                                                target="_blank" @click="open=false"
                                                class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><svg
                                                    class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z" />
                                                </svg>Lihat Invoice</a></li>
                                        <li><a href="{{ route('sales.transaction.salesInvoice.print', $invoice->id) }}"
                                                target="_blank" @click="open=false"
                                                class="flex items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><svg
                                                    class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v7H6v-7z" />
                                                </svg>Cetak Invoice</a></li>
                                        <li><button wire:click="openEdit({{ $invoice->id }})" @click="open=false"
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><svg
                                                    class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11.8 15H9v-2.8l8.6-8.6z" />
                                                </svg>Ubah</button></li>
                                        @if (
                                            $invoice->status === \App\Models\SalesInvoice::STATUS_DRAFT &&
                                                auth()->user()?->hasPermission('sales.transaction.sales-invoice.confirm'))
                                            <li><button wire:click="openConfirmInvoice({{ $invoice->id }})"
                                                    @click="open=false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-green-600 hover:bg-green-600 hover:text-white"><svg
                                                        class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0Z" />
                                                    </svg>Konfirmasi</button></li>
                                        @endif
                                    </ul>
                                    @if (auth()->user()?->isSuperAdmin())
                                        <div class="py-1"><button wire:click="confirmDelete({{ $invoice->id }})"
                                                @click="open=false" @disabled(!auth()->user()?->isSuperAdmin())
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white disabled:opacity-40"><svg
                                                    class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 7 18 20H6L5 7m4 0V4h6v3M4 7h16" />
                                                </svg>Hapus</button></div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-400">Belum ada Faktur Penjualan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $invoices->links() }}</div>

    @if ($showModal)
        <div
            class="fixed inset-0 z-40 flex items-start justify-center overflow-hidden bg-black/50 p-4 backdrop-blur-sm">
            <div
                class="mx-auto flex h-[80vh] max-h-[calc(100dvh-2rem)] w-full max-w-full flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-800">
                <div
                    class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-zinc-50 px-8 py-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">{{ $editingId ? 'Ubah' : 'Tambah' }} Faktur
                        Penjualan</h3>
                    <button wire:click="$set('showModal', false)" type="button"
                        class="cursor-pointer text-gray-400 hover:text-white"><svg class="h-5 w-5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg></button>
                </div>

                <form wire:submit="save"
                    x-on:keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
                    class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto px-8 py-6">
                        <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Nomor
                                    Faktur</label><input value="{{ $invoiceNo }}" readonly
                                    class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                            </div>
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Tanggal
                                    Faktur</label><input wire:model="invoiceDate" type="date"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('invoiceDate')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Pesanan
                                    Penjualan</label><select wire:model.live="salesOrderId"
                                    @disabled($editingId)
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-gray-600 dark:bg-zinc-800 dark:text-white dark:disabled:bg-zinc-700">
                                    <option value="">-- Pilih Pesanan Penjualan --</option>
                                    @foreach ($salesOrders as $order)
                                        <option value="{{ $order->id }}">{{ $order->order_no }} -
                                            {{ $order->customer?->name }}</option>
                                    @endforeach
                                </select>
                                @error('salesOrderId')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Tanggal
                                    Jatuh Tempo</label><input wire:model="dueDate" type="date"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('dueDate')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2"><label
                                    class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Surat
                                    Jalan</label>
                                <div
                                    class="grid max-h-44 gap-2 overflow-y-auto rounded-lg border border-gray-300 bg-gray-50 p-3 dark:border-gray-600 dark:bg-zinc-800 sm:grid-cols-2">
                                    @forelse($deliveryOrders as $deliveryOrder)
                                        <label wire:key="si-do-{{ $deliveryOrder->id }}"
                                            class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900"><input
                                                wire:model.live="selectedDeliveryOrderIds" type="checkbox"
                                                value="{{ $deliveryOrder->id }}" @disabled($editingId)
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 disabled:cursor-not-allowed disabled:opacity-50"><span><span
                                                    class="block font-mono font-medium">{{ $deliveryOrder->delivery_no }}</span><span
                                                    class="text-xs text-gray-400">{{ $deliveryOrder->delivery_date?->format('d/m/Y') }}</span></span></label>
                                    @empty
                                        <p class="text-sm text-gray-400 sm:col-span-2">
                                            {{ $salesOrderId ? 'Tidak ada Surat Jalan berstatus Dikirim yang tersedia.' : 'Pilih Pesanan Penjualan terlebih dahulu.' }}
                                        </p>
                                    @endforelse
                                </div>
                                @error('selectedDeliveryOrderIds')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-6"><label
                                class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Catatan</label>
                            <textarea wire:model="notes" rows="3"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"
                                placeholder="Masukkan catatan atau keterangan tambahan..."></textarea>
                            @error('notes')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mt-12">
                            <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">Detail Produk</h3>
                            <div class="overflow-x-auto">
                                <table
                                    class="w-full min-w-max border-collapse border border-gray-300 text-left text-sm dark:border-zinc-600 dark:text-white">
                                    <thead class="bg-gray-200 text-xs font-bold uppercase dark:bg-zinc-700">
                                        <tr>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">No.</th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Kode</th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Produk
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Gudang
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Satuan
                                            </th>
                                            <th
                                                class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                Qty</th>
                                            <th
                                                class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                Harga</th>
                                            <th
                                                class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                Diskon</th>
                                            <th
                                                class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($items as $index => $item)
                                            <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800">
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $index + 1 }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['sku'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['product_name'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['warehouse_name'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['unit_name'] }}</td>
                                                <td
                                                    class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                    {{ number_format($item['qty'], 0, ',', '.') }}</td>
                                                <td
                                                    class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                    Rp {{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                                                <td
                                                    class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                    Rp {{ number_format($item['discount_amount'], 0, ',', '.') }}</td>
                                                <td
                                                    class="border border-gray-300 px-4 py-3 text-right font-medium dark:border-zinc-600">
                                                    Rp {{ number_format($item['line_total'], 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9"
                                                    class="border border-gray-300 px-4 py-8 text-center text-gray-400 dark:border-zinc-600">
                                                    Pilih Pesanan Penjualan untuk menampilkan detail produk.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <h4 class="mb-4 font-bold dark:text-white">Detail Harga</h4>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                @foreach (['Bruto' => 'subtotal', 'Total Diskon' => 'discount_total', 'PPN (11%)' => 'tax_amount', 'Neto' => 'grand_total'] as $label => $key)
                                    <div><label
                                            class="mb-2 block text-sm font-medium dark:text-white">{{ $label }}</label><input
                                            value="Rp {{ number_format($totals[$key] ?? 0, 0, ',', '.') }}" disabled
                                            class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><label class="mb-2 block text-sm font-medium dark:text-white">DP</label><input
                                        value="Rp {{ number_format($totals['dp_amount'] ?? 0, 0, ',', '.') }}"
                                        disabled
                                        class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                </div>
                                <div><label class="mb-2 block text-sm font-medium dark:text-white">Sisa
                                        Tagihan</label><input
                                        value="Rp {{ number_format($totals['amount_due'] ?? 0, 0, ',', '.') }}"
                                        disabled
                                        class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm font-semibold text-blue-600 dark:border-gray-600 dark:bg-zinc-700 dark:text-blue-400">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <button wire:click="$set('showModal', false)" type="button"
                            class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2 text-sm dark:text-gray-300">Batal</button><button
                            type="submit" wire:loading.attr="disabled"
                            class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"><span
                                wire:loading.remove
                                wire:target="save">{{ $editingId ? 'Simpan Perubahan' : 'Simpan' }}</span><span
                                wire:loading wire:target="save">Menyimpan...</span></button></div>
                </form>
            </div>
        </div>
    @endif
    @if ($showDetailModal && $selectedInvoice)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4">
            <div
                class="flex max-h-[calc(100dvh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b px-6 py-4 dark:border-zinc-700">
                    <div>
                        <h3 class="text-lg font-semibold">Rincian Faktur Penjualan</h3>
                        <p class="font-mono text-sm text-gray-400">{{ $selectedInvoice->invoice_no }}</p>
                    </div><button wire:click="$set('showDetailModal',false)"
                        class="text-2xl text-gray-400">&times;</button>
                </div>
                <div class="overflow-y-auto p-6">
                    <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-gray-400">Tanggal</dt>
                            <dd class="font-medium">{{ $selectedInvoice->invoice_date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Jatuh Tempo</dt>
                            <dd class="font-medium">{{ $selectedInvoice->due_date?->format('d/m/Y') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Pesanan Penjualan</dt>
                            <dd class="font-medium">{{ $selectedInvoice->salesOrder?->order_no }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Pelanggan</dt>
                            <dd class="font-medium">{{ $selectedInvoice->customer?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Status</dt>
                            <dd class="font-medium">
                                {{ $selectedInvoice->status === 'Confirmed' ? 'Dikonfirmasi' : 'Draf' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Total</dt>
                            <dd class="font-medium">Rp {{ number_format($selectedInvoice->grand_total, 0, ',', '.') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">DP</dt>
                            <dd class="font-medium">Rp {{ number_format($selectedInvoice->dp_amount, 0, ',', '.') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Sisa Tagihan</dt>
                            <dd class="font-bold text-blue-600">Rp
                                {{ number_format($selectedInvoice->amount_due, 0, ',', '.') }}</dd>
                        </div>
                    </dl>
                    <div class="mt-6 overflow-x-auto rounded-xl border dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase dark:bg-zinc-800">
                                <tr>
                                    <th class="p-3 text-left">Produk</th>
                                    <th class="p-3 text-left">Gudang</th>
                                    <th class="p-3 text-left">Satuan</th>
                                    <th class="p-3 text-right">Qty</th>
                                    <th class="p-3 text-right">Harga</th>
                                    <th class="p-3 text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedInvoice->items as $item)
                                    <tr class="border-t dark:border-zinc-700">
                                        <td class="p-3">{{ $item->product?->name }}</td>
                                        <td class="p-3">{{ $item->warehouse?->name }}</td>
                                        <td class="p-3">{{ $item->unit?->name }}</td>
                                        <td class="p-3 text-right">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                        <td class="p-3 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}
                                        </td>
                                        <td class="p-3 text-right">Rp {{ number_format($item->line_total, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t p-4 dark:border-zinc-700"><a
                        href="{{ route('sales.transaction.salesInvoice.view', $selectedInvoice->id) }}"
                        target="_blank"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-100 dark:border-zinc-600 dark:text-gray-200 dark:hover:bg-zinc-700">Lihat
                        Invoice</a><a href="{{ route('sales.transaction.salesInvoice.print', $selectedInvoice->id) }}"
                        target="_blank" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Cetak
                        Invoice</a><button wire:click="$set('showDetailModal',false)"
                        class="rounded-lg bg-zinc-700 px-4 py-2 text-white">Tutup</button></div>
            </div>
        </div>
    @endif

    @if ($showConfirmModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0Z" />
                    </svg></div>
                <h3 class="mb-2 text-lg font-semibold">Konfirmasi Faktur Penjualan?</h3>
                <p class="mb-6 text-sm text-gray-400">Konfirmasi akan membuat jurnal piutang dan mengizinkan Pembayaran
                    Piutang untuk faktur ini. Faktur tetap dapat diubah atau dihapus setelah dikonfirmasi.</p>
                <div class="flex justify-end gap-3"><button wire:click="$set('showConfirmModal',false)"
                        class="rounded-lg border px-4 py-2">Batal</button><button wire:click="confirmInvoice"
                        class="rounded-lg bg-green-600 px-4 py-2 text-white">Konfirmasi</button></div>
            </div>
        </div>
    @endif
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <h3 class="mb-2 text-lg font-semibold">Hapus Faktur Penjualan?</h3>
                <p class="mb-6 text-sm text-gray-400">Faktur dan jurnal terkait akan dihapus. Faktur yang sudah
                    memiliki pembayaran atau retur tidak dapat dihapus.</p>
                <div class="flex justify-end gap-3"><button wire:click="$set('showDeleteModal',false)"
                        class="rounded-lg border px-4 py-2">Batal</button><button wire:click="delete"
                        @disabled(!auth()->user()?->isSuperAdmin()) class="rounded-lg bg-red-600 px-4 py-2 text-white">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
