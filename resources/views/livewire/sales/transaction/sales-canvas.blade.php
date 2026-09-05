<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3500)">
    <div x-cloak x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[80] rounded-lg px-4 py-2 text-sm text-white shadow-lg">
        <span x-text="toastMsg"></span>
    </div>

    <div class="my-4 flex flex-col gap-3 dark:bg-zinc-900">
        <div class="flex w-full flex-col items-stretch gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="relative w-full sm:w-72">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg aria-hidden="true" class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="search"
                    placeholder="Cari nomor, customer, salesman..."
                    class="block w-full rounded-lg border border-gray-600 p-2.5 pl-10 text-sm placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 dark:bg-zinc-800 dark:text-white">
            </div>

            <select wire:model.live="statusFilter"
                class="w-full rounded-lg border border-gray-600 px-8 py-2.5 text-sm focus:ring-primary-500 dark:bg-zinc-800 dark:text-white sm:w-auto">
                <option value="">Semua Status</option>
                <option value="draft">Draf</option>
                <option value="confirmed">Dikonfirmasi</option>
                <option value="sales_order">Sales Order</option>
            </select>

            <select wire:model.live="perPage"
                class="w-full rounded-lg border border-gray-600 px-8 py-2.5 text-sm dark:bg-zinc-800 dark:text-white sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>

            <label
                class="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg border border-gray-600 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300">
                <input wire:model.live="showTrashed" type="checkbox"
                    class="h-4 w-4 rounded border-gray-600 text-blue-600 dark:bg-zinc-800">
                Tampilkan Terhapus
            </label>

            <button wire:click="openCreate" type="button"
                class="order-last inline-flex w-full cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 sm:ml-auto sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Penjualan
            </button>
        </div>

        <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300 sm:min-w-28">Rentang tanggal</span>
            <input wire:model.live="dateFrom" type="date" title="Tanggal mulai" aria-label="Tanggal mulai"
                class="w-full rounded-lg border border-gray-600 px-3 py-2.5 text-sm dark:bg-zinc-800 dark:text-white sm:w-auto">
            <span class="hidden text-gray-400 sm:inline">s.d.</span>
            <input wire:model.live="dateTo" type="date" title="Tanggal akhir" aria-label="Tanggal akhir"
                class="w-full rounded-lg border border-gray-600 px-3 py-2.5 text-sm dark:bg-zinc-800 dark:text-white sm:w-auto">
            <button wire:click="resetFilters" type="button"
                class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-zinc-800">
                Bersihkan Filter
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th wire:click="sortBy('canvas_no')" class="cursor-pointer px-4 py-3">Nomor</th>
                    <th wire:click="sortBy('date')" class="cursor-pointer px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Salesman</th>
                    <th class="px-4 py-3">Customer</th>
                    <th wire:click="sortBy('grand_total')" class="cursor-pointer px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($salesCanvases as $canvas)
                    <tr wire:key="canvas-{{ $canvas->id }}"
                        class="hover:bg-gray-50 dark:hover:bg-zinc-800 {{ $canvas->trashed() ? 'opacity-60' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $canvas->canvas_no }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $canvas->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $canvas->salesman?->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $canvas->customer?->name ?? '-' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium">Rp
                            {{ number_format($canvas->grand_total, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if ($canvas->trashed())
                                <span
                                    class="rounded-full bg-red-100 px-2.5 py-1 text-xs text-red-700 dark:bg-red-900/40 dark:text-red-300">Terhapus</span>
                            @elseif ($canvas->status === 'confirmed')
                                <span
                                    class="rounded-full bg-green-100 px-2.5 py-1 text-xs text-green-700 dark:bg-green-900/40 dark:text-green-300">Dikonfirmasi</span>
                            @elseif ($canvas->status === 'sales_order')
                                <span
                                    class="rounded-full bg-blue-100 px-2.5 py-1 text-xs text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">Sales
                                    Order</span>
                            @elseif ($canvas->status !== 'draft')
                                <span
                                    class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-300">Diproses</span>
                            @else
                                <span
                                    class="rounded-full bg-yellow-100 px-2.5 py-1 text-xs text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300">Draf</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="inline-block" x-data="{ open: false, top: 0, left: 0, toggle(el) { const r = el.getBoundingClientRect();
                                    this.top = r.bottom + 6;
                                    this.left = Math.max(8, r.right - 224);
                                    this.open = !this.open } }">
                                <button type="button" @click="toggle($el)" @click.outside="open = false"
                                    aria-label="Buka aksi penjualan kanvas"
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
                                            <button type="button" wire:click="openDetail({{ $canvas->id }})"
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
                                        @if (!$canvas->trashed() && $canvas->status === 'draft')
                                            <li>
                                                <button type="button" wire:click="openEdit({{ $canvas->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Ubah
                                                </button>
                                            </li>
                                            @if (auth()->user()?->canPerform('sales.transaction.salesCanvas', 'confirm'))
                                                <li><button type="button"
                                                        wire:click="openConfirmCanvas({{ $canvas->id }})"
                                                        @click="open = false"
                                                        class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-green-600 hover:bg-green-600 hover:text-white dark:text-green-400"><svg
                                                            class="h-5 w-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>Konfirmasi</button></li>
                                            @endif
                                        @endif
                                        @if (
                                            !$canvas->trashed() &&
                                                $canvas->status === 'confirmed' &&
                                                auth()->user()?->canPerform('sales.transaction.salesCanvas', 'convert'))
                                            <li>
                                                <button type="button"
                                                    wire:click="confirmConvertToSalesOrder({{ $canvas->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-blue-600 hover:bg-blue-600 hover:text-white dark:text-blue-400">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4" />
                                                    </svg>
                                                    Jadikan Sales Order
                                                </button>
                                            </li>
                                        @endif
                                    </ul>
                                    <div class="py-1">
                                        @if ($canvas->trashed())
                                            <button type="button" wire:click="restore({{ $canvas->id }})"
                                                @click="open = false" @disabled(!auth()->user()?->canPerform('sales.transaction.salesCanvas', 'delete'))
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-green-600 hover:bg-green-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">
                                                Pulihkan
                                            </button>
                                        @elseif ($canvas->status === 'draft')
                                            <button type="button" wire:click="confirmDelete({{ $canvas->id }})"
                                                @click="open = false" @disabled(!auth()->user()?->canPerform('sales.transaction.salesCanvas', 'delete'))
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Hapus
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-gray-400">Belum ada transaksi penjualan
                            kanvas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $salesCanvases->links() }}</div>

    @if ($showModal)
        <div
            class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm">
            <div
                class="mx-auto flex h-[80vh] max-h-[calc(100dvh-2rem)] w-full max-w-full flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-800">
                <div
                    class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-zinc-50 px-8 py-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">
                        {{ $editingId ? 'Ubah Penjualan Kanvas' : 'Tambah Penjualan Kanvas' }}</h3>
                    <button type="button" wire:click="$set('showModal', false)"
                        class="cursor-pointer text-gray-400 hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="save"
                    x-on:keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
                    class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 touch-pan-y overflow-y-auto px-8 py-6"
                        style="overscroll-behavior: contain; scrollbar-gutter: stable; -webkit-overflow-scrolling: touch;">
                        <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                            <div class="w-full">
                                <label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Nomor
                                    Canvas</label>
                                <input wire:model="canvasNo" type="text" readonly
                                    class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                            </div>
                            <div class="w-full">
                                <label
                                    class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Tanggal</label>
                                <input wire:model="date" type="date"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('date')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="w-full">
                                <label
                                    class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Salesman</label>
                                @if (auth()->user()->isSuperAdmin())
                                    <select wire:model.live="salesmanId"
                                        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                        <option value="">-- Pilih Salesman --</option>
                                        @foreach ($salesmen as $salesman)
                                            <option value="{{ $salesman->id }}">{{ $salesman->code }} -
                                                {{ $salesman->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-400">Pilih salesman yang menjalankan transaksi ini.</p>
                                @else
                                    <input type="text"
                                        value="{{ $selectedSalesman ? $selectedSalesman->code . ' - ' . $selectedSalesman->name : '' }}"
                                        readonly
                                        class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                    <p class="mt-1 text-xs text-gray-400">Otomatis mengikuti salesman yang sedang login.</p>
                                @endif
                                @error('salesmanId')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="w-full">
                                <label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Pajak
                                    (PPN 11%)</label>
                                <label class="inline-flex cursor-pointer items-center">
                                    <span
                                        class="select-none text-base font-medium text-gray-600 dark:text-gray-400">Tidak</span>
                                    <span class="relative mx-3">
                                        <input wire:model.live="tax" type="checkbox" class="peer sr-only">
                                        <span
                                            class="block h-5 w-9 rounded-full bg-red-200 after:absolute after:start-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-red-500 after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full"></span>
                                    </span>
                                    <span
                                        class="select-none text-base font-medium text-gray-600 dark:text-gray-400">Ya</span>
                                </label>
                            </div>
                            <div class="w-full">
                                <label
                                    class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Customer</label>
                                <select wire:model.live="customerId"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
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
                            <div class="w-full">
                                <label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Alamat
                                    Pengiriman</label>
                                <select wire:model="customerAddressId" @disabled(!$customerId)
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

                        <div class="mt-6 w-full">
                            <label
                                class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Catatan</label>
                            <textarea wire:model="notes" rows="3" placeholder="Masukkan catatan atau keterangan tambahan..."
                                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"></textarea>
                            @error('notes')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-12">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Detail Produk</h3>
                            </div>
                            @error('items')
                                <p class="mb-2 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <div class="overflow-x-auto">
                                <table
                                    class="w-full min-w-max border-collapse border border-gray-300 text-left text-sm text-gray-900 whitespace-nowrap dark:border-zinc-600 dark:text-white">
                                    <thead
                                        class="bg-gray-200 text-sm font-bold text-gray-900 uppercase dark:bg-zinc-700 dark:text-white">
                                        <tr>
                                            <th
                                                class="w-14 border border-gray-300 px-4 py-3 text-center dark:border-zinc-600">
                                                <button wire:click="openProductPicker" type="button"
                                                    class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-sm bg-blue-600 text-white hover:bg-blue-700">
                                                    <span class="text-xl font-semibold">+</span>
                                                </button>
                                            </th>
                                            <th class="border border-gray-300 px-4 py-3 text-sm dark:border-zinc-600">
                                                No.</th>
                                            <th class="border border-gray-300 px-4 py-3 text-sm dark:border-zinc-600">
                                                Kode Produk</th>
                                            <th class="border border-gray-300 px-4 py-3 text-sm dark:border-zinc-600">
                                                Nama Produk</th>
                                            <th class="border border-gray-300 px-4 py-3 text-sm dark:border-zinc-600">
                                                Gudang</th>
                                            <th class="border border-gray-300 px-4 py-3 text-sm dark:border-zinc-600">
                                                Satuan</th>
                                            <th class="border border-gray-300 px-4 py-3 text-sm dark:border-zinc-600">
                                                AFS (Stok Tersedia)</th>
                                            <th class="border border-gray-300 px-4 py-3 text-sm dark:border-zinc-600">
                                                Qty</th>
                                            <th class="border border-gray-300 px-4 py-3 text-sm dark:border-zinc-600">
                                                Harga</th>
                                            <th class="border border-gray-300 px-4 py-3 text-sm dark:border-zinc-600">
                                                Disc (Rp)</th>
                                            <th class="border border-gray-300 px-4 py-3 text-sm dark:border-zinc-600">
                                                Sub Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($items as $index => $item)
                                            <tr wire:key="canvas-item-{{ $item['product_id'] }}"
                                                class="text-sm hover:bg-gray-100 dark:hover:bg-zinc-800">
                                                <td
                                                    class="border border-gray-300 px-4 py-3 text-center dark:border-zinc-600">
                                                    <button wire:click="removeItem({{ $index }})"
                                                        type="button"
                                                        class="cursor-pointer text-red-500 hover:text-red-700"
                                                        aria-label="Hapus produk">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $index + 1 }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['sku'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    {{ $item['name'] }}</td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    <select wire:model.live="items.{{ $index }}.warehouse_id"
                                                        class="block w-48 rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                        <option value="">Pilih gudang</option>
                                                        @foreach ($warehouses as $warehouse)
                                                            <option value="{{ $warehouse->id }}">
                                                                {{ $warehouse->desc }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error("items.$index.warehouse_id")
                                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    <select wire:model.live="items.{{ $index }}.unit_id"
                                                        class="block w-36 rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                        @foreach ($item['unit_options'] as $unit)
                                                            <option value="{{ $unit['unit_id'] }}">
                                                                {{ $unit['unit_name'] }} (x{{ $unit['conversion'] }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    @if ($item['warehouse_id'])
                                                        <span
                                                            class="inline-flex rounded bg-blue-100 px-2.5 py-1 font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                                            {{ $item['stock_available_display'] ?? number_format($item['stock_available'], 0, ',', '.') . ' ' . ($item['base_unit_name'] ?? '') }}
                                                        </span>
                                                    @else
                                                        <span class="text-gray-400">Pilih gudang</span>
                                                    @endif
                                                </td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    <input type="text" inputmode="numeric" autocomplete="off"
                                                        x-data="{ display: '{{ number_format($item['qty'] ?? 0, 0, ',', '.') }}' }" x-model="display"
                                                        @input="
                                                            let raw = display.replace(/\./g, '').replace(/\D/g, '');
                                                            display = raw === '' ? '' : Number(raw).toLocaleString('id-ID');
                                                            $wire.set('items.{{ $index }}.qty', raw === '' ? 0 : Number(raw));
                                                        "
                                                        class="block w-24 rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                    @error("items.$index.qty")
                                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    <input type="text" inputmode="numeric" autocomplete="off"
                                                        wire:key="canvas-unit-price-{{ $item['product_id'] }}-{{ $item['unit_id'] }}"
                                                        x-data="{ display: '{{ number_format($item['unit_price'] ?? 0, 0, ',', '.') }}' }" x-model="display"
                                                        @input="
                                                            let raw = display.replace(/\./g, '').replace(/\D/g, '');
                                                            display = raw === '' ? '' : Number(raw).toLocaleString('id-ID');
                                                            $wire.set('items.{{ $index }}.unit_price', raw === '' ? 0 : Number(raw));
                                                        "
                                                        class="block w-28 rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                    @error("items.$index.unit_price")
                                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </td>
                                                <td class="border border-gray-300 px-4 py-3 dark:border-zinc-600">
                                                    <input type="text" inputmode="numeric" autocomplete="off"
                                                        x-data="{ display: '{{ number_format($item['discount_amount'] ?? 0, 0, ',', '.') }}' }" x-model="display"
                                                        @input="
                                                            let raw = display.replace(/\./g, '').replace(/\D/g, '');
                                                            display = raw === '' ? '' : Number(raw).toLocaleString('id-ID');
                                                            $wire.set('items.{{ $index }}.discount_amount', raw === '' ? 0 : Number(raw));
                                                        "
                                                        class="block w-28 rounded-lg border border-gray-300 bg-gray-50 p-2 text-xs text-gray-900 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                    @error("items.$index.discount_amount")
                                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                                    @enderror
                                                </td>
                                                <td
                                                    class="border border-gray-300 px-4 py-3 text-right font-medium dark:border-zinc-600">
                                                    Rp
                                                    {{ number_format(max(0, (int) $item['qty'] * (int) $item['unit_price'] - (int) $item['discount_amount']), 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11"
                                                    class="border border-gray-300 px-4 py-8 text-center text-sm text-gray-400 dark:border-zinc-600">
                                                    Belum ada produk. Klik tombol <strong>+</strong> untuk menambahkan.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div
                            class="mt-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-zinc-800">
                            <h4 class="mb-4 text-base font-bold text-gray-900 dark:text-white">Detail Harga</h4>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Bruto</label>
                                    <input type="text"
                                        value="Rp {{ number_format($totals['subtotal'], 0, ',', '.') }}" disabled
                                        class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Total
                                        Diskon</label>
                                    <input type="text"
                                        value="Rp {{ number_format($totals['discount'], 0, ',', '.') }}" disabled
                                        class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">PPN
                                        (11%)</label>
                                    <input type="text" value="Rp {{ number_format($totals['tax'], 0, ',', '.') }}"
                                        disabled
                                        class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Neto</label>
                                    <input type="text"
                                        value="Rp {{ number_format($totals['grand_total'], 0, ',', '.') }}" disabled
                                        class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <button wire:click="$set('showModal', false)" type="button"
                            class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2 text-sm dark:text-gray-300 hover:bg-zinc-700">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="save">Simpan</span><span wire:loading
                                wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showProductModal)
        <div
            class="fixed inset-0 z-[60] flex items-start justify-center overflow-hidden bg-black/50 p-4 backdrop-blur-sm">
            <div
                class="flex h-[80vh] max-h-[calc(100dvh-2rem)] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-900">
                <div
                    class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-zinc-700">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tambah Detail Produk</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Cari produk dan pilih dari daftar.</p>
                    </div>
                    <button wire:click="$set('showProductModal', false)" type="button"
                        class="cursor-pointer text-gray-400 hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-6">
                    <div class="grid items-end gap-4 sm:grid-cols-[1fr_auto]">
                        <div class="relative">
                            <input wire:model.live.debounce.300ms="productSearch" type="text"
                                placeholder="Cari produk (nama / kode)..."
                                class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.5 5.5a7.5 7.5 0 0 0 10.5 10.5Z" />
                            </svg>
                        </div>
                        <select wire:model.live="categoryFilter"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 sm:w-60 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                            <option value="">Semua Kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->desc }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-300 dark:border-zinc-600">
                        <table class="w-full border-collapse text-left text-sm text-gray-900 dark:text-white">
                            <thead
                                class="bg-gray-100 text-xs font-semibold uppercase dark:bg-zinc-800 dark:text-gray-200">
                                <tr>
                                    <th class="w-12 border border-gray-300 px-4 py-3 text-center dark:border-zinc-700">
                                        Pilih</th>
                                    <th class="border border-gray-300 px-4 py-3 dark:border-zinc-700">Kode</th>
                                    <th class="border border-gray-300 px-4 py-3 dark:border-zinc-700">Nama Produk</th>
                                    <th class="border border-gray-300 px-4 py-3 dark:border-zinc-700">Kategori</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    @php $alreadyAdded = collect($items)->contains('product_id', $product->id); @endphp
                                    <tr wire:key="product-picker-{{ $product->id }}"
                                        @click="$event.currentTarget.querySelector('input[type=checkbox]:not(:disabled)')?.click()"
                                        class="hover:bg-gray-50 dark:hover:bg-zinc-800 {{ $alreadyAdded ? 'cursor-not-allowed opacity-50' : 'cursor-pointer' }}">
                                        <td class="border border-gray-300 px-4 py-3 text-center dark:border-zinc-700">
                                            <input wire:model="selectedProductIds" type="checkbox"
                                                value="{{ $product->id }}" @click.stop @disabled($alreadyAdded)
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-zinc-800">
                                        </td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-700">
                                            {{ $product->name }}</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-700">
                                            {{ $product->sku }}</td>
                                        <td class="border border-gray-300 px-4 py-3 dark:border-zinc-700">
                                            {{ $product->category?->desc ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">Produk dengan
                                            harga jual tidak ditemukan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-zinc-700">
                        <button wire:click="$set('showProductModal', false)" type="button"
                            class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100 dark:border-zinc-600 dark:text-gray-300 dark:hover:bg-zinc-700">Batal</button>
                        <button wire:click="addSelectedProducts" type="button"
                            class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Tambah
                            Produk Terpilih</button>
                    </div>
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
                    <h3 class="text-lg font-semibold dark:text-white">Konfirmasi Penjualan Kanvas?</h3>
                </div>
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Setelah dikonfirmasi, Penjualan Kanvas tidak
                    dapat diubah dan dapat dilanjutkan menjadi Sales Order.</p>
                <div class="flex justify-end gap-3"><button wire:click="$set('showConfirmModal',false)"
                        type="button"
                        class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-zinc-700">Batal</button><button
                        wire:click="confirmCanvas({{ $confirmTargetId }})" wire:loading.attr="disabled"
                        wire:target="confirmCanvas" type="button"
                        class="cursor-pointer rounded-lg bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700 disabled:opacity-50"><span
                            wire:loading.remove wire:target="confirmCanvas">Ya, Konfirmasi</span><span wire:loading
                            wire:target="confirmCanvas">Memproses...</span></button></div>
            </div>
        </div>
    @endif
    @if ($showConvertModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="mx-4 w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <div class="mb-4 flex items-center gap-3">
                    <div class="rounded-full bg-blue-900 p-2"><svg class="h-5 w-5 text-blue-300" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4" />
                        </svg></div>
                    <h3 class="text-base font-semibold dark:text-white">Jadikan Sales Order?</h3>
                </div>
                <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">Header dan seluruh produk akan disalin ke
                    Sales Order. Penjualan kanvas ini akan dikunci dan statusnya berubah menjadi Sales Order.</p>
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('showConvertModal', false)"
                        class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-zinc-700">Batal</button>
                    <button wire:click="convertToSalesOrder" wire:loading.attr="disabled"
                        class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"><span
                            wire:loading.remove wire:target="convertToSalesOrder">Ya, Buat Sales Order</span><span
                            wire:loading wire:target="convertToSalesOrder">Memproses...</span></button>
                </div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Hapus Penjualan Kanvas?</h3>
                <p class="mb-6 text-sm text-gray-400">Data akan dipindahkan ke tempat sampah.</p>
                <div class="flex justify-end gap-3">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2 text-sm hover:bg-zinc-700 dark:text-gray-300">Batal</button>
                    <button wire:click="delete" @disabled(!auth()->user()?->canPerform('sales.transaction.salesCanvas', 'delete'))
                        class="cursor-pointer rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40">Hapus</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showDetailModal && $selectedCanvas)
        <div
            class="fixed inset-0 z-50 flex items-start justify-center overflow-hidden bg-black/60 p-4 backdrop-blur-sm">
            <div
                class="flex max-h-[min(80vh,calc(100dvh-2rem))] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-zinc-700">
                    <h3 class="font-semibold dark:text-white">Rincian Transaksi {{ $selectedCanvas->canvas_no }}</h3>
                    <button wire:click="$set('showDetailModal', false)"
                        class="cursor-pointer text-2xl text-gray-500">&times;</button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <dl class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <div>
                            <dt class="text-gray-400">Tanggal</dt>
                            <dd class="font-medium dark:text-white">{{ $selectedCanvas->date->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Salesman</dt>
                            <dd class="font-medium dark:text-white">{{ $selectedCanvas->salesman?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Customer</dt>
                            <dd class="font-medium dark:text-white">{{ $selectedCanvas->customer?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-400">Status</dt>
                            <dd class="font-medium uppercase dark:text-white">{{ $selectedCanvas->status }}</dd>
                        </div>
                    </dl>
                    <div class="mt-5 overflow-x-auto rounded-lg border border-gray-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-zinc-900">
                                <tr>
                                    <th class="px-3 py-2 text-left">Produk</th>
                                    <th class="px-3 py-2 text-left">Gudang</th>
                                    <th class="px-3 py-2 text-left">Satuan</th>
                                    <th class="px-3 py-2 text-right">Qty</th>
                                    <th class="px-3 py-2 text-right">Harga</th>
                                    <th class="px-3 py-2 text-right">Diskon</th>
                                    <th class="px-3 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @foreach ($selectedCanvas->items as $item)
                                    <tr>
                                        <td class="px-3 py-2 dark:text-white">{{ $item->product?->sku }}</td>
                                        <td class="px-3 py-2">{{ $item->warehouse?->desc }}</td>
                                        <td class="px-3 py-2">{{ $item->unit?->name }}</td>
                                        <td class="px-3 py-2 text-right">{{ $item->qty }}</td>
                                        <td class="px-3 py-2 text-right">Rp
                                            {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right">Rp
                                            {{ number_format($item->discount_amount, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right">Rp
                                            {{ number_format($item->line_total, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="ml-auto mt-5 w-full max-w-sm space-y-2 text-sm">
                        <div class="flex justify-between"><span>Subtotal</span><span>Rp
                                {{ number_format($selectedCanvas->subtotal, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span>Diskon</span><span>- Rp
                                {{ number_format($selectedCanvas->discount_total, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span>PPN</span><span>Rp
                                {{ number_format($selectedCanvas->tax_amount, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between border-t pt-2 text-base font-bold dark:text-white"><span>Grand
                                Total</span><span>Rp
                                {{ number_format($selectedCanvas->grand_total, 0, ',', '.') }}</span></div>
                    </div>
                </div>
                <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-zinc-700"><button
                        wire:click="$set('showDetailModal', false)"
                        class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm dark:text-white">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</div>
