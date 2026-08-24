<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3500)">
    <div x-cloak x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[80] rounded-lg px-4 py-2 text-sm text-white shadow-lg"><span
            x-text="toastMsg"></span></div>

    <div class="my-4 flex flex-col gap-3 dark:bg-zinc-900">
        <div class="flex w-full flex-col items-stretch gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="relative w-full sm:w-72">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"><svg
                        class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg></div>
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari nomor atau customer..."
                    class="block w-full rounded-lg border border-gray-600 p-2.5 pl-10 text-sm placeholder-gray-400 dark:bg-zinc-800 dark:text-white">
            </div>
            <select wire:model.live="statusFilter"
                class="w-full rounded-lg border border-gray-600 px-8 py-2.5 text-sm dark:bg-zinc-800 dark:text-white sm:w-auto">
                <option value="">Semua Status</option>
                <option value="draft">Draf</option>
                <option value="confirmed">Dikonfirmasi</option>
                <option value="sales_order">Menjadi Sales Order</option>
            </select>
            <select wire:model.live="perPage"
                class="w-full rounded-lg border border-gray-600 px-8 py-2.5 text-sm dark:bg-zinc-800 dark:text-white sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>
            <label
                class="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg border border-gray-600 px-3 py-2.5 text-sm dark:text-gray-300"><input
                    wire:model.live="showTrashed" type="checkbox"
                    class="h-4 w-4 rounded border-gray-600 text-blue-600 dark:bg-zinc-800"> Tampilkan Terhapus</label>
            <button wire:click="openCreate" type="button"
                class="order-last inline-flex w-full cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 sm:ml-auto sm:w-auto"><svg
                    class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>Tambah Transaksi</button>
        </div>
        <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300 sm:min-w-28">Rentang tanggal</span>
            <input wire:model.live="dateFrom" type="date"
                class="w-full rounded-lg border border-gray-600 px-3 py-2.5 text-sm dark:bg-zinc-800 dark:text-white sm:w-auto"><span
                class="hidden text-gray-400 sm:inline">s.d.</span>
            <input wire:model.live="dateTo" type="date"
                class="w-full rounded-lg border border-gray-600 px-3 py-2.5 text-sm dark:bg-zinc-800 dark:text-white sm:w-auto">
            <button wire:click="resetFilters" type="button"
                class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2.5 text-sm hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-zinc-800">Bersihkan
                Filter</button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3">Nomor Pre Order</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Neto</th>
                    <th class="px-4 py-3">Target DP</th>
                    <th class="px-4 py-3">DP Dibayar</th>
                    <th class="px-4 py-3">Status DP</th>
                    <th class="px-4 py-3">Status Dokumen</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($preOrders as $preOrder)
                    @php $postedDp=(int)($preOrder->posted_dp_amount ?? 0); @endphp
                    <tr wire:key="pre-order-{{ $preOrder->id }}"
                        class="hover:bg-gray-50 dark:hover:bg-zinc-800 {{ $preOrder->trashed() ? 'opacity-60' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $preOrder->pre_order_no }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $preOrder->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $preOrder->customer?->name ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">Rp {{ number_format($preOrder->grand_total, 0, ',', '.') }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-medium">Rp
                            {{ number_format($preOrder->dp_amount, 0, ',', '.') }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-green-600">Rp
                            {{ number_format($postedDp, 0, ',', '.') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($preOrder->dp_payment_status === 'paid')
                                <span
                                    class="rounded-full bg-green-100 px-2.5 py-1 text-xs text-green-700">Dibayar</span>
                            @elseif($preOrder->dp_payment_status === 'partial_paid')
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs text-blue-700">Dibayar
                                    Sebagian</span>
                                <div class="mt-1 text-xs text-gray-400">Sisa Rp
                                {{ number_format(max(0, $preOrder->dp_amount - $postedDp), 0, ',', '.') }}</div>@else<span
                                    class="rounded-full bg-yellow-100 px-2.5 py-1 text-xs text-yellow-700">Belum
                                    Dibayar</span>
                                <div class="mt-1 text-xs text-gray-400">Sisa Rp
                                    {{ number_format($preOrder->dp_amount, 0, ',', '.') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($preOrder->trashed())
                                <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs text-red-700">Terhapus</span>
                            @elseif($preOrder->status === 'confirmed')
                                <span
                                    class="rounded-full bg-green-100 px-2.5 py-1 text-xs text-green-700">Dikonfirmasi</span>
                            @elseif($preOrder->status === 'sales_order')
                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs text-blue-700">Sales
                                Order</span>@else<span
                                    class="rounded-full bg-yellow-100 px-2.5 py-1 text-xs text-yellow-700">Draf</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="inline-block" x-data="{ open: false, top: 0, left: 0, toggle(el) { const r = el.getBoundingClientRect();
                                    this.top = r.bottom + 6;
                                    this.left = Math.max(8, r.right - 224);
                                    this.open = !this.open } }">
                                <button @click="toggle($el)" @click.outside="open=false"
                                    class="cursor-pointer p-0.5 text-gray-500"><svg class="h-5 w-5" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg></button>
                                <div x-cloak x-show="open" :style="`position:fixed;top:${top}px;left:${left}px`"
                                    class="z-50 w-56 divide-y divide-gray-100 rounded bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                                    @if ($preOrder->trashed())
                                        <button wire:click="restore({{ $preOrder->id }})" @disabled(!auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'delete'))
                                            class="w-full px-4 py-2 text-left text-sm text-green-600 disabled:opacity-40">Pulihkan</button>
                                    @else
                                        <ul class="whitespace-nowrap py-1 text-sm dark:text-gray-200">
                                            <li><button wire:click="openDetail({{ $preOrder->id }})"
                                                    @click="open=false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><svg
                                                        class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>Rincian</button></li>
                                            <li><button wire:click="openEdit({{ $preOrder->id }})"
                                                    @click="open=false" @disabled($preOrder->status !== 'draft' || $postedDp > 0)
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-gray-600"><svg
                                                        class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>Ubah</button></li>
                                            @if (auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'confirm'))
                                                <li><button wire:click="openConfirmPreOrder({{ $preOrder->id }})"
                                                        @click="open=false" @disabled($preOrder->status !== 'draft')
                                                        class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-green-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-gray-600"><svg
                                                            class="h-5 w-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>Konfirmasi</button></li>
                                            @endif
                                            <li><button wire:click="confirmConvert({{ $preOrder->id }})"
                                                    @click="open=false" @disabled(
                                                        $preOrder->status !== 'confirmed' ||
                                                            $preOrder->dp_payment_status !== 'paid' ||
                                                            !auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'convert'))
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-blue-600 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-gray-600"><svg
                                                        class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 7h11m0 0-4-4m4 4-4 4M17 17H6m0 0 4 4m-4-4 4-4" />
                                                    </svg>Jadikan Sales Order</button></li>
                                        </ul>
                                        <div class="py-1"><button wire:click="confirmDelete({{ $preOrder->id }})"
                                                @click="open=false" @disabled(
                                                    !auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'delete') ||
                                                        $preOrder->status !== 'draft' ||
                                                        $postedDp > 0)
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"><svg
                                                    class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>Hapus</button></div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="9" class="px-4 py-10 text-center text-gray-400">Belum ada Pesanan Awal.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $preOrders->links() }}</div>

    @if ($showModal)
        <div
            class="fixed inset-0 z-40 flex items-start justify-center overflow-hidden bg-black/50 p-4 backdrop-blur-sm">
            <div
                class="mx-auto flex h-[80vh] max-h-[calc(100dvh-2rem)] w-full max-w-full flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-800">
                <div
                    class="flex shrink-0 items-center justify-between border-b bg-zinc-50 px-8 py-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">{{ $editingId ? 'Ubah' : 'Tambah' }} Pesanan Awal
                    </h3><button wire:click="$set('showModal',false)" type="button"
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
                            <div><label class="mb-3 block text-base font-medium dark:text-white">Nomor Pre
                                    Order</label><input wire:model="preOrderNo" readonly
                                    class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                            </div>
                            <div><label class="mb-3 block text-base font-medium dark:text-white">Tanggal</label><input
                                    wire:model="date" type="date"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('date')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div><label
                                    class="mb-3 block text-base font-medium dark:text-white">Customer</label><select
                                    wire:model.live="customerId"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                    <option value="">-- Pilih Customer --</option>
                                    @foreach ($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->code }} -
                                            {{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                @error('customerId')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div><label class="mb-3 block text-base font-medium dark:text-white">Pajak (PPN
                                    11%)</label><label class="inline-flex cursor-pointer items-center"><span
                                        class="text-base text-gray-600">Tidak</span><span class="relative mx-3"><input
                                            wire:model.live="tax" type="checkbox" class="peer sr-only"><span
                                            class="block h-5 w-9 rounded-full bg-red-200 after:absolute after:start-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-red-500 after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full"></span></span><span
                                        class="text-base text-gray-600">Ya</span></label></div>
                            <div class="sm:col-span-2"><label
                                    class="mb-3 block text-base font-medium dark:text-white">Alamat
                                    Pengiriman</label><select wire:model="customerAddressId"
                                    @disabled(!$customerId)
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm disabled:opacity-50 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                    <option value="">Tanpa alamat pengiriman</option>
                                    @foreach ($customerAddresses as $address)
                                        <option value="{{ $address->id }}">{{ $address->code }} -
                                            {{ $address->label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-6"><label
                                class="mb-3 block text-base font-medium dark:text-white">Catatan</label>
                            <textarea wire:model="notes" rows="3"
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></textarea>
                        </div>

                        <div class="mt-12">
                            <h3 class="mb-4 text-lg font-bold dark:text-white">Detail Produk</h3>
                            @error('items')
                                <p class="mb-2 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <div class="overflow-x-auto">
                                <table
                                    class="w-full min-w-max border-collapse border border-gray-300 text-left text-sm dark:border-zinc-600 dark:text-white">
                                    <thead class="bg-gray-200 text-xs font-bold uppercase dark:bg-zinc-700">
                                        <tr>
                                            <th
                                                class="w-14 border border-gray-300 px-4 py-3 text-center dark:border-zinc-600">
                                                <button wire:click="openProductPicker" type="button"
                                                    class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-sm bg-blue-600 text-xl text-white">+</button>
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">No.</th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Kode</th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Produk
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Gudang
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Satuan
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">AFS (Stok
                                                Tersedia)</th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Qty</th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Harga
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Diskon
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 dark:border-zinc-600">Subtotal
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($items as $index=>$item)
                                            <tr wire:key="pre-order-item-{{ $item['product_id'] }}"
                                                class="hover:bg-gray-100 dark:hover:bg-zinc-800">
                                                <td
                                                    class="border border-gray-300 px-4 py-3 text-center dark:border-zinc-600">
                                                    <button wire:click="removeItem({{ $index }})"
                                                        type="button" class="cursor-pointer text-red-500"><svg
                                                            class="h-4 w-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg></button></td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $index + 1 }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['sku'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['name'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    <select wire:model.live="items.{{ $index }}.warehouse_id"
                                                        class="w-48 rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                        @foreach ($warehouses as $warehouse)
                                                            <option value="{{ $warehouse->id }}">
                                                                {{ $warehouse->desc }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    <select wire:model.live="items.{{ $index }}.unit_id"
                                                        class="w-36 rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                        @foreach ($item['unit_options'] as $unit)
                                                            <option value="{{ $unit['unit_id'] }}">
                                                                {{ $unit['unit_name'] }} (x{{ $unit['conversion'] }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600"><span
                                                        class="rounded bg-blue-100 px-2.5 py-1 text-blue-700">{{ $item['stock_available_display'] ?? number_format($item['stock_available'], 0, ',', '.') . ' ' . $item['base_unit_name'] }}</span>
                                                </td>
                                                @foreach (['qty' => 'w-24', 'unit_price' => 'w-28', 'discount_amount' => 'w-28'] as $field => $width)
                                                    <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                        <input type="text" inputmode="numeric"
                                                            x-data="{ display: '{{ number_format($item[$field] ?? 0, 0, ',', '.') }}' }" x-model="display"
                                                            @input="let raw=display.replace(/\./g,'').replace(/\D/g,'');display=raw===''?'':Number(raw).toLocaleString('id-ID');$wire.set('items.{{ $index }}.{{ $field }}',raw===''?0:Number(raw))"
                                                            class="{{ $width }} rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                        @error("items.$index.$field")
                                                            <p class="text-xs text-red-500">{{ $message }}</p>
                                                        @enderror
                                                    </td>
                                                @endforeach
                                                <td
                                                    class="border border-gray-300 px-4 py-3 font-medium dark:border-zinc-600">
                                                    Rp
                                                    {{ number_format(max(0, $item['qty'] * $item['unit_price'] - $item['discount_amount']), 0, ',', '.') }}
                                                </td>
                                        </tr>@empty<tr>
                                                <td colspan="11"
                                                    class="border border-gray-300 px-4 py-8 text-center text-gray-400 dark:border-zinc-600">
                                                    Belum ada produk. Klik tombol <strong>+</strong>.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mt-6 rounded-lg border border-gray-200 p-4 dark:border-zinc-700">
                            <h4 class="mb-4 font-bold dark:text-white">Detail Harga</h4>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                @foreach (['Bruto' => 'subtotal', 'Total Diskon' => 'discount', 'PPN (11%)' => 'tax', 'Neto' => 'grand_total'] as $label => $key)
                                    <div><label
                                            class="mb-2 block text-sm font-medium dark:text-white">{{ $label }}</label><input
                                            value="Rp {{ number_format($totals[$key], 0, ',', '.') }}" disabled
                                            class="block w-full rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                    </div>
                                @endforeach
                                <div>
                                    <label class="mb-2 block text-sm font-medium dark:text-white">Uang DP</label>
                                    <div class="relative"><span
                                            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">Rp</span><input
                                            type="text" inputmode="numeric" x-data="{ display: '{{ number_format($dpAmount, 0, ',', '.') }}' }"
                                            x-model="display"
                                            @input="let raw=display.replace(/\./g,'').replace(/\D/g,'');display=raw===''?'':Number(raw).toLocaleString('id-ID');$wire.set('dpAmount',raw===''?0:Number(raw))"
                                            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-9 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-400">Maksimal sebesar Neto.</p>
                                    @error('dpAmount')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <button wire:click="$set('showModal',false)" type="button"
                            class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2 text-sm dark:text-gray-300">Batal</button><button
                            type="submit"
                            class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showProductModal)
        <div class="fixed inset-0 z-[60] flex items-start justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="flex h-[80vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b px-6 py-4 dark:border-zinc-700">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white">Tambah Detail Produk</h3>
                        <p class="text-sm text-gray-500">Cari produk dan pilih dari daftar.</p>
                    </div><button wire:click="$set('showProductModal',false)"
                        class="cursor-pointer text-gray-400"><svg class="h-5 w-5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg></button>
                </div>
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-6">
                    <div class="grid gap-4 sm:grid-cols-[1fr_auto]"><input
                            wire:model.live.debounce.300ms="productSearch" placeholder="Cari produk (nama / kode)..."
                            class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white"><select
                            wire:model.live="categoryFilter"
                            class="rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->desc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-300 dark:border-zinc-600">
                        <table class="w-full text-left text-sm dark:text-white">
                            <thead class="bg-gray-100 text-xs uppercase dark:bg-zinc-800">
                                <tr>
                                    <th class="px-4 py-3 text-center">Pilih</th>
                                    <th class="px-4 py-3">Kode</th>
                                    <th class="px-4 py-3">Nama Produk</th>
                                    <th class="px-4 py-3">Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    @php $added=collect($items)->contains('product_id',$product->id); @endphp
                                    <tr @click="$event.currentTarget.querySelector('input:not(:disabled)')?.click()"
                                        class="border-t dark:border-zinc-700 {{ $added ? 'opacity-50' : 'cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800' }}">
                                        <td class="px-4 py-3 text-center"><input wire:model="selectedProductIds"
                                                type="checkbox" value="{{ $product->id }}" @click.stop
                                                @disabled($added)
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600"></td>
                                        <td class="px-4 py-3">{{ $product->name }}</td>
                                        <td class="px-4 py-3">{{ $product->sku }}</td>
                                        <td class="px-4 py-3">{{ $product->category?->desc ?? '-' }}</td>
                                </tr>@empty<tr>
                                        <td colspan="4" class="p-8 text-center text-gray-400">Produk tidak
                                            ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="flex justify-end gap-2 border-t pt-4 dark:border-zinc-700"><button
                            wire:click="$set('showProductModal',false)"
                            class="cursor-pointer rounded-lg border px-4 py-2 text-sm dark:text-white">Batal</button><button
                            wire:click="addSelectedProducts"
                            class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Tambah
                            Produk Terpilih</button></div>
                </div>
            </div>
        </div>
    @endif

    @if ($showConfirmModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="rounded-full bg-green-100 p-2 dark:bg-green-900/40"><svg
                            class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg></div>
                    <h3 class="text-lg font-semibold dark:text-white">Konfirmasi Pesanan Awal?</h3>
                </div>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Setelah dikonfirmasi, Pesanan Awal tidak dapat
                    diubah dan dapat dipilih pada Penerimaan DP Pelanggan.</p>
                <div class="flex justify-end gap-3"><button wire:click="$set('showConfirmModal',false)"
                        type="button"
                        class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-zinc-700">Batal</button><button
                        wire:click="confirmPreOrder({{ $confirmTargetId }})" wire:loading.attr="disabled"
                        wire:target="confirmPreOrder" type="button"
                        class="cursor-pointer rounded-lg bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 disabled:opacity-50"><span
                            wire:loading.remove wire:target="confirmPreOrder">Ya, Konfirmasi</span><span wire:loading
                            wire:target="confirmPreOrder">Memproses...</span></button></div>
            </div>
        </div>
    @endif
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Hapus Pesanan Awal?</h3>
                <p class="mb-6 text-sm text-gray-400">Data akan dipindahkan ke tempat sampah.</p>
                <div class="flex justify-end gap-3"><button wire:click="$set('showDeleteModal',false)"
                        class="rounded-lg border px-4 py-2">Batal</button><button wire:click="delete"
                        @disabled(!auth()->user()?->canPerform('sales.transaction.salesPreOrder', 'delete'))
                        class="rounded-lg bg-red-600 px-4 py-2 text-white disabled:opacity-40">Hapus</button></div>
            </div>
        </div>
    @endif
    @if ($showConvertModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Jadikan Sales Order?</h3>
                <p class="mb-6 text-sm text-gray-400">Target DP sudah dibayar lunas. Seluruh DP yang telah diposting
                    akan mengurangi sisa tagihan Sales Order.</p>
                <div class="flex justify-end gap-3"><button wire:click="$set('showConvertModal',false)"
                        class="rounded-lg border px-4 py-2">Batal</button><button wire:click="convertToSalesOrder"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-white">Jadikan Sales Order</button></div>
            </div>
        </div>
    @endif

    @if ($showDetailModal && $selectedPreOrder)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4">
            <div
                class="flex max-h-[80vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b px-6 py-4 dark:border-zinc-700">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white">Rincian Pesanan Awal</h3>
                        <p class="font-mono text-sm text-gray-400">{{ $selectedPreOrder->pre_order_no }}</p>
                    </div><button wire:click="$set('showDetailModal',false)">&times;</button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <dl class="grid gap-4 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="text-gray-400">Customer</dt>
                            <dd class="font-medium dark:text-white">{{ $selectedPreOrder->customer?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Tanggal</dt>
                            <dd>{{ $selectedPreOrder->date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Sales Order</dt>
                            <dd>{{ $selectedPreOrder->salesOrder?->order_no ?? '-' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-5 overflow-x-auto rounded-lg border">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-zinc-800">
                                <tr>
                                    <th class="p-3 text-left">Produk</th>
                                    <th class="p-3 text-left">Gudang</th>
                                    <th class="p-3 text-left">Satuan</th>
                                    <th class="p-3">Qty</th>
                                    <th class="p-3">Harga</th>
                                    <th class="p-3">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedPreOrder->items as $item)
                                    <tr class="border-t">
                                        <td class="p-3">{{ $item->product?->sku }}</td>
                                        <td class="p-3">{{ $item->warehouse?->desc }}</td>
                                        <td class="p-3">{{ $item->unit?->name }}</td>
                                        <td class="p-3">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                        <td class="p-3">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                        <td class="p-3">Rp {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @php $detailDp=(int)$selectedPreOrder->dpAllocations->sum('amount'); @endphp<div class="ml-auto mt-5 max-w-sm space-y-2 text-sm">
                        <div class="flex justify-between"><span>Neto</span><span>Rp
                                {{ number_format($selectedPreOrder->grand_total, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between font-medium"><span>Target DP</span><span>Rp
                                {{ number_format($selectedPreOrder->dp_amount, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between text-green-600"><span>DP Dibayar</span><span>Rp
                                {{ number_format($detailDp, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between border-t pt-2 text-base font-bold"><span>Sisa Target
                                DP</span><span>Rp
                                {{ number_format(max(0, $selectedPreOrder->dp_amount - $detailDp), 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between"><span>Status
                                DP</span><span>{{ $selectedPreOrder->dp_payment_status === 'paid' ? 'Dibayar' : ($selectedPreOrder->dp_payment_status === 'partial_paid' ? 'Dibayar Sebagian' : 'Belum Dibayar') }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end border-t p-4"><button wire:click="$set('showDetailModal',false)"
                        class="rounded-lg bg-zinc-700 px-5 py-2 text-white">Tutup</button></div>
            </div>
        </div>
    @endif
</div>
