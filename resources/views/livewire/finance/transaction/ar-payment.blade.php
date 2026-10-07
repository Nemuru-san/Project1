<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg=$event.detail.message;toastType=$event.detail.type;setTimeout(()=>toastMsg='',3500)">
    <div x-cloak x-show="toastMsg" :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[80] rounded-lg px-4 py-2 text-sm text-white"><span x-text="toastMsg"></span></div>
    <x-filter.card title="Filter Pembayaran Piutang" description="Temukan pembayaran berdasarkan nomor pembayaran atau faktur.">
        <x-slot:actions>
            <x-filter.add-button wire:click="openCreate">Tambah Pembayaran Piutang</x-filter.add-button>
        </x-slot:actions>
        <x-filter.search placeholder="Cari nomor pembayaran atau faktur..." />
        <x-filter.select model="statusFilter" label="Status">
            <option value="">Semua status</option>
            <option value="Draft">Draf</option>
            <option value="Posted">Diposting</option>
            <option value="Cancelled">Dibatalkan</option>
        </x-filter.select>
        <x-filter.per-page :show-reset="filled($search) || filled($statusFilter)" />
    </x-filter.card>

    <div class="overflow-x-auto rounded-xl border dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase dark:bg-zinc-800">
                <tr>
                    <th class="p-3">Nomor</th>
                    <th class="p-3">Tanggal</th>
                    <th class="p-3">Faktur Penjualan</th>
                    <th class="p-3">Pelanggan</th>
                    <th class="p-3 text-right">Nominal</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-zinc-700">
                @forelse($payments as $payment)
                    <tr>
                        <td class="p-3 font-medium">{{ $payment->code }}</td>
                        <td class="p-3">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="p-3">{{ $payment->salesInvoice?->invoice_no }}</td>
                        <td class="p-3">{{ $payment->customer?->name }}</td>
                        <td class="p-3 text-right">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td class="p-3">
                            @if ($payment->status === 'Posted')
                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs text-green-700">Diposting</span>
                            @elseif ($payment->status === 'Cancelled')
                                <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs text-red-700">Dibatalkan</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-700">Draf</span>
                            @endif
                        </td>
                        <td class="p-3 text-center">
                            <div class="inline-block" x-data="{ open: false, top: 0, left: 0, toggle(el) { const r = el.getBoundingClientRect();
                                    this.top = r.bottom + 6;
                                    this.left = Math.max(8, r.right - 176);
                                    this.open = !this.open } }"><button type="button"
                                    @click="toggle($el)" @click.outside="open = false"
                                    aria-label="Buka aksi pembayaran piutang"
                                    class="inline-flex cursor-pointer items-center rounded-lg p-0.5 text-center text-gray-500 hover:text-gray-800 focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"><svg
                                        class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg></button>
                                <div x-cloak x-show="open" :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                                    class="z-50 w-44 divide-y divide-gray-100 rounded bg-white text-left shadow dark:divide-gray-600 dark:bg-gray-700">
                                    <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                        <li><button type="button" wire:click="openDetail({{ $payment->id }})"
                                                @click="open = false"
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><svg
                                                    class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.5 12C3.7 8 7.5 5 12 5s8.3 3 9.5 7c-1.2 4-5 7-9.5 7s-8.3-3-9.5-7z" />
                                                </svg>Detail</button></li>
                                        @if ($payment->status === 'Draft')
                                            <li><button type="button" wire:click="confirmPost({{ $payment->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-blue-700 hover:bg-blue-600 hover:text-white dark:text-blue-300"><svg
                                                        class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0Z" />
                                                    </svg>Posting Pembayaran</button></li>
                                            <li><button type="button" wire:click="openEdit({{ $payment->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600"><svg
                                                        class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.4-9.4a2 2 0 112.8 2.8L11.8 15H9v-2.8l8.6-8.6z" />
                                                    </svg>Ubah</button></li>
                                        @endif
                                        @if ($payment->status !== 'Cancelled' && auth()->user()?->canCancelTransactions())
                                            <li><button type="button" wire:click="confirmCancel({{ $payment->id }})"
                                                    @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-600 hover:text-white dark:text-red-300"><svg
                                                        class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>Batalkan Pembayaran</button></li>
                                        @endif
                                    </ul>
                                    @if ($payment->status === 'Draft' && auth()->user()?->isSuperAdmin())
                                        <div class="py-1"><button type="button" wire:click="confirmDelete({{ $payment->id }})"
                                                @disabled(!auth()->user()?->isSuperAdmin()) @click="open = false"
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white disabled:opacity-40"><svg
                                                    class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7 18 20H6L5 7m4 0V4h6v3M4 7h16" />
                                                </svg>Hapus Draf</button></div>
                                    @endif
                                </div>
                            </div>
                        </td>
                </tr>@empty<tr>
                        <td colspan="7" class="p-10 text-center text-gray-400">Belum ada Pembayaran Piutang.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $payments->links() }}</div>

    @if ($showModal)
        <div
            class="fixed inset-0 z-40 flex items-start justify-center overflow-hidden bg-black/50 p-4 backdrop-blur-sm">
            <div
                class="mx-auto flex h-[80vh] max-h-[calc(100dvh-2rem)] w-full max-w-full flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-800">
                <div
                    class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-zinc-50 px-8 py-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">{{ $editingId ? 'Ubah Pembayaran Piutang' : 'Tambah Pembayaran Piutang' }}</h3>
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
                                    Pembayaran</label><input value="{{ $code }}" readonly
                                    class="block w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-700 dark:text-gray-400">
                            </div>
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Tanggal
                                    Pembayaran</label><input wire:model="paymentDate" type="date"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('paymentDate')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="sm:col-span-2"><label
                                    class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Faktur
                                    Penjualan Dikonfirmasi</label><select wire:model.live="salesInvoiceId"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                    <option value="">-- Pilih Faktur Penjualan --</option>
                                    @foreach ($salesInvoices as $invoice)
                                        <option value="{{ $invoice->id }}">{{ $invoice->invoice_no }} -
                                            {{ $invoice->salesOrder?->order_no }} - {{ $invoice->customer?->name }} -
                                            @if ($invoice->dp_amount > 0)
                                                DP Rp {{ number_format($invoice->dp_amount, 0, ',', '.') }} -
                                            @endif
                                            Sisa Rp {{ number_format($invoice->amount_due, 0, ',', '.') }}</option>
                                    @endforeach
                                </select>
                                @error('salesInvoiceId')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            @if ($selectedInvoice)
                                <div
                                    class="sm:col-span-2 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                                    @if ($selectedInvoice->salesOrder?->pre_order_id)
                                        <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">
                                            <span
                                                class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">Dari
                                                Pre Order</span>
                                            <span
                                                class="ml-1 font-mono">{{ $selectedInvoice->salesOrder?->preOrder?->pre_order_no ?? '-' }}</span>
                                        </p>
                                    @endif
                                    <dl class="grid grid-cols-2 gap-4 text-sm lg:grid-cols-4">
                                        <div>
                                            <dt class="text-gray-400">Total Faktur</dt>
                                            <dd class="font-medium dark:text-white">Rp
                                                {{ number_format($selectedInvoice->grand_total, 0, ',', '.') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-400">DP (Uang Muka)</dt>
                                            <dd class="font-medium text-green-600">- Rp
                                                {{ number_format($selectedInvoice->dp_amount, 0, ',', '.') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-400">Sudah Dibayar</dt>
                                            <dd class="font-medium text-green-600">- Rp
                                                {{ number_format($selectedInvoice->paid_amount, 0, ',', '.') }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-gray-400">Sisa Tagihan</dt>
                                            <dd class="font-bold dark:text-white">Rp
                                                {{ number_format($selectedInvoice->amount_due, 0, ',', '.') }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            @endif
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Rekening
                                    Penerimaan</label><select wire:model="bankAccountId"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach ($bankAccounts as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->display_label }}</option>
                                    @endforeach
                                </select>
                                @error('bankAccountId')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Metode
                                    Pembayaran</label><select wire:model="paymentMethod"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                    <option>Transfer</option>
                                    <option>Tunai</option>
                                    <option>Giro</option>
                                </select></div>
                            <div><label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Nominal
                                    Pembayaran</label><input wire:model="amount" type="number" min="1"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('amount')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div><label
                                    class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Catatan</label>
                                <textarea wire:model="notes" rows="3"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"
                                    placeholder="Masukkan catatan pembayaran..."></textarea>
                                @error('notes')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <button wire:click="$set('showModal', false)" type="button"
                            class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2 text-sm dark:text-gray-300">Batal</button><button
                            type="submit" wire:loading.attr="disabled"
                            class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50"><span
                                wire:loading.remove wire:target="save">Simpan Draf</span><span wire:loading
                                wire:target="save">Menyimpan...</span></button></div>
                </form>
            </div>
        </div>
    @endif
    @if ($showDetailModal && $selectedPayment)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-hidden bg-black/60 p-4 backdrop-blur-sm">
            <div class="flex max-h-[min(80vh,calc(100dvh-2rem))] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-zinc-700">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white">Detail Pembayaran Piutang</h3>
                        <p class="mt-0.5 font-mono text-sm text-gray-400">{{ $selectedPayment->code }}</p>
                    </div>
                    <button wire:click="closeDetail" type="button" class="cursor-pointer text-gray-400 hover:text-white"><svg
                            class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg></button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    @php $detailInvoice = $selectedPayment->salesInvoice; @endphp
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div><dt class="text-gray-400">Tanggal</dt><dd class="font-medium dark:text-white">{{ $selectedPayment->payment_date->format('d/m/Y') }}</dd></div>
                        <div><dt class="text-gray-400">Status</dt><dd class="font-medium dark:text-white">{{ ['Draft' => 'Draf', 'Posted' => 'Diposting', 'Cancelled' => 'Dibatalkan'][$selectedPayment->status] ?? $selectedPayment->status }}</dd></div>
                        <div><dt class="text-gray-400">Pelanggan</dt><dd class="font-medium dark:text-white">{{ $selectedPayment->customer?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-400">Faktur Penjualan</dt><dd class="font-mono font-medium dark:text-white">{{ $detailInvoice?->invoice_no ?? '-' }}</dd></div>
                        <div><dt class="text-gray-400">Rekening</dt><dd class="font-medium dark:text-white">{{ $selectedPayment->bankAccount?->display_label ?? $selectedPayment->bankAccount?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-400">Metode</dt><dd class="font-medium dark:text-white">{{ $selectedPayment->payment_method }}</dd></div>
                        <div><dt class="text-gray-400">Nominal</dt><dd class="text-base font-bold dark:text-white">Rp {{ number_format($selectedPayment->amount, 0, ',', '.') }}</dd></div>
                        <div><dt class="text-gray-400">Dibuat oleh</dt><dd class="font-medium dark:text-white">{{ $selectedPayment->creator?->name ?? '-' }}</dd></div>
                        <div class="col-span-2"><dt class="text-gray-400">Catatan</dt><dd class="dark:text-white">{{ $selectedPayment->notes ?: '-' }}</dd></div>
                    </dl>
                    @if ($detailInvoice)
                        <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                            <p class="mb-2 font-semibold dark:text-white">Posisi faktur saat ini</p>
                            <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                                <div><dt class="text-gray-400">Total</dt><dd class="dark:text-white">Rp {{ number_format($detailInvoice->grand_total, 0, ',', '.') }}</dd></div>
                                <div><dt class="text-gray-400">DP</dt><dd class="text-green-600">Rp {{ number_format($detailInvoice->dp_amount, 0, ',', '.') }}</dd></div>
                                <div><dt class="text-gray-400">Dibayar</dt><dd class="text-green-600">Rp {{ number_format($detailInvoice->paid_amount, 0, ',', '.') }}</dd></div>
                                <div><dt class="text-gray-400">Sisa</dt><dd class="font-bold dark:text-white">Rp {{ number_format($detailInvoice->amount_due, 0, ',', '.') }}</dd></div>
                            </dl>
                        </div>
                    @endif
                </div>
                <div class="flex justify-end border-t px-6 py-4 dark:border-zinc-700">
                    <button wire:click="closeDetail" type="button" class="cursor-pointer rounded-lg bg-zinc-700 px-5 py-2 text-sm font-medium text-white hover:bg-zinc-600">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showCancelModal)
        <x-cancel-transaction-modal title="Batalkan Pembayaran Piutang?" confirm="cancelPayment" close="closeCancel">
            Jika pembayaran sudah diposting, nominalnya dikembalikan ke sisa tagihan faktur dan jurnal
            penerimaannya dibatalkan. Faktur terkait kemudian bisa dibatalkan atau ditagih ulang.
        </x-cancel-transaction-modal>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Hapus Draf Pembayaran Piutang?</h3>
                <p class="mb-6 text-sm text-gray-400">Draf akan dipindahkan ke tempat sampah.</p>
                <div class="flex justify-end gap-3"><button wire:click="$set('showDeleteModal',false)"
                        class="rounded-lg border px-4 py-2 dark:text-gray-300">Batal</button><button wire:click="delete"
                        @disabled(!auth()->user()?->isSuperAdmin())
                        class="rounded-lg bg-red-600 px-4 py-2 text-white">Hapus</button></div>
            </div>
        </div>
    @endif

    @if ($showPostModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <h3 class="mb-2 text-lg font-semibold">Posting Pembayaran Piutang?</h3>
                <p class="mb-6 text-sm text-gray-400">Posting akan mengurangi sisa tagihan dan membuat jurnal
                    penerimaan.</p>
                <div class="flex justify-end gap-3"><button wire:click="$set('showPostModal',false)"
                        class="rounded-lg border px-4 py-2">Batal</button><button wire:click="post"
                        class="rounded-lg bg-green-600 px-4 py-2 text-white">Posting</button></div>
            </div>
        </div>
    @endif
</div>
