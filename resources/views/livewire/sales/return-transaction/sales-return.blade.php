<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg=$event.detail.message;toastType=$event.detail.type;setTimeout(()=>toastMsg='',3000)">
    <div x-show="toastMsg" x-cloak :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[70] rounded-lg px-4 py-2 text-sm text-white shadow"><span x-text="toastMsg"></span>
    </div>
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-1 flex-wrap gap-3"><input wire:model.live.debounce.300ms="search" type="search"
                placeholder="Cari nomor retur / surat jalan / pelanggan"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 sm:w-72"><select
                wire:model.live="statusFilter"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800">
                <option value="">Semua status</option>
                <option>Draft</option>
                <option>Confirmed</option>
                <option>Cancelled</option>
            </select><input wire:model.live="dateFrom" type="date" aria-label="Tanggal mulai"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800"><input
                wire:model.live="dateTo" type="date" aria-label="Tanggal akhir"
                class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800"><button
                wire:click="resetFilters" class="cursor-pointer rounded-lg border px-4 py-2 text-sm">Bersihkan</button>
        </div>
        <button wire:click="openCreate" @disabled(!auth()->user()?->hasPermission('sales.return.sales-return'))
            class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">+
            Tambah Retur</button>
    </div>
    <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
        <table class="w-full min-w-[1050px] text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-100 text-xs uppercase dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3">No.</th>
                    <th class="px-4 py-3">Nomor Retur</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Pelanggan</th>
                    <th class="px-4 py-3">Surat Jalan</th>
                    <th class="px-4 py-3">Sales Order</th>
                    <th class="px-4 py-3 text-center">Item</th>
                    <th class="px-4 py-3">Faktur Retur</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse($returns as $i=>$return)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                        <td class="px-4 py-3 text-gray-400">{{ $returns->firstItem() + $i }}</td>
                        <td class="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">
                            {{ $return->return_no }}</td>
                        <td class="px-4 py-3">{{ $return->return_date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $return->customer?->name }}</td>
                        <td class="px-4 py-3 font-mono">{{ $return->deliveryOrder?->delivery_no }}</td>
                        <td class="px-4 py-3 font-mono">{{ $return->salesOrder?->order_no }}</td>
                        <td class="px-4 py-3 text-center">{{ $return->items_count ?? $return->items()->count() }}</td>
                        <td class="px-4 py-3 font-mono">{{ $return->returnInvoice?->credit_note_no ?? '-' }}</td>
                        <td class="px-4 py-3"><span
                                class="rounded-full px-2.5 py-1 text-xs {{ $return->status === 'Confirmed' ? 'bg-green-100 text-green-700' : ($return->status === 'Cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-700') }}">{{ $return->status === 'Confirmed' ? 'Dikonfirmasi' : ($return->status === 'Cancelled' ? 'Dibatalkan' : 'Draf') }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-block" x-data="{ open: false, top: 0, left: 0, toggle(el) { const r = el.getBoundingClientRect();
                                    this.top = r.bottom + 6;
                                    this.left = Math.max(8, r.right - 208);
                                    this.open = !this.open } }"><button @click="toggle($el)"
                                    @click.outside="open=false" class="cursor-pointer p-1 text-gray-500"><svg
                                        class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg></button>
                                <div x-cloak x-show="open" :style="`position:fixed;top:${top}px;left:${left}px`"
                                    class="z-50 w-52 rounded bg-white py-1 text-left shadow dark:bg-gray-700"><button
                                        wire:click="openDetail({{ $return->id }})"
                                        class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600">◉
                                        Rincian Retur</button><a
                                        href="{{ route('sales.return.sales-return.print', $return->id) }}"
                                        target="_blank"
                                        class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600">▣
                                        Cetak Retur</a>
                                    @if ($return->status === 'Draft')
                                        <button wire:click="confirmReturn({{ $return->id }})"
                                            class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-green-600 hover:bg-green-600 hover:text-white">✓
                                            Konfirmasi Retur</button>
                                        @endif @if ($return->status === 'Confirmed' && !$return->returnInvoice)
                                            <a href="{{ route('sales.return.sales-return-invoice', ['return' => $return->id]) }}"
                                                wire:navigate
                                                class="flex items-center gap-2 px-4 py-2 text-sm text-blue-600 hover:bg-blue-600 hover:text-white">+
                                                Buat Faktur Retur</a>
                                            @endif @if (auth()->user()?->isSuperAdmin() && $return->status === 'Draft')
                                                <button wire:click="delete({{ $return->id }})"
                                                    wire:confirm="Hapus retur Draf ini?"
                                                    class="flex w-full cursor-pointer px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white">Hapus</button>
                                            @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty<tr>
                        <td colspan="10" class="px-4 py-10 text-center text-gray-400">Belum ada Retur Penjualan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $returns->links() }}</div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4">
            <div class="my-4 w-full max-w-5xl rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b px-6 py-4 dark:border-zinc-700">
                    <div>
                        <h2 class="text-xl font-bold">Tambah Retur Penjualan</h2>
                        <p class="text-sm text-gray-400">Pilih Surat Jalan yang sudah dikirim.</p>
                    </div><button wire:click="$set('showModal',false)" class="cursor-pointer text-2xl">×</button>
                </div>
                <div class="space-y-5 p-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div><label class="mb-1 block text-sm font-medium">Tanggal Retur</label><input
                                wire:model="returnDate" type="date"
                                class="w-full rounded-lg border px-3 py-2.5 dark:bg-zinc-800"></div>
                        <div><label class="mb-1 block text-sm font-medium">Surat Jalan</label><select
                                wire:model.live="deliveryOrderId"
                                class="w-full rounded-lg border px-3 py-2.5 dark:bg-zinc-800">
                                <option value="">Pilih Surat Jalan</option>
                                @foreach ($deliveryOrders as $delivery)
                                    <option value="{{ $delivery->id }}">{{ $delivery->delivery_no }} —
                                        {{ $delivery->customer?->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-lg border dark:border-zinc-700">
                        <table class="w-full min-w-[850px] text-sm">
                            <thead class="bg-gray-100 text-xs uppercase dark:bg-zinc-800">
                                <tr>
                                    <th class="px-3 py-3 text-left">Produk</th>
                                    <th class="px-3 py-3">Gudang</th>
                                    <th class="px-3 py-3 text-right">Dikirim</th>
                                    <th class="px-3 py-3 text-right">Bisa Diretur</th>
                                    <th class="px-3 py-3 text-right">Qty Retur</th>
                                    <th class="px-3 py-3 text-left">Alasan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y dark:divide-zinc-700">
                                @forelse($items as $i=>$item)
                                    <tr>
                                        <td class="px-3 py-3">
                                            <div class="font-medium">{{ $item['product_name'] }}</div>
                                            <div class="text-xs text-gray-400">{{ $item['product_sku'] }} /
                                                {{ $item['unit_name'] }}</div>
                                        </td>
                                        <td class="px-3 py-3 text-center">{{ $item['warehouse_name'] }}</td>
                                        <td class="px-3 py-3 text-right">
                                            {{ number_format($item['delivered_qty'], 0, ',', '.') }}</td>
                                        <td class="px-3 py-3 text-right">
                                            {{ number_format($item['remaining_qty'], 0, ',', '.') }}</td>
                                        <td class="px-3 py-3"><input wire:model="items.{{ $i }}.qty"
                                                type="number" min="0" max="{{ $item['remaining_qty'] }}"
                                                class="ml-auto block w-24 rounded-lg border px-2 py-2 text-right dark:bg-zinc-800">
                                        </td>
                                        <td class="px-3 py-3"><input wire:model="items.{{ $i }}.reason"
                                                class="w-full rounded-lg border px-2 py-2 dark:bg-zinc-800"
                                                placeholder="Rusak / tidak sesuai"></td>
                                </tr>@empty<tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">Pilih Surat
                                            Jalan terlebih dahulu.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @error('items')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div>
                        <label class="mb-1 block text-sm font-medium">Catatan</label>
                        <textarea wire:model="notes" rows="3" class="w-full rounded-lg border px-3 py-2 dark:bg-zinc-800"></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 border-t px-6 py-4 dark:border-zinc-700"><button
                        wire:click="$set('showModal',false)"
                        class="cursor-pointer rounded-lg border px-4 py-2">Batal</button><button wire:click="save"
                        class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-white">Simpan Draf</button></div>
            </div>
        </div>
    @endif
    @if ($showDetail && $selectedReturn)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/60 p-4">
            <div class="my-4 w-full max-w-4xl rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex justify-between border-b px-6 py-4 dark:border-zinc-700">
                    <div>
                        <h2 class="text-xl font-bold">Detail Retur Penjualan</h2>
                        <p class="font-mono text-sm text-gray-400">{{ $selectedReturn->return_no }}</p>
                    </div><button wire:click="$set('showDetail',false)" class="cursor-pointer text-2xl">×</button>
                </div>
                <div class="space-y-5 p-6">
                    <div class="grid gap-3 text-sm md:grid-cols-3">
                        <div><span class="text-gray-400">Pelanggan</span>
                            <p class="font-medium">{{ $selectedReturn->customer?->name }}</p>
                        </div>
                        <div><span class="text-gray-400">Surat Jalan</span>
                            <p class="font-mono">{{ $selectedReturn->deliveryOrder?->delivery_no }}</p>
                        </div>
                        <div><span class="text-gray-400">Status</span>
                            <p>{{ $selectedReturn->status }}</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-lg border">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100 dark:bg-zinc-800">
                                <tr>
                                    <th class="px-3 py-3 text-left">Produk</th>
                                    <th class="px-3 py-3">Gudang</th>
                                    <th class="px-3 py-3 text-right">Qty</th>
                                    <th class="px-3 py-3 text-right">Nilai</th>
                                    <th class="px-3 py-3 text-left">Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedReturn->items as $item)
                                    <tr class="border-t">
                                        <td class="px-3 py-3">{{ $item->product?->name }}</td>
                                        <td class="px-3 py-3 text-center">{{ $item->warehouse?->name }}</td>
                                        <td class="px-3 py-3 text-right">{{ number_format($item->qty, 0, ',', '.') }}
                                            {{ $item->unit?->name }}</td>
                                        <td class="px-3 py-3 text-right">Rp
                                            {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                                        <td class="px-3 py-3">{{ $item->reason ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if ($showConfirmModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h3 class="text-lg font-semibold">Konfirmasi Retur Penjualan?</h3>
                <p class="mt-2 text-sm text-gray-500">Stok akan langsung ditambah sesuai jumlah barang yang
                    dikembalikan.</p>
                <div class="mt-5 flex justify-end gap-2"><button wire:click="$set('showConfirmModal',false)"
                        class="cursor-pointer rounded-lg border px-4 py-2">Batal</button><button wire:click="confirm"
                        class="cursor-pointer rounded-lg bg-green-600 px-4 py-2 text-white">Ya, Konfirmasi</button>
                </div>
            </div>
        </div>
    @endif
</div>
