<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- FILTER BAR --}}
    <div class="flex flex-col gap-3 my-4 dark:bg-zinc-900">
        <div class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-3 w-full">
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
                    placeholder="Cari kode, supplier, metode..." />
            </div>

            <select wire:model.live="statusFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Status</option>
                <option value="Draft">Draf</option>
                <option value="Posted">Diposting</option>
            </select>

            <select wire:model.live="perPage"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 w-full sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>

            <label
                class="flex items-center gap-2 rounded-lg border border-gray-600 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer whitespace-nowrap">
                <input type="checkbox" wire:model.live="showTrashed"
                    class="w-4 h-4 rounded border-gray-600 dark:bg-zinc-800 text-blue-600">
                Tampilkan Terhapus
            </label>

            <button wire:click="openCreate"
                class="order-last sm:ml-auto inline-flex items-center gap-2 text-white bg-blue-600 hover:bg-blue-700 border border-transparent text-sm font-medium px-4 py-2.5 rounded-lg whitespace-nowrap cursor-pointer sm:w-auto w-full justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Pembayaran
            </button>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <span class="text-sm font-medium text-gray-600 dark:text-gray-300 sm:min-w-28">Rentang tanggal</span>
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
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400">
            <thead class="text-lg font-bold uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('code')">
                        <div class="flex items-center gap-1">
                            No. Pembayaran
                            @if ($sortField === 'code')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('payment_date')">
                        <div class="flex items-center gap-1">
                            Tanggal
                            @if ($sortField === 'payment_date')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4">Pemasok</th>
                    <th class="px-4 py-4">Rekening Bank</th>
                    <th class="px-4 py-4">Metode</th>
                    <th class="px-4 py-4 text-right">Total Nilai</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Aksi</th>
                </tr>
            </thead>

            <tbody class="dark:bg-zinc-950 text-base">
                @forelse ($payments as $payment)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $payment->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $payment->code }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $payment->payment_date?->format('d/m/Y') }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $payment->supplier?->name ?? '-' }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $payment->bankAccount?->name ?? ($payment->bankAccount?->bank_name ?? '-') }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $payment->payment_method ?: '-' }}
                        </td>

                        <td class="px-4 py-4 text-right font-medium text-gray-900 dark:text-white">
                            Rp {{ number_format($payment->total_amount, 0, ',', '.') }}
                        </td>

                        <td class="px-4 py-4">
                            @if ($payment->trashed())
                                <span class="text-sm px-2.5 py-0.5 rounded bg-red-700 text-white">Terhapus</span>
                            @elseif ($payment->status === 'Draft')
                                <span class="text-sm px-2.5 py-0.5 rounded bg-gray-600 text-white">Draf</span>
                            @elseif ($payment->status === 'Posted')
                                <span class="text-sm px-2.5 py-0.5 rounded bg-green-700 text-white">Diposting</span>
                            @else
                                <span
                                    class="text-sm px-2.5 py-0.5 rounded bg-gray-700 text-white">{{ $payment->status }}</span>
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
                                    @if ($payment->trashed())
                                        <div class="px-4 py-2 text-sm text-gray-400">
                                            Data sudah terhapus
                                        </div>
                                    @else
                                        @php
                                            $locked = $payment->status !== 'Draft';
                                        @endphp

                                        <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                            @if ($payment->status === 'Draft')
                                                <li>
                                                    <button wire:click="confirmPost({{ $payment->id }})"
                                                        @click="open = false"
                                                        class="flex items-center gap-2 w-full py-2 px-4 text-blue-700 hover:bg-blue-600 hover:text-white dark:text-blue-300 dark:hover:bg-blue-600 dark:hover:text-white cursor-pointer">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        Posting Pembayaran
                                                    </button>
                                                </li>
                                            @endif

                                            <li>
                                                <button wire:click="openDetail({{ $payment->id }})"
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
                                                    @if (!$locked) wire:click="openEdit({{ $payment->id }})" @endif
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
                                                @if (!$locked) wire:click="confirmDelete({{ $payment->id }})" @endif
                                                @click="open = false" @disabled($locked)
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base {{ $locked ? 'opacity-40 cursor-not-allowed text-gray-400 dark:text-gray-500' : 'text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white cursor-pointer' }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 001 1v3M4 7h16" />
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
                        <td colspan="8" class="text-center py-8 text-gray-400 dark:text-gray-500">
                            Tidak ada data Pembayaran Utang.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $payments->links() }}
    </div>

    {{-- CREATE / EDIT MODAL --}}
    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-full mx-auto h-[90vh] flex flex-col overflow-hidden">
                <div
                    class="flex items-center justify-between px-8 py-6 border-b border-gray-200 dark:border-zinc-700 shrink-0 bg-zinc-50 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">
                        {{ $paymentId ? 'Edit AP Payment' : 'Tambah AP Payment' }}
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
                    <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                No. Pembayaran
                            </label>
                            <input type="text" value="{{ $code ?: 'Auto Generated' }}" disabled
                                class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed">
                        </div>

                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                Tanggal Pembayaran
                            </label>
                            <input wire:model.live="payment_date" type="date"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                            @error('payment_date')
                                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Pemasok
                            </label>
                            <select wire:model.live="supplier_id" @disabled($paymentId)
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white disabled:bg-gray-100 disabled:dark:bg-zinc-700 disabled:cursor-not-allowed">
                                <option value="">-- Pilih Pemasok --</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            @error('supplier_id')
                                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Rekening Bank
                            </label>
                            <select wire:model.live="bank_account_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                <option value="">-- Pilih Bank Account --</option>
                                @foreach ($bankAccounts as $bank)
                                    <option value="{{ $bank->id }}">
                                        {{ $bank->name ?? ($bank->bank_name ?? 'Bank Account #' . $bank->id) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('bank_account_id')
                                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">Metode Pembayaran
                            </label>
                            <select wire:model.live="payment_method"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                <option value="Transfer">Transfer</option>
                                <option value="Cash">Tunai</option>
                                <option value="Giro">Giro</option>
                                <option value="Other">Lainnya</option>
                            </select>
                            @error('payment_method')
                                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                Total Nilai
                            </label>
                            <input type="text" disabled value="Rp {{ number_format($total_amount, 0, ',', '.') }}"
                                class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed">
                            @error('total_amount')
                                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full sm:col-span-2">
                            <label class="block mb-3 text-base font-medium text-gray-900 dark:text-white">
                                Note
                            </label>
                            <textarea wire:model="note" rows="3"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-800 dark:border-gray-600 dark:text-white"
                                placeholder="Catatan pembayaran..."></textarea>
                            @error('note')
                                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- DETAIL INVOICE --}}
                    <div class="mt-12">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Detail Faktur Dibayar</h3>
                            @if ($supplier_id && !$paymentId)
                                <button type="button" wire:click="loadSupplierInvoices"
                                    class="px-4 py-2 text-sm rounded-lg bg-zinc-700 hover:bg-zinc-600 text-white cursor-pointer">
                                    Muat Ulang Faktur
                                </button>
                            @endif
                        </div>

                        <div class="overflow-x-auto">
                            <table
                                class="w-full text-base text-left text-gray-900 dark:text-white my-2 min-w-375 whitespace-nowrap border-collapse border border-gray-300 dark:border-zinc-600">
                                <thead
                                    class="text-base font-bold text-gray-900 uppercase bg-gray-200 dark:bg-zinc-700 dark:text-white">
                                    <tr>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Tidak
                                        </th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">PIV
                                            No</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Faktur Pemasok</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Tanggal
                                        </th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Due
                                            Tanggal</th>
                                        <th
                                            class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm text-right">
                                            Total Keseluruhan</th>
                                        <th
                                            class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm text-right">Lunas</th>
                                        <th
                                            class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm text-right">
                                            Remaining</th>
                                        <th
                                            class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm text-right">
                                            Amount</th>
                                        <th class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-sm">Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($detailRows as $index => $row)
                                        <tr class="hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm">
                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $index + 1 }}
                                            </td>

                                            <td
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 font-mono">
                                                {{ $row['invoice_code'] ?? '-' }}
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $row['supplier_invoice_number'] ?? '-' }}
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $row['date'] ?? '-' }}
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                {{ $row['due_date'] ?? '-' }}
                                            </td>

                                            <td
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-right">
                                                Rp {{ number_format($row['grand_total'] ?? 0, 0, ',', '.') }}
                                            </td>

                                            <td
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-right">
                                                Rp {{ number_format($row['paid_amount'] ?? 0, 0, ',', '.') }}
                                            </td>

                                            <td
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-3 text-right">
                                                Rp {{ number_format($row['remaining_amount'] ?? 0, 0, ',', '.') }}
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <input type="text" inputmode="numeric" autocomplete="off"
                                                    x-data="{
                                                        display: '{{ number_format($row['amount'] ?? 0, 0, ',', '.') }}',
                                                        index: {{ $index }}
                                                    }" x-model="display"
                                                    @amount-updated.window="
                                                        if ($event.detail.index === index) {
                                                            let val = $event.detail.amount;
                                                                display = val === 0 ? '' : Number(val).toLocaleString('id-ID');
                                                            }
                                                        "
                                                    @input="
                                                        let raw = display.replace(/\./g, '').replace(/\D/g, '');
                                                        display = raw === '' ? '' : Number(raw).toLocaleString('id-ID');
                                                        $wire.set('detailRows.{{ $index }}.amount', raw === '' ? 0 : Number(raw));
                                                    "
                                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg block w-36 p-2 dark:bg-zinc-800 dark:border-gray-600 dark:text-white">
                                                @error("detailRows.$index.amount")
                                                    <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
                                                @enderror
                                            </td>

                                            <td class="border border-gray-300 dark:border-zinc-600 px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <button type="button" wire:click="payFull({{ $index }})"
                                                        class="px-3 py-1.5 rounded bg-green-700 hover:bg-green-800 text-white text-xs cursor-pointer">
                                                        Full
                                                    </button>

                                                    <button type="button"
                                                        wire:click="clearAmount({{ $index }})"
                                                        class="px-3 py-1.5 rounded bg-zinc-600 hover:bg-zinc-700 text-white text-xs cursor-pointer">
                                                        Clear
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10"
                                                class="border border-gray-300 dark:border-zinc-600 px-4 py-8 text-center text-gray-400">
                                                Pilih supplier terlebih dahulu / tidak ada invoice yang belum lunas.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div
                            class="mt-6 p-4 bg-white dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h4 class="mb-4 text-base font-bold text-gray-900 dark:text-white">Total Pembayaran</h4>

                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label class="block mb-4 text-base font-medium text-gray-900 dark:text-white">
                                        Total Nilai
                                    </label>
                                    <input type="text" value="Rp {{ number_format($total_amount, 0, ',', '.') }}"
                                        disabled
                                        class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 dark:bg-zinc-700 dark:border-gray-600 dark:text-gray-400 cursor-not-allowed">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="flex justify-end gap-2 p-6 border-t border-gray-200 dark:border-zinc-700 shrink-0 bg-white dark:bg-zinc-900">
                    <button wire:click="$set('showModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700 cursor-pointer">
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

    {{-- DETAIL MODAL --}}
    @if ($showDetail && $selectedPayment)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b dark:border-zinc-700">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Detail Pembayaran Utang</h2>
                        <p class="text-sm text-gray-400 font-mono mt-0.5">{{ $selectedPayment->code }}</p>
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
                                <span class="text-gray-400">No. Pembayaran</span>
                                <span class="font-mono font-medium text-gray-800 dark:text-white">
                                    {{ $selectedPayment->code }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Tanggal</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedPayment->payment_date?->format('d F Y') ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Pemasok</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedPayment->supplier?->name ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Dibuat Oleh</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedPayment->creator?->name ?? '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Rekening Bank</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedPayment->bankAccount?->name ?? ($selectedPayment->bankAccount?->bank_name ?? '-') }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Metode</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedPayment->payment_method ?: '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Status</span>
                                <span>
                                    @php
                                        $statusClass = match ($selectedPayment->status) {
                                            'Draft' => 'bg-zinc-600 text-white',
                                            'Posted' => 'bg-green-700 text-white',
                                            default => 'bg-zinc-600 text-white',
                                        };
                                    @endphp

                                    <span class="text-sm px-2.5 py-0.5 rounded {{ $statusClass }}">
                                        {{ $selectedPayment->status }}
                                    </span>
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Catatan</span>
                                <span class="text-gray-800 dark:text-white text-right max-w-xs">
                                    {{ $selectedPayment->note ?: '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border dark:border-zinc-700">
                        <table class="w-full text-sm text-left">
                            <thead
                                class="bg-gray-50 dark:bg-zinc-800 text-gray-500 dark:text-gray-300 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 w-8">No.</th>
                                    <th class="px-4 py-3">PIV No</th>
                                    <th class="px-4 py-3">Faktur Pemasok</th>
                                    <th class="px-4 py-3 text-right">Total Keseluruhan</th>
                                    <th class="px-4 py-3 text-right">Jumlah Dibayar</th>
                                </tr>
                            </thead>

                            <tbody class="dark:bg-zinc-900 divide-y divide-gray-100 dark:divide-zinc-700">
                                @forelse($selectedPayment->details as $i => $detail)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white font-mono">
                                            {{ $detail->purchaseInvoice?->code ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            {{ $detail->purchaseInvoice?->supplier_invoice_number ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            Rp
                                            {{ number_format($detail->purchaseInvoice?->grand_total ?? 0, 0, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            Rp {{ number_format($detail->amount, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                                            Tidak ada detail payment.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end">
                        <div class="space-y-2 text-sm w-full max-w-xs">
                            <div
                                class="flex justify-between font-bold text-base text-gray-800 dark:text-white border-t dark:border-zinc-700 pt-2">
                                <span>Total Pembayaran</span>
                                <span>Rp {{ number_format($selectedPayment->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    @if ($selectedPayment->status === 'Draft')
                        <div class="border-t dark:border-zinc-700 pt-5">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Ubah Status</h4>

                            <button wire:click="confirmPost({{ $selectedPayment->id }})"
                                class="px-4 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium cursor-pointer">
                                Posting Pembayaran
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

    {{-- POST CONFIRM MODAL --}}
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
                        Posting Pembayaran Utang?
                    </h3>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
                    Setelah payment diposting, invoice akan dianggap terbayar sesuai amount. Data payment tidak bisa
                    diedit atau dihapus.
                </p>

                <div class="flex justify-end gap-2">
                    <button wire:click="cancelPost"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-zinc-700 cursor-pointer">
                        Batal
                    </button>

                    <button wire:click="postPayment" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 cursor-pointer">
                        <span wire:loading.remove wire:target="postPayment">
                            Ya, Posting Pembayaran
                        </span>
                        <span wire:loading wire:target="postPayment">
                            Mem-post...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- DELETE CONFIRM MODAL --}}
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

                    <h3 class="text-base font-semibold dark:text-white">Hapus Pembayaran Utang?</h3>
                </div>

                <p class="text-sm text-gray-400 mb-5">
                    Data akan dipindahkan ke tempat sampah. Pembayaran yang sudah diposting tidak bisa dihapus.
                </p>

                <div class="flex justify-end gap-2">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 text-sm rounded-lg border border-gray-600 dark:text-gray-300 hover:bg-zinc-700 cursor-pointer">
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
