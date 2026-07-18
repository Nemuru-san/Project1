<div x-data="{ toastMsg: '', toastType: '', showAddProductModal: false }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- FILTER BAR --}}
    <div
        class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 my-4 dark:bg-zinc-900">
        <div class="flex flex-col sm:flex-row sm:flex-wrap items-center gap-3 w-full">
            {{-- Search --}}
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg aria-hidden="true" class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5 placeholder-gray-400"
                    placeholder="Cari kode, nama, kontak..." />
            </div>
            {{-- Status Filter --}}
            {{-- <select wire:model.live="statusFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Status</option>
                <option value="1">Draf</option>
                <option value="0">Disetujui</option>
                <option value="0">Lunas</option>
                <option value="0">Dibayar Sebagian</option>
                <option value="0">Canceled</option>
            </select> --}}
            <select wire:model.live="statusFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Status</option>
                <option value="Draft">Draf</option>
                <option value="Posted">Diposting</option>
            </select>

            <select wire:model.live="paymentStatusFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Pembayaran</option>
                <option value="Unpaid">Belum Lunas</option>
                <option value="Partial Paid">Dibayar Sebagian</option>
                <option value="Paid">Lunas</option>
            </select>
            {{-- Per Page --}}
            <select wire:model.live="perPage"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 w-full sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>
            {{-- Show Trashed --}}
            <label class="flex items-center gap-2 text-sm dark:text-gray-300 cursor-pointer whitespace-nowrap">
                <input type="checkbox" wire:model.live="showTrashed"
                    class="w-4 h-4 rounded border-gray-600 dark:bg-zinc-800 text-blue-600">
                Tampilkan Terhapus
            </label>
            {{-- Export --}}
            {{-- <button wire:click="export"
                class="inline-flex items-center gap-2 text-white bg-indigo-600 hover:bg-indigo-700 border border-transparent text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12v6m0 0l-3-3m3 3l3-3M12 3v9" />
                </svg>
                Export CSV
            </button> --}}
            <button wire:click="openCreate"
                class="order-last sm:ml-auto inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 border border-transparent text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap cursor-pointer sm:w-auto w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Transaksi
            </button>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-4">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">Rentang tanggal</span>
        <input wire:model.live="dateFrom" type="date" title="Tanggal mulai" aria-label="Tanggal mulai"
            class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-3 py-2.5 w-full sm:w-auto">
        <span class="hidden sm:inline text-gray-400">s.d.</span>
        <input wire:model.live="dateTo" type="date" title="Tanggal akhir" aria-label="Tanggal akhir"
            class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-3 py-2.5 w-full sm:w-auto">
        <button wire:click="resetFilters" type="button"
            class="rounded-lg border border-gray-600 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-zinc-800 cursor-pointer">
            Bersihkan Filter
        </button>
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-lg font-bold uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('code')">
                        <div class="flex items-center gap-1">
                            PIV No
                            @if ($sortField === 'code')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4">Faktur Pemasok</th>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('date')">
                        <div class="flex items-center gap-1">
                            Tanggal
                            @if ($sortField === 'date')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-4">PO No</th>
                    <th class="px-4 py-4">Pemasok</th>
                    <th class="px-4 py-4 text-right">Grand Total</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Pembayaran</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>

            <tbody class="dark:bg-zinc-950 text-base">
                @forelse ($invoices as $invoice)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $invoice->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $invoice->code }}
                        </td>
                        <td class="px-4 py-4">
                            {{ $invoice->supplier_invoice_number ?: '-' }}
                        </td>
                        <td class="px-4 py-4">
                            {{ $invoice->date?->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-4">
                            {{ $invoice->purchaseOrder?->code ?? '-' }}
                        </td>
                        <td class="px-4 py-4">
                            {{ $invoice->supplier?->name ?? '-' }}
                        </td>
                        <td class="px-4 py-4 text-right font-medium text-gray-900 dark:text-white">
                            Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-4">
                            @if ($invoice->trashed())
                                <span class="text-sm px-2.5 py-0.5 rounded bg-red-700 text-white">Terhapus</span>
                            @elseif ($invoice->status === 'Draft')
                                <span class="text-sm px-2.5 py-0.5 rounded bg-gray-600 text-white">Draf</span>
                            @elseif ($invoice->status === 'Posted')
                                <span class="text-sm px-2.5 py-0.5 rounded bg-green-700 text-white">Diposting</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @if ($invoice->payment_status === 'Paid')
                                <span class="text-sm px-2.5 py-0.5 rounded bg-green-700 text-white">Lunas</span>
                            @elseif ($invoice->payment_status === 'Partial Paid')
                                <span class="text-sm px-2.5 py-0.5 rounded bg-yellow-600 text-white">Dibayar Sebagian</span>
                            @else
                                <span class="text-sm px-2.5 py-0.5 rounded bg-red-700 text-white">Belum Lunas</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <div class="inline-block" x-data="{
                                open: false,
                                top: 0,
                                left: 0,
                                toggle($el) {
                                    const rect = $el.getBoundingClientRect();
                            
                                    this.top = rect.bottom + 6;
                                    this.left = rect.left - 128;
                            
                                    this.open = !this.open;
                                }
                            }">
                                <button @click="toggle($el)" @click.outside="open = false"
                                    class="inline-flex items-center p-0.5 text-md font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100 cursor-pointer"
                                    type="button">
                                    <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </button>

                                <div x-show="open" x-cloak :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                                    class="z-50 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600">
                                    @if ($invoice->trashed())
                                        <div class="px-4 py-2 text-sm text-gray-400">
                                            Data sudah terhapus
                                        </div>
                                    @else
                                        @php
                                            $locked = $invoice->status !== 'Draft';
                                        @endphp

                                        <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                            @if ($invoice->status === 'Draft')
                                                <li>
                                                    <button wire:click="confirmPost({{ $invoice->id }})"
                                                        @click="open = false"
                                                        class="flex items-center gap-2 w-full py-2 px-4 text-blue-700 hover:bg-blue-600 hover:text-white dark:text-blue-300 dark:hover:bg-blue-600 dark:hover:text-white cursor-pointer">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Posting Faktur
                                                    </button>
                                                </li>
                                            @endif

                                            <li>
                                                <a href="{{ route('purchases.transaction.purchase-invoice.print', $invoice->id) }}"
                                                    target="_blank" @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M6 9V4h12v5M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v6H6v-6z" />
                                                    </svg>Cetak
                                                </a>
                                            </li>

                                            <li>
                                                <button wire:click="openDetail({{ $invoice->id }})"
                                                    @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>Detail
                                                </button>
                                            </li>

                                            <li>
                                                <button
                                                    @if (!$locked) wire:click="openEdit({{ $invoice->id }})" @endif
                                                    @click="open = false" @disabled($locked)
                                                    class="flex items-center gap-2 w-full py-2 px-4 {{ $locked ? 'opacity-40 cursor-not-allowed text-gray-400 dark:text-gray-500' : 'hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer' }}">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>Ubah
                                                </button>
                                            </li>
                                        </ul>

                                        <div class="py-1">
                                            <button
                                                @if (!$locked) wire:click="confirmDelete({{ $invoice->id }})" @endif
                                                @click="open = false" @disabled($locked)
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base {{ $locked ? 'opacity-40 cursor-not-allowed text-gray-400 dark:text-gray-500' : 'text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white cursor-pointer' }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>Hapus
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-400 dark:text-gray-500">
                            Tidak ada data purchase invoice.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $invoices->links() }}
    </div>

    {{-- SHOW MODAL --}}
    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-hidden bg-black/50 backdrop-blur-sm p-4">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-full mx-auto h-[80vh] max-h-[calc(100dvh-2rem)] flex flex-col overflow-hidden"
                    @click.outside="$wire.showModal = false">
                    <div
                        class="flex items-center justify-between px-8 py-6 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                        <h3 class="text-lg font-semibold dark:text-white">
                            Tambah Faktur Pembelian
                        </h3>
                        <button wire:click="$set('showModal', false)"
                            class="text-gray-400 hover:text-white cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-8 py-6">
                        <form action="#">
                            <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                                <div class="w-full">
                                    <label for="piv_no"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">PIV
                                        No</label>
                                    <input type="text" value="{{ $code ?: 'Auto Generated' }}"
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed"
                                        placeholder="Auto Generated" disabled>
                                </div>

                                <div class="w-full">
                                    <label for="supplier_invoice_number"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                        No. Faktur Pemasok
                                    </label>
                                    <input wire:model="supplier_invoice_number" type="text"
                                        id="supplier_invoice_number"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                        placeholder="Input nomor invoice dari supplier">
                                    @error('supplier_invoice_number')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <label for="po_no"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">PO
                                        No</label>
                                    <select wire:model.live="purchase_order_id"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                        <option value="">-- Pilih PO --</option>
                                        @foreach ($purchaseOrders as $po)
                                            <option value="{{ $po->id }}">
                                                {{ $po->code }} - {{ $po->supplier?->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('purchase_order_id')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <label for="supplier"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Pemasok</label>
                                    <select disabled
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed">
                                        <option value="">-- Pemasok otomatis dari PO --</option>
                                        @foreach ($purchaseOrders as $po)
                                            @if ($supplier_id === $po->supplier_id)
                                                <option selected>{{ $po->supplier?->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('supplier_id')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <label for="date"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Tanggal</label>
                                    <input wire:model.live="date" type="date"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    @error('date')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <label for="top_term"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                        TOP / Termin Pembayaran
                                    </label>
                                    <select wire:model.live="top_term" id="top_term"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                        <option value="">-- Pilih Termin Pembayaran --</option>
                                        <option value="7">7 Hari</option>
                                        <option value="30">1 Bulan</option>
                                        <option value="90">3 Bulan</option>
                                        <option value="custom">Kustom</option>
                                    </select>
                                </div>

                                @if ($top_term === 'custom')
                                    <div class="w-full">
                                        <label for="custom_top"
                                            class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                            Tanggal Jatuh Tempo Khusus
                                        </label>
                                        <input wire:model.live="custom_top" type="date" id="custom_top"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    </div>
                                @endif

                                <div class="w-full">
                                    <label for="due_date"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                        Tanggal Jatuh Tempo / Tanggal Jatuh Tempo
                                    </label>
                                    <input type="date" id="due_date" value="{{ $due_date }}"
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed"
                                        disabled>
                                    @error('due_date')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="w-full">
                                    <label for="tax"
                                        class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Pajak</label>
                                    <label class="inline-flex items-center cursor-pointer">
                                        <span
                                            class="select-none text-base font-medium text-gray-600 dark:text-gray-400">Tidak</span>
                                        <div class="relative mx-3">
                                            <input type="checkbox" wire:model.live="tax" class="sr-only peer">
                                            <div
                                                class="w-9 h-5 bg-red-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-red-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-red-500 after:content-[''] after:absolute after:top-0.5 after:start-0.5 after:bg-red-500 after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600">
                                            </div>
                                        </div>
                                        <span
                                            class="select-none text-base font-medium text-gray-600 dark:text-gray-400">Ya</span>
                                    </label>
                                </div>

                                <div class="w-full sm:col-span-2 mt-2">
                                    <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                        Note
                                    </label>
                                    <textarea wire:model="note" rows="3"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white"
                                        placeholder="Masukkan catatan atau keterangan tambahan..."></textarea>
                                    @error('note')
                                        <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </form>

                        {{-- tabel --}}
                        <div class="mt-12">
                            <div class="w-full grid grid-cols-1 sm:grid-cols-2  gap-6 mb-4 items-center">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Detail Produk</h3>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table
                                    class="w-full text-base text-left text-gray-900 dark:text-white my-2 min-w-425 whitespace-nowrap border-collapse border border-gray-300 dark:border-zinc-600">
                                    <thead
                                        class="text-base font-bold text-gray-900 uppercase bg-gray-200 dark:bg-zinc-700 dark:text-white">
                                        <tr>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">No.</th>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                                PO No</th>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                                GR No</th>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                                Kode Produk</th>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                                Nama Produk</th>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                                Kategori</th>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                                Satuan</th>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                                Jumlah Pesanan</th>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Harga</th>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                                Disc Amount</th>
                                            <th scope="col"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">
                                                Sub Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($visibleItemRows as $index => $row)
                                            @php
                                                $realIndex = $row['_index'];
                                            @endphp
                                            <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                                <td
                                                    class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-medium text-gray-900 dark:text-white">
                                                    {{ $itemRowsFrom + $index }}
                                                </td>
                                                <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                    {{ $row['po_code'] ?? '-' }}
                                                </td>
                                                <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                    -
                                                </td>
                                                <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                    {{ $row['product_code'] ?? '-' }}
                                                </td>
                                                <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                    {{ $row['product_name'] ?? '-' }}
                                                </td>
                                                <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                    {{ $row['category_name'] ?? '-' }}
                                                </td>
                                                <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                    {{ $row['unit_name'] ?? '-' }}
                                                </td>
                                                <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                    {{ number_format($row['qty'] ?? 0, 0, ',', '.') }}
                                                </td>
                                                {{-- PRICE --}}
                                                <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                    <input type="text" inputmode="numeric" autocomplete="off"
                                                        x-data="{
                                                            display: '{{ number_format($row['price'] ?? 0, 0, ',', '.') }}'
                                                        }" x-model="display"
                                                        @input="
                                                            let raw = display.replace(/\./g, '').replace(/\D/g, '');
                                                            display = raw === '' ? '' : Number(raw).toLocaleString('id-ID');
                                                            $wire.set('itemRows.{{ $row['_index'] }}.price', raw === '' ? 0 : Number(raw));
                                                        "
                                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-32 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                                </td>

                                                {{-- DISCOUNT --}}
                                                <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                    <input type="text" inputmode="numeric" autocomplete="off"
                                                        x-data="{
                                                            display: '{{ number_format($row['discount'] ?? 0, 0, ',', '.') }}'
                                                        }" x-model="display"
                                                        @input="
                                                            let raw = display.replace(/\./g, '').replace(/\D/g, '');
                                                            display = raw === '' ? '' : Number(raw).toLocaleString('id-ID');
                                                            $wire.set('itemRows.{{ $row['_index'] }}.discount', raw === '' ? 0 : Number(raw));
                                                        "
                                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-32 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                                </td>
                                                <td
                                                    class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-right text-gray-900 dark:text-white">
                                                    Rp {{ number_format($row['total'] ?? 0, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11"
                                                    class="border border-gray-300 dark:border-zinc-600 px-4 py-8 text-center text-gray-400">
                                                    Pilih Pesanan Pembelian terlebih dahulu.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <nav class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 p-4 mt-4 dark:border-zinc-700 dark:bg-zinc-900"
                            aria-label="Navigasi tabel produk">
                            <span class="text-base font-normal text-gray-500 dark:text-gray-400">
                                Showing
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ $itemRowsFrom }}-{{ $itemRowsTo }}
                                </span>
                                of
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ $itemRowsTotal }}
                                </span>
                            </span>

                            <ul class="inline-flex items-stretch -space-x-px">
                                <li>
                                    <button type="button" wire:click="previousItemPage" @disabled($itemPage <= 1)
                                        class="flex items-center justify-center h-full py-1.5 px-3 ml-0 text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <span class="sr-only">Sebelumnya</span>
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </li>

                                @for ($page = 1; $page <= $itemRowsLastPage; $page++)
                                    <li>
                                        <button type="button" wire:click="goToItemPage({{ $page }})"
                                            class="flex items-center justify-center text-base py-2 px-3 leading-tight border border-gray-300 dark:border-gray-700
                    {{ $itemPage === $page
                        ? 'z-10 text-primary-600 bg-primary-50 border-primary-300 dark:bg-gray-700 dark:text-white'
                        : 'text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white' }}">
                                            {{ $page }}
                                        </button>
                                    </li>
                                @endfor

                                <li>
                                    <button type="button" wire:click="nextItemPage" @disabled($itemPage >= $itemRowsLastPage)
                                        class="flex items-center justify-center h-full py-1.5 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <span class="sr-only">Berikutnya</span>
                                        <svg class="w-5 h-5" aria-hidden="true" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </li>
                            </ul>
                        </nav>

                        <div
                            class="mt-6 p-4 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h4 class="mb-4 text-base font-bold text-gray-900 dark:text-white">Detail Harga</h4>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label for="gross"
                                        class="block mb-4 text-base font-medium text-gray-900 dark:text-white">Bruto</label>
                                    <input type="text" id="gross"
                                        value="Rp {{ number_format($sub_total, 0, ',', '.') }}" disabled
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed">
                                </div>

                                <div>
                                    <label for="total_disc"
                                        class="block mb-4 text-base font-medium text-gray-900 dark:text-white">Total
                                        Diskon</label>
                                    <input type="text" id="total_disc"
                                        value="Rp {{ number_format($discount_total, 0, ',', '.') }}" disabled
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed">
                                </div>

                                <div>
                                    <label for="ppn"
                                        class="block mb-4 text-base font-medium text-gray-900 dark:text-white">PPN
                                        (11%)</label>
                                    <input type="text" id="ppn"
                                        value="Rp {{ number_format($tax_amount, 0, ',', '.') }}" disabled
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed">
                                </div>

                                <div>
                                    <label for="nett"
                                        class="block mb-4 text-base font-medium text-gray-900 dark:text-white">Neto</label>
                                    <input type="text" id="nett"
                                        value="Rp {{ number_format($grand_total, 0, ',', '.') }}" disabled
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 cursor-not-allowed">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex justify-end gap-2 p-6 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-white dark:bg-zinc-900">
                        <button wire:click="$set('showModal', false)"
                            class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                            Batal
                        </button>
                        <button wire:click="save" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm rounded-lg bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 cursor-pointer">
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </div>
        </div>
    @endif

    @if ($showDetail && $selectedInvoice)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-hidden bg-black/60 backdrop-blur-sm p-4">
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[min(80vh,calc(100dvh-2rem))] overflow-y-auto">

                <div class="flex items-center justify-between px-6 py-4 border-b dark:border-zinc-700">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Detail Faktur Pembelian</h2>
                        <p class="text-sm text-gray-400 font-mono mt-0.5">{{ $selectedInvoice->code }}</p>
                    </div>

                    <button wire:click="closeDetail"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-white transition cursor-pointer">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">PIV No</span>
                                <span class="font-mono font-medium text-gray-800 dark:text-white">
                                    {{ $selectedInvoice->code }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Faktur Pemasok</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedInvoice->supplier_invoice_number ?: '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Tanggal</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedInvoice->date?->format('d F Y') ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Tanggal Jatuh Tempo</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedInvoice->due_date?->format('d F Y') ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">PO No</span>
                                <span class="font-mono text-gray-800 dark:text-white">
                                    {{ $selectedInvoice->purchaseOrder?->code ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Pemasok</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedInvoice->supplier?->name ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Status</span>
                                <span>
                                    @php
                                        $statusClass = match ($selectedInvoice->status) {
                                            'Draft' => 'bg-zinc-600 text-white',
                                            'Posted' => 'bg-green-700 text-white',
                                            default => 'bg-zinc-600 text-white',
                                        };
                                    @endphp

                                    <span class="text-sm px-2.5 py-0.5 rounded {{ $statusClass }}">
                                        {{ $selectedInvoice->status }}
                                    </span>
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Pembayaran</span>
                                <span>
                                    @php
                                        $paymentClass = match ($selectedInvoice->payment_status) {
                                            'Paid' => 'bg-green-700 text-white',
                                            'Partial Paid' => 'bg-yellow-600 text-white',
                                            default => 'bg-red-700 text-white',
                                        };
                                    @endphp

                                    <span class="text-sm px-2.5 py-0.5 rounded {{ $paymentClass }}">
                                        {{ $selectedInvoice->payment_status }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Pajak</span>
                            <span class="text-gray-800 dark:text-white">
                                {{ $selectedInvoice->tax ? 'Ya' : 'Tidak' }}
                            </span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-400">Catatan</span>
                            <span class="text-gray-800 dark:text-white text-right max-w-xs">
                                {{ $selectedInvoice->note ?: '-' }}
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border dark:border-zinc-700">
                        <table class="w-full text-sm text-left">
                            <thead
                                class="bg-gray-50 dark:bg-zinc-800 text-gray-500 dark:text-gray-300 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 w-8">No.</th>
                                    <th class="px-4 py-3">Produk</th>
                                    <th class="px-4 py-3">Satuan</th>
                                    <th class="px-4 py-3 text-right">Jumlah</th>
                                    <th class="px-4 py-3 text-right">Harga</th>
                                    <th class="px-4 py-3 text-right">Diskon</th>
                                    <th class="px-4 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>

                            <tbody class="dark:bg-zinc-900 divide-y divide-gray-100 dark:divide-zinc-700">
                                @forelse($selectedInvoice->items as $i => $item)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            <div class="font-medium">{{ $item->product?->name ?? '-' }}</div>
                                            <div class="text-xs text-gray-400 font-mono">
                                                {{ $item->product?->sku ?? ($item->product?->code ?? '') }}
                                            </div>
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            {{ $item->unit?->name ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            {{ number_format($item->qty, 0, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            Rp {{ number_format($item->price, 0, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            Rp {{ number_format($item->discount, 0, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            Rp {{ number_format($item->total, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-gray-400">
                                            Tidak ada item.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end">
                        <div class="space-y-2 text-sm w-full max-w-xs">
                            <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                <span>Bruto</span>
                                <span>Rp {{ number_format($selectedInvoice->sub_total, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                <span>Total Diskon</span>
                                <span>Rp {{ number_format($selectedInvoice->discount_total, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                <span>PPN</span>
                                <span>Rp {{ number_format($selectedInvoice->tax_amount, 0, ',', '.') }}</span>
                            </div>

                            <div
                                class="flex justify-between font-bold text-base text-gray-800 dark:text-white border-t dark:border-zinc-700 pt-2">
                                <span>Neto</span>
                                <span>Rp {{ number_format($selectedInvoice->grand_total, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    @if ($selectedInvoice->status === 'Draft')
                        <div class="border-t dark:border-zinc-700 pt-5">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Ubah Status</h4>

                            <button wire:click="confirmPost({{ $selectedInvoice->id }})"
                                class="px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium cursor-pointer">
                                Posting Faktur
                            </button>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-4 border-t dark:border-zinc-700 flex justify-end">
                    <button wire:click="closeDetail"
                        class="px-5 py-2 rounded-lg bg-zinc-700 hover:bg-zinc-600 text-white text-sm font-medium transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showPostModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-blue-900 rounded-full">
                        <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <h3 class="text-base font-semibold dark:text-white">
                        Posting Faktur Pembelian?
                    </h3>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    Setelah invoice diposting, data tidak bisa diedit atau dihapus. Faktur akan dikunci dan siap
                    digunakan untuk proses pembayaran.
                </p>

                <div class="flex justify-end gap-2">
                    <button wire:click="cancelPost"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 cursor-pointer">
                        Batal
                    </button>

                    <button wire:click="postInvoice" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="postInvoice">
                            Ya, Posting Faktur
                        </span>
                        <span wire:loading wire:target="postInvoice">
                            Mem-post...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl w-full max-w-sm mx-4 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-red-900 rounded-full">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold dark:text-white">Hapus Faktur Pembelian?</h3>
                </div>
                <p class="text-sm text-gray-400 mb-5">
                    Data akan dipindahkan ke tempat sampah. Faktur yang sudah diposting tidak bisa dihapus.
                </p>
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700">
                        Batal
                    </button>
                    <button wire:click="delete"
                        class="px-4 py-2 text-sm rounded-lg bg-red-700 text-white hover:bg-red-800 cursor-pointer">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
