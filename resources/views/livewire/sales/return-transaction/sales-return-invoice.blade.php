<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg=$event.detail.message;toastType=$event.detail.type;setTimeout(()=>toastMsg='',3000)">
    <div x-show="toastMsg" x-cloak :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[70] rounded-lg px-4 py-2 text-sm text-white shadow"><span x-text="toastMsg"></span>
    </div>
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-1 flex-wrap gap-3"><input wire:model.live.debounce.300ms="search" type="search"
                placeholder="Cari faktur retur / pelanggan"
                class="w-full rounded-lg border px-3 py-2.5 text-sm dark:bg-zinc-800 sm:w-72"><select
                wire:model.live="statusFilter" class="rounded-lg border px-3 py-2.5 text-sm dark:bg-zinc-800">
                <option value="">Semua status</option>
                <option>Draft</option>
                <option>Posted</option>
            </select><input wire:model.live="dateFrom" type="date"
                class="rounded-lg border px-3 py-2.5 text-sm dark:bg-zinc-800"><input wire:model.live="dateTo"
                type="date" class="rounded-lg border px-3 py-2.5 text-sm dark:bg-zinc-800"><button
                wire:click="resetFilters" class="cursor-pointer rounded-lg border px-4 py-2">Bersihkan</button></div>
        <button wire:click="openCreate"
            class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white">+ Tambah Faktur
            Retur</button>
    </div>
    <div class="mt-5 overflow-x-auto rounded-xl border">
        <table class="w-full min-w-[1050px] text-left text-sm">
            <thead class="bg-gray-100 text-xs uppercase dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3">No.</th>
                    <th class="px-4 py-3">Nomor Kredit</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Retur Penjualan</th>
                    <th class="px-4 py-3">Faktur Penjualan</th>
                    <th class="px-4 py-3">Pelanggan</th>
                    <th class="px-4 py-3 text-right">Subtotal</th>
                    <th class="px-4 py-3 text-right">PPN</th>
                    <th class="px-4 py-3 text-right">Total Kredit</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-zinc-700">
                @forelse($invoices as $i=>$invoice)
                    <tr>
                        <td class="px-4 py-3">{{ $invoices->firstItem() + $i }}</td>
                        <td class="px-4 py-3 font-mono font-medium">{{ $invoice->credit_note_no }}</td>
                        <td class="px-4 py-3">{{ $invoice->invoice_date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 font-mono">{{ $invoice->salesReturn?->return_no }}</td>
                        <td class="px-4 py-3 font-mono">{{ $invoice->salesInvoice?->invoice_no }}</td>
                        <td class="px-4 py-3">{{ $invoice->customer?->name }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold">Rp
                            {{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                        <td class="px-4 py-3"><span
                                class="rounded-full px-2.5 py-1 text-xs {{ $invoice->status === 'Posted' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">{{ $invoice->status === 'Posted' ? 'Diposting' : 'Draf' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-block" x-data="{ open: false, top: 0, left: 0, toggle(el) { const r = el.getBoundingClientRect();
                                    this.top = r.bottom + 6;
                                    this.left = Math.max(8, r.right - 208);
                                    this.open = !this.open } }"><button @click="toggle($el)"
                                    @click.outside="open=false" class="cursor-pointer p-1"><svg class="h-5 w-5"
                                        fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg></button>
                                <div x-cloak x-show="open" :style="`position:fixed;top:${top}px;left:${left}px`"
                                    class="z-50 w-52 rounded bg-white py-1 text-left shadow dark:bg-gray-700"><button
                                        wire:click="openDetail({{ $invoice->id }})"
                                        class="w-full cursor-pointer px-4 py-2 text-left text-sm hover:bg-gray-100">◉
                                        Rincian Faktur</button><a
                                        href="{{ route('sales.return.sales-return-invoice.print', $invoice->id) }}"
                                        target="_blank" class="block px-4 py-2 text-sm hover:bg-gray-100">▣ Cetak Faktur
                                        Retur</a>
                                    @if ($invoice->status === 'Draft')
                                        <button wire:click="confirmPost({{ $invoice->id }})"
                                            class="w-full cursor-pointer px-4 py-2 text-left text-sm text-green-600 hover:bg-green-600 hover:text-white">✓
                                            Posting Faktur</button>
                                        @endif @if (auth()->user()?->isSuperAdmin() && $invoice->status === 'Draft')
                                            <button wire:click="delete({{ $invoice->id }})"
                                                wire:confirm="Hapus faktur retur ini?"
                                                class="w-full cursor-pointer px-4 py-2 text-left text-sm text-red-600 hover:bg-red-600 hover:text-white">Hapus</button>
                                        @endif
                                </div>
                            </div>
                        </td>
                </tr>@empty<tr>
                        <td colspan="11" class="px-4 py-10 text-center text-gray-400">Belum ada Faktur Retur
                            Penjualan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $invoices->links() }}</div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4">
            <div class="my-4 w-full max-w-3xl rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex justify-between border-b px-6 py-4">
                    <div>
                        <h2 class="text-xl font-bold">Tambah Faktur Retur Penjualan</h2>
                        <p class="text-sm text-gray-400">Satu Retur Penjualan hanya dapat memiliki satu faktur retur.
                        </p>
                    </div><button wire:click="$set('showModal',false)" class="cursor-pointer text-2xl">×</button>
                </div>
                <div class="space-y-5 p-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div><label class="mb-1 block text-sm">Tanggal</label><input wire:model="invoiceDate"
                                type="date" class="w-full rounded-lg border px-3 py-2.5 dark:bg-zinc-800"></div>
                        <div><label class="mb-1 block text-sm">Referensi Pelanggan</label><input
                                wire:model="customerReferenceNo"
                                class="w-full rounded-lg border px-3 py-2.5 dark:bg-zinc-800"></div>
                        <div><label class="mb-1 block text-sm">Retur Penjualan</label><select
                                wire:model.live="salesReturnId"
                                class="w-full rounded-lg border px-3 py-2.5 dark:bg-zinc-800">
                                <option value="">Pilih Retur</option>
                                @foreach ($salesReturns as $return)
                                    <option value="{{ $return->id }}">{{ $return->return_no }} —
                                        {{ $return->customer?->name }}</option>
                                @endforeach
                            </select>
                            @error('salesReturnId')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div><label class="mb-1 block text-sm">Faktur Penjualan</label><select
                                wire:model="salesInvoiceId"
                                class="w-full rounded-lg border px-3 py-2.5 dark:bg-zinc-800">
                                <option value="">Pilih Faktur</option>
                                @foreach ($salesInvoices as $pi)
                                    <option value="{{ $pi->id }}">{{ $pi->invoice_no }} — Sisa Rp
                                        {{ number_format($pi->amount_due, 0, ',', '.') }}</option>
                                @endforeach
                            </select>
                            @error('salesInvoiceId')
                                <p class="text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="rounded-xl border bg-gray-50 p-4 dark:bg-zinc-800">
                        <div class="flex justify-between text-sm"><span>Subtotal Retur</span><strong>Rp
                                {{ number_format($subtotal, 0, ',', '.') }}</strong></div>
                        <div class="mt-2 flex justify-between text-sm"><span>PPN Retur</span><strong>Rp
                                {{ number_format($taxAmount, 0, ',', '.') }}</strong></div>
                        <div class="mt-3 flex justify-between border-t pt-3 text-base"><span>Total
                                Kredit</span><strong>Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong></div>
                    </div>
                    <div><label class="mb-1 block text-sm">Catatan</label>
                        <textarea wire:model="notes" rows="3" class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t px-6 py-4"><button wire:click="$set('showModal',false)"
                        class="cursor-pointer rounded-lg border px-4 py-2">Batal</button><button wire:click="save"
                        class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-white">Simpan Draf</button></div>
            </div>
        </div>
    @endif
    @if ($showDetail && $selectedInvoice)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4">
            <div class="my-4 w-full max-w-4xl rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex justify-between border-b px-6 py-4">
                    <div>
                        <h2 class="text-xl font-bold">Detail Faktur Retur Penjualan</h2>
                        <p class="font-mono text-sm text-gray-400">{{ $selectedInvoice->credit_note_no }}</p>
                    </div><button wire:click="$set('showDetail',false)" class="cursor-pointer text-2xl">×</button>
                </div>
                <div class="space-y-5 p-6">
                    <div class="grid gap-3 md:grid-cols-3">
                        <div><span class="text-sm text-gray-400">Pelanggan</span>
                            <p>{{ $selectedInvoice->customer?->name }}</p>
                        </div>
                        <div><span class="text-sm text-gray-400">Faktur Penjualan</span>
                            <p class="font-mono">{{ $selectedInvoice->salesInvoice?->invoice_no }}</p>
                        </div>
                        <div><span class="text-sm text-gray-400">Status</span>
                            <p>{{ $selectedInvoice->status }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-3 py-3 text-left">Produk</th>
                                    <th class="px-3 py-3 text-right">Qty</th>
                                    <th class="px-3 py-3 text-right">Harga</th>
                                    <th class="px-3 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedInvoice->salesReturn->items as $item)
                                    <tr class="border-t">
                                        <td class="px-3 py-3">{{ $item->product?->name }}</td>
                                        <td class="px-3 py-3 text-right">{{ $item->qty }} {{ $item->unit?->name }}
                                        </td>
                                        <td class="px-3 py-3 text-right">Rp
                                            {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                        <td class="px-3 py-3 text-right">Rp
                                            {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="ml-auto max-w-xs space-y-2 text-sm">
                        <div class="flex justify-between"><span>Subtotal</span><span>Rp
                                {{ number_format($selectedInvoice->subtotal, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between"><span>PPN</span><span>Rp
                                {{ number_format($selectedInvoice->tax_amount, 0, ',', '.') }}</span></div>
                        <div class="flex justify-between border-t pt-2 font-bold"><span>Total Kredit</span><span>Rp
                                {{ number_format($selectedInvoice->grand_total, 0, ',', '.') }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if ($showPostModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h3 class="text-lg font-semibold">Posting Faktur Retur?</h3>
                <p class="mt-2 text-sm text-gray-500">Sisa piutang pelanggan akan dikurangi dan jurnal pembalik dibuat.
                    Dokumen tidak dapat diedit setelah diposting.</p>
                <div class="mt-5 flex justify-end gap-2"><button wire:click="$set('showPostModal',false)"
                        class="cursor-pointer rounded-lg border px-4 py-2">Batal</button><button wire:click="post"
                        class="cursor-pointer rounded-lg bg-green-600 px-4 py-2 text-white">Ya, Posting</button></div>
            </div>
        </div>
    @endif
</div>
