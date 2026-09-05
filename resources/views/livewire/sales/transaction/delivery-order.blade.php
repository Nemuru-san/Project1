<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3500)">
    <div x-cloak x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[80] rounded-lg px-4 py-2 text-sm text-white shadow-lg"><span
            x-text="toastMsg"></span></div>

    <div class="my-4 flex flex-col gap-3">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="relative w-full sm:w-72">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"><svg
                        class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg></div>
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Cari nomor, SO, atau pelanggan..."
                    class="block w-full rounded-lg border border-gray-600 p-2.5 pl-10 text-sm dark:bg-zinc-800 dark:text-white">
            </div>
            <input wire:model.live="dateFrom" type="date" aria-label="Tanggal mulai"
                class="w-full rounded-lg border border-gray-600 p-2.5 text-sm dark:bg-zinc-800 dark:text-white sm:w-auto">
            <span class="hidden text-gray-400 sm:inline">s.d.</span>
            <input wire:model.live="dateTo" type="date" aria-label="Tanggal selesai"
                class="w-full rounded-lg border border-gray-600 p-2.5 text-sm dark:bg-zinc-800 dark:text-white sm:w-auto">
            <select wire:model.live="perPage"
                class="w-full rounded-lg border border-gray-600 px-8 py-2.5 text-sm dark:bg-zinc-800 dark:text-white sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>
            @if ($search !== '' || $dateFrom !== '' || $dateTo !== '')
                <button wire:click="resetFilters"
                    class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2.5 text-sm dark:text-gray-300">Reset</button>
            @endif
            <button wire:click="openCreate"
                class="sm:ml-auto inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700"><span
                    class="text-lg leading-none">+</span> Tambah Surat Jalan</button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3">Nomor Surat Jalan</th>
                    <th class="px-4 py-3">Tanggal Kirim</th>
                    <th class="px-4 py-3">Pesanan Penjualan</th>
                    <th class="px-4 py-3">Sumber</th>
                    <th class="px-4 py-3">Pelanggan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($deliveryOrders as $deliveryOrder)
                    <tr wire:key="surat-jalan-{{ $deliveryOrder->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $deliveryOrder->delivery_no }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $deliveryOrder->delivery_date->format('d/m/Y') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $deliveryOrder->salesOrder?->order_no ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            {{ $deliveryOrder->salesOrder?->preOrder?->pre_order_no ?? ($deliveryOrder->salesOrder?->salesCanvas?->canvas_no ?? 'Manual') }}
                        </td>
                        <td class="px-4 py-3">{{ $deliveryOrder->customer?->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($deliveryOrder->status === \App\Models\DeliveryOrder::STATUS_DRAFT)
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-700">Draf</span>
                            @elseif($deliveryOrder->status === \App\Models\DeliveryOrder::STATUS_SHIPPED)
                                <span
                                    class="rounded-full bg-green-100 px-2.5 py-1 text-xs text-green-700">Dikirim</span>
                            @else
                                <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs text-red-700">Dibatalkan</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="inline-block" x-data="{ open: false, top: 0, left: 0, toggle(el) { const r = el.getBoundingClientRect();
                                    this.top = r.bottom + 6;
                                    this.left = Math.max(8, r.right - 224);
                                    this.open = !this.open } }">
                                <button type="button" @click="toggle($el)" @click.outside="open = false"
                                    aria-label="Buka aksi surat jalan"
                                    class="inline-flex cursor-pointer items-center rounded-lg p-0.5 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </button>

                                <div x-cloak x-show="open" :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                                    class="z-50 w-56 divide-y divide-gray-100 rounded bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                                    <ul class="whitespace-nowrap py-1 text-sm text-gray-700 dark:text-gray-200">
                                        <li>
                                            <button type="button" wire:click="openDetail({{ $deliveryOrder->id }})"
                                                @click="open = false"
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Rincian
                                            </button>
                                        </li>
                                        <li>
                                            <a href="{{ route('sales.transaction.deliveryOrder.view', $deliveryOrder->id) }}"
                                                target="_blank" @click="open = false"
                                                class="flex w-full items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z" />
                                                </svg>
                                                Lihat Surat Jalan
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('sales.transaction.deliveryOrder.print', $deliveryOrder->id) }}"
                                                target="_blank" @click="open = false"
                                                class="flex w-full items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M6.72 13.829h-.846a3 3 0 0 1-3-3V8.25a3 3 0 0 1 3-3h12.252a3 3 0 0 1 3 3v2.579a3 3 0 0 1-3 3h-.846M6.75 9h.008v.008H6.75V9Zm10.5 11.25H6.75v-8.5h10.5v8.5Z" />
                                                </svg>
                                                Cetak Surat Jalan
                                            </a>
                                        </li>
                                        @if ($deliveryOrder->status === \App\Models\DeliveryOrder::STATUS_DRAFT)
                                            <li>
                                                <button type="button"
                                                    wire:click="openConfirmShipment({{ $deliveryOrder->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-green-600 hover:bg-green-600 hover:text-white dark:text-green-400">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M3 7h11v10H3V7Zm11 3h4l3 3v4h-7v-7ZM7 20a2 2 0 100-4 2 2 0 000 4Zm10 0a2 2 0 100-4 2 2 0 000 4Z" />
                                                    </svg>
                                                    Konfirmasi Pengiriman
                                                </button>
                                            </li>
                                        @endif
                                    </ul>
                                    @if (auth()->user()?->isSuperAdmin() && $deliveryOrder->status !== \App\Models\DeliveryOrder::STATUS_CANCELLED)
                                        <div class="py-1">
                                            @if ($deliveryOrder->status === \App\Models\DeliveryOrder::STATUS_DRAFT)
                                                <button type="button"
                                                    wire:click="confirmDelete({{ $deliveryOrder->id }})"
                                                    @click="open = false" @disabled(!auth()->user()?->isSuperAdmin())
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Hapus Draf
                                                </button>
                                            @elseif($deliveryOrder->status === \App\Models\DeliveryOrder::STATUS_SHIPPED)
                                                <button type="button"
                                                    wire:click="openCancelShipment({{ $deliveryOrder->id }})"
                                                    @click="open = false" @disabled(!auth()->user()?->isSuperAdmin())
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 4v6h6M20 20v-6h-6M5.6 15A7 7 0 0018 17.4M18.4 9A7 7 0 006 6.6" />
                                                    </svg>
                                                    Batalkan Pengiriman
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400">Belum ada Surat Jalan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $deliveryOrders->links() }}</div>

    @if ($showModal)
        <div
            class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm">
            <div
                class="mx-auto flex h-[80vh] max-h-[calc(100dvh-2rem)] w-full max-w-full flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-800">
                <div
                    class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-zinc-50 px-8 py-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">Tambah Surat Jalan</h3>
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
                    <div class="min-h-0 flex-1 touch-pan-y overflow-y-auto px-8 py-6"
                        style="overscroll-behavior: contain; scrollbar-gutter: stable; -webkit-overflow-scrolling: touch;">
                        <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Nomor
                                    Surat Jalan</label><input wire:model="deliveryNo" readonly
                                    class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                            </div>
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Tanggal
                                    Pengiriman</label><input wire:model="deliveryDate" type="date"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('deliveryDate')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Pesanan
                                    Penjualan</label><select wire:model.live="salesOrderId"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                    <option value="">-- Pilih Pesanan Penjualan --</option>
                                    @foreach ($salesOrders as $salesOrder)
                                        <option value="{{ $salesOrder->id }}">{{ $salesOrder->order_no }} -
                                            {{ $salesOrder->customer?->name }}{{ $salesOrder->preOrder ? ' - ' . $salesOrder->preOrder->pre_order_no : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('salesOrderId')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div><label
                                    class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Customer</label><input
                                    value="{{ $salesOrders->firstWhere('id', $salesOrderId)?->customer?->name ?? '' }}"
                                    readonly placeholder="Terisi dari Pesanan Penjualan"
                                    class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                            </div>
                            <div class="sm:col-span-2"><label
                                    class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Alamat
                                    Pengiriman</label><select wire:model="customerAddressId"
                                    @disabled(!$customerId)
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 disabled:opacity-50 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                    <option value="">Tanpa alamat pengiriman</option>
                                    @foreach ($customerAddresses as $address)
                                        <option value="{{ $address->id }}">{{ $address->code }} -
                                            {{ $address->label }}</option>
                                    @endforeach
                                </select>
                                @error('customerAddressId')
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
                            @error('items')
                                <p class="mb-2 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <div class="overflow-x-auto">
                                <table
                                    class="w-full min-w-max border-collapse border border-gray-300 text-left text-sm dark:border-zinc-600 dark:text-white">
                                    <thead class="bg-gray-200 text-xs font-bold uppercase dark:bg-zinc-700">
                                        <tr>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">No.</th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Kode</th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Produk
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Kategori
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Gudang
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Satuan
                                            </th>
                                            <th
                                                class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                Qty Pesanan</th>
                                            <th
                                                class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                Sudah Dialokasikan</th>
                                            <th
                                                class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                Qty Sisa</th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Qty
                                                Dikirim</th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Catatan
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($items as $index => $item)
                                            <tr wire:key="item-sj-{{ $item['sales_order_item_id'] }}"
                                                class="hover:bg-gray-100 dark:hover:bg-zinc-800">
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $index + 1 }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['sku'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['product_name'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['category_name'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['warehouse_name'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['unit_name'] }}</td>
                                                <td
                                                    class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                    {{ number_format($item['qty_order'], 0, ',', '.') }}</td>
                                                <td
                                                    class="border border-gray-300 px-4 py-3 text-right dark:border-zinc-600">
                                                    {{ number_format($item['qty_already_allocated'], 0, ',', '.') }}</td>
                                                <td
                                                    class="border border-gray-300 px-4 py-3 text-right font-medium text-blue-600 dark:border-zinc-600">
                                                    {{ number_format($item['qty_outstanding'], 0, ',', '.') }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    <input wire:model="items.{{ $index }}.qty_delivered"
                                                        type="number" min="0"
                                                        max="{{ $item['qty_outstanding'] }}"
                                                        class="w-24 rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                    @error("items.$index.qty_delivered")
                                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    <input wire:model="items.{{ $index }}.note" type="text"
                                                        class="w-40 rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11"
                                                    class="border border-gray-300 px-4 py-8 text-center text-gray-400 dark:border-zinc-600">
                                                    Pilih Pesanan Penjualan yang masih memiliki qty sisa.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <button wire:click="$set('showModal', false)" type="button"
                            class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2 text-sm dark:text-gray-300">Batal</button><button
                            type="submit" wire:loading.attr="disabled"
                            class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"><span
                                wire:loading.remove wire:target="save">Simpan</span><span wire:loading
                                wire:target="save">Menyimpan...</span></button></div>
                </form>
            </div>
        </div>
    @endif
    @if ($showDetailModal && $selectedDeliveryOrder)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4">
            <div
                class="flex max-h-[calc(100dvh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b px-6 py-4 dark:border-zinc-700">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white">Rincian Surat Jalan</h3>
                        <p class="font-mono text-sm text-gray-400">{{ $selectedDeliveryOrder->delivery_no }}</p>
                    </div><button wire:click="$set('showDetailModal', false)"
                        class="text-2xl text-gray-400">&times;</button>
                </div>
                <div class="overflow-y-auto p-6">
                    <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <dt class="text-gray-400">Tanggal Pengiriman</dt>
                            <dd class="font-medium dark:text-white">
                                {{ $selectedDeliveryOrder->delivery_date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Pesanan Penjualan</dt>
                            <dd class="font-medium dark:text-white">
                                {{ $selectedDeliveryOrder->salesOrder?->order_no }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Pesanan Awal/Kanvas</dt>
                            <dd class="font-medium dark:text-white">
                                {{ $selectedDeliveryOrder->salesOrder?->preOrder?->pre_order_no ?? ($selectedDeliveryOrder->salesOrder?->salesCanvas?->canvas_no ?? 'Manual') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Pelanggan</dt>
                            <dd class="font-medium dark:text-white">{{ $selectedDeliveryOrder->customer?->name }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-gray-400">Alamat Pengiriman</dt>
                            <dd class="dark:text-white">{{ $selectedDeliveryOrder->customerAddress?->address ?? '-' }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-gray-400">Catatan</dt>
                            <dd class="dark:text-white">{{ $selectedDeliveryOrder->notes ?: '-' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-6 overflow-x-auto rounded-xl border dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase dark:bg-zinc-800">
                                <tr>
                                    <th class="p-3 text-left">Produk</th>
                                    <th class="p-3 text-left">Gudang</th>
                                    <th class="p-3 text-left">Satuan</th>
                                    <th class="p-3 text-right">Qty Pesanan</th>
                                    <th class="p-3 text-right">Qty Dikirim</th>
                                    <th class="p-3 text-right">Sisa Setelah Kirim</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedDeliveryOrder->items as $item)
                                    <tr class="border-t dark:border-zinc-700">
                                        <td class="p-3 dark:text-white">{{ $item->product?->name }}</td>
                                        <td class="p-3">{{ $item->warehouse?->name }}</td>
                                        <td class="p-3">{{ $item->unit?->name }}</td>
                                        <td class="p-3 text-right">{{ number_format($item->qty_order, 0, ',', '.') }}
                                        </td>
                                        <td class="p-3 text-right font-semibold">
                                            {{ number_format($item->qty_delivered, 0, ',', '.') }}</td>
                                        <td class="p-3 text-right">
                                            {{ number_format($item->qty_outstanding, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t px-6 py-4 dark:border-zinc-700"><a
                        href="{{ route('sales.transaction.deliveryOrder.view', $selectedDeliveryOrder->id) }}"
                        target="_blank"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:border-zinc-600 dark:text-gray-200 dark:hover:bg-zinc-700">Lihat
                        Surat Jalan</a><a
                        href="{{ route('sales.transaction.deliveryOrder.print', $selectedDeliveryOrder->id) }}"
                        target="_blank"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Cetak Surat
                        Jalan</a><button wire:click="$set('showDetailModal', false)"
                        class="rounded-lg bg-zinc-700 px-4 py-2 text-sm text-white">Tutup</button></div>
            </div>
        </div>
    @endif

    @if ($showShipModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 7h11v10H3V7Zm11 3h4l3 3v4h-7v-7ZM7 20a2 2 0 100-4 2 2 0 000 4Zm10 0a2 2 0 100-4 2 2 0 000 4Z" />
                    </svg></div>
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Konfirmasi Pengiriman?</h3>
                <p class="mb-3 text-sm text-gray-400">Barang akan dinyatakan dikirim, stok gudang dikurangi, dan status
                    Pesanan Penjualan diperbarui.</p>
                @error('shipment')
                    <p class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showShipModal', false)"
                        class="rounded-lg border px-4 py-2 dark:border-zinc-600 dark:text-gray-300">Batal</button><button
                        wire:click="confirmShipment" wire:loading.attr="disabled" wire:target="confirmShipment"
                        class="rounded-lg bg-green-600 px-4 py-2 text-white disabled:opacity-50"><span
                            wire:loading.remove wire:target="confirmShipment">Ya, Kirim Barang</span><span wire:loading
                            wire:target="confirmShipment">Memproses...</span></button>
                </div>
            </div>
        </div>
    @endif

    @if ($showCancelShipmentModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Batalkan Pengiriman?</h3>
                <p class="mb-3 text-sm text-gray-400">Stok barang akan dikembalikan ke gudang dan status Pesanan
                    Penjualan dihitung ulang. Riwayat Surat Jalan tetap disimpan sebagai Dibatalkan.</p>
                @error('shipment')
                    <p class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <div class="flex justify-end gap-3"><button wire:click="$set('showCancelShipmentModal', false)"
                        class="rounded-lg border px-4 py-2 dark:border-zinc-600 dark:text-gray-300">Batal</button><button
                        wire:click="cancelShipment" @disabled(!auth()->user()?->isSuperAdmin())
                        class="rounded-lg bg-red-600 px-4 py-2 text-white">Batalkan Pengiriman</button></div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Hapus Draf Surat Jalan?</h3>
                <p class="mb-6 text-sm text-gray-400">Draf akan dihapus dan alokasi qty dikembalikan ke sisa Pesanan
                    Penjualan. Stok tidak berubah.</p>
                <div class="flex justify-end gap-3"><button wire:click="$set('showDeleteModal', false)"
                        class="rounded-lg border px-4 py-2">Batal</button><button wire:click="delete"
                        @disabled(!auth()->user()?->isSuperAdmin()) class="rounded-lg bg-red-600 px-4 py-2 text-white">Hapus
                        Draf</button></div>
            </div>
        </div>
    @endif
</div>
