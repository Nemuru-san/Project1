<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg=$event.detail.message;toastType=$event.detail.type;setTimeout(()=>toastMsg='',3500)">
    <div x-cloak x-show="toastMsg" :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[80] rounded-lg px-4 py-2 text-sm text-white"><span x-text="toastMsg"></span></div>

    <x-filter.card title="Filter Stok Opname" description="Hitung fisik stok per gudang. Saat disetujui, selisihnya otomatis dibukukan sebagai penyesuaian stok.">
        <x-slot:actions>
            <x-filter.add-button wire:click="openCreate">Tambah Stok Opname</x-filter.add-button>
        </x-slot:actions>
        <x-filter.search placeholder="Cari nomor stok opname..." />
        <x-filter.select model="statusFilter" label="Status">
            <option value="">Semua status</option>
            <option value="draft">Draf</option>
            <option value="approved">Disetujui</option>
            <option value="cancelled">Dibatalkan</option>
        </x-filter.select>
        <x-filter.date-range />
        <x-filter.per-page :show-reset="filled($search) || filled($statusFilter) || filled($dateFrom) || filled($dateTo)" />
    </x-filter.card>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="px-4 py-3">Nomor</th>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Gudang</th>
                    <th class="px-4 py-3 text-right">Produk Selisih</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($opnames as $opname)
                    <tr wire:key="opname-{{ $opname->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                        <td class="whitespace-nowrap px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $opname->opname_no }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $opname->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $opname->warehouse?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($opname->difference_count, 0, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            @if ($opname->status === 'approved')
                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs text-green-700">Disetujui</span>
                            @elseif ($opname->status === 'cancelled')
                                <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs text-red-700">Dibatalkan</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs text-amber-700">Draf</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="inline-block" x-data="{ open: false, top: 0, left: 0, toggle(el) { const r = el.getBoundingClientRect();
                                    this.top = r.bottom + 6;
                                    this.left = Math.max(8, r.right - 192);
                                    this.open = !this.open } }">
                                <button type="button" @click="toggle($el)" @click.outside="open=false" aria-label="Buka aksi stok opname"
                                    class="cursor-pointer rounded-lg p-0.5 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><svg
                                        class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg></button>
                                <div x-cloak x-show="open" :style="`position:fixed;top:${top}px;left:${left}px`"
                                    class="z-50 w-48 divide-y divide-gray-100 rounded bg-white text-left shadow dark:divide-gray-600 dark:bg-gray-700">
                                    <ul class="whitespace-nowrap py-1 text-sm text-gray-700 dark:text-gray-200">
                                        <li><button type="button" wire:click="openDetail({{ $opname->id }})" @click="open=false"
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Detail</button></li>
                                        @if ($opname->status === 'draft')
                                            <li><button type="button" wire:click="openEdit({{ $opname->id }})" @click="open=false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">Ubah / Lanjut Hitung</button></li>
                                            @if (auth()->user()?->hasPermission('inventory.transaction.stock-opname.approve'))
                                                <li><button type="button" wire:click="confirmApprove({{ $opname->id }})" @click="open=false"
                                                        class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-green-600 hover:bg-green-600 hover:text-white">Setujui</button></li>
                                            @endif
                                        @endif
                                        @if ($opname->status !== 'cancelled' && auth()->user()?->canCancelTransactions())
                                            <li><button type="button" wire:click="confirmCancel({{ $opname->id }})" @click="open=false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-600 hover:text-white">Batalkan</button></li>
                                        @endif
                                    </ul>
                                    @if ($opname->status === 'draft' && auth()->user()?->isSuperAdmin())
                                        <div class="py-1"><button type="button" wire:click="confirmDelete({{ $opname->id }})"
                                                @disabled(!auth()->user()?->isSuperAdmin()) @click="open=false"
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-600 hover:text-white disabled:opacity-40">Hapus Draf</button></div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">Belum ada stok opname.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $opnames->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-hidden bg-black/50 p-4 backdrop-blur-sm">
            <div class="mx-auto flex h-[85vh] max-h-[calc(100dvh-2rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-zinc-50 px-8 py-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">{{ $editingId ? 'Ubah Stok Opname' : 'Tambah Stok Opname' }}</h3>
                    <button wire:click="$set('showModal', false)" type="button" class="cursor-pointer text-gray-400 hover:text-white"><svg class="h-5 w-5" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg></button>
                </div>

                <form wire:submit="save" x-on:keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
                    class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 overflow-y-auto px-8 py-6">
                        <div class="grid gap-5 sm:grid-cols-3">
                            <div><label class="mb-2 block text-sm font-medium dark:text-white">Tanggal</label>
                                <input wire:model="date" type="date" class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                @error('date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div><label class="mb-2 block text-sm font-medium dark:text-white">Gudang</label>
                                <select wire:model.live="warehouseId" @disabled($editingId)
                                    class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                                @error('warehouseId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-end">
                                <button type="button" wire:click="loadProducts" wire:loading.attr="disabled"
                                    class="w-full cursor-pointer rounded-lg bg-zinc-700 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-600 disabled:opacity-50">
                                    {{ $items === [] ? 'Muat Daftar Produk' : 'Muat Ulang Stok Sistem' }}
                                </button>
                            </div>
                            <div class="sm:col-span-3"><label class="mb-2 block text-sm font-medium dark:text-white">Catatan</label>
                                <textarea wire:model="notes" rows="2" class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white"
                                    placeholder="Contoh: opname akhir bulan"></textarea>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <h4 class="font-bold dark:text-white">Hasil Hitung Fisik <span class="text-sm font-normal text-gray-400">(satuan dasar)</span></h4>
                                <input wire:model.live.debounce.300ms="itemSearch" type="search" placeholder="Cari produk..."
                                    class="w-full rounded-lg border border-gray-300 bg-gray-50 p-2 text-sm sm:w-64 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                            </div>
                            @error('items') <p class="mb-2 text-sm text-red-500">{{ $message }}</p> @enderror
                            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-zinc-700">
                                <table class="w-full text-left text-sm dark:text-gray-200">
                                    <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-zinc-900 dark:text-gray-300">
                                        <tr>
                                            <th class="px-4 py-3">Kode</th>
                                            <th class="px-4 py-3">Produk</th>
                                            <th class="px-4 py-3 text-right">Stok Sistem</th>
                                            <th class="w-40 px-4 py-3 text-right">Qty Fisik</th>
                                            <th class="px-4 py-3 text-right">Selisih</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                        @forelse ($visibleItems as $productId => $item)
                                            @php $difference = (int) $item['physical_qty'] - (int) $item['system_qty']; @endphp
                                            <tr wire:key="opname-item-{{ $productId }}" class="{{ $difference !== 0 ? 'bg-amber-50 dark:bg-amber-950/20' : '' }}">
                                                <td class="whitespace-nowrap px-4 py-2 font-mono">{{ $item['sku'] }}</td>
                                                <td class="px-4 py-2">{{ $item['name'] }}</td>
                                                <td class="whitespace-nowrap px-4 py-2 text-right">{{ number_format($item['system_qty'], 0, ',', '.') }} {{ $item['unit'] }}</td>
                                                <td class="px-4 py-2 text-right">
                                                    <input wire:model.live.debounce.400ms="items.{{ $productId }}.physical_qty" type="number" min="0"
                                                        class="w-32 rounded-lg border border-gray-300 bg-white p-1.5 text-right text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
                                                    @error("items.$productId.physical_qty") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                                </td>
                                                <td class="whitespace-nowrap px-4 py-2 text-right font-semibold {{ $difference > 0 ? 'text-green-600' : ($difference < 0 ? 'text-red-600' : 'text-gray-400') }}">
                                                    {{ $difference > 0 ? '+' : '' }}{{ number_format($difference, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">Pilih gudang lalu klik "Muat Daftar Produk".</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <button wire:click="$set('showModal', false)" type="button" class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2 text-sm dark:text-gray-300">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="save">Simpan Draf</span><span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDetailModal && $selectedOpname)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-hidden bg-black/60 p-4 backdrop-blur-sm">
            <div class="flex max-h-[min(85vh,calc(100dvh-2rem))] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-zinc-700">
                    <div>
                        <h3 class="text-lg font-semibold dark:text-white">Detail Stok Opname</h3>
                        <p class="mt-0.5 font-mono text-sm text-gray-400">{{ $selectedOpname->opname_no }}</p>
                    </div>
                    <button wire:click="closeDetail" type="button" class="cursor-pointer text-gray-400 hover:text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg></button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-6">
                    <dl class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <div><dt class="text-gray-400">Tanggal</dt><dd class="font-medium dark:text-white">{{ $selectedOpname->date->format('d/m/Y') }}</dd></div>
                        <div><dt class="text-gray-400">Gudang</dt><dd class="font-medium dark:text-white">{{ $selectedOpname->warehouse?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-400">Dibuat oleh</dt><dd class="font-medium dark:text-white">{{ $selectedOpname->creator?->name ?? '-' }}</dd></div>
                        <div><dt class="text-gray-400">Disetujui</dt><dd class="font-medium dark:text-white">{{ $selectedOpname->approver?->name ?? '-' }} {{ $selectedOpname->approved_at?->format('d/m/Y H:i') }}</dd></div>
                        <div class="col-span-2 md:col-span-4"><dt class="text-gray-400">Catatan</dt><dd class="dark:text-white">{{ $selectedOpname->notes ?: '-' }}</dd></div>
                        @if ($selectedOpname->adjustments->isNotEmpty())
                            <div class="col-span-2 md:col-span-4"><dt class="text-gray-400">Penyesuaian yang dibuat</dt>
                                <dd class="flex flex-wrap gap-1">
                                    @foreach ($selectedOpname->adjustments as $adjustment)
                                        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-mono text-xs text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ $adjustment->adjustment_no }}</span>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    </dl>
                    <p class="mb-2 mt-5 text-sm font-semibold dark:text-white">Produk dengan selisih</p>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-xs uppercase dark:bg-zinc-800">
                                <tr><th class="px-3 py-2 text-left">Produk</th><th class="px-3 py-2 text-right">Sistem</th><th class="px-3 py-2 text-right">Fisik</th><th class="px-3 py-2 text-right">Selisih</th></tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                                @forelse ($selectedOpname->items as $item)
                                    <tr>
                                        <td class="px-3 py-2 dark:text-white">{{ $item->product?->name ?? '-' }} <span class="font-mono text-xs text-gray-400">{{ $item->product?->sku }}</span></td>
                                        <td class="px-3 py-2 text-right">{{ number_format($item->system_qty, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right">{{ number_format($item->physical_qty, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right font-semibold {{ $item->difference > 0 ? 'text-green-600' : 'text-red-600' }}">{{ $item->difference > 0 ? '+' : '' }}{{ number_format($item->difference, 0, ',', '.') }} {{ $item->product?->baseUnit?->name }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-3 py-6 text-center text-gray-400">Tidak ada selisih.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end border-t px-6 py-4 dark:border-zinc-700">
                    <button wire:click="closeDetail" type="button" class="cursor-pointer rounded-lg bg-zinc-700 px-5 py-2 text-sm font-medium text-white hover:bg-zinc-600">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showApproveModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Setujui Stok Opname?</h3>
                <p class="mb-6 text-sm text-gray-400">Selisih dihitung ulang terhadap stok saat ini, lalu dibukukan sebagai Penyesuaian
                    Stok Masuk/Keluar beserta jurnal selisih persediaannya.</p>
                <div class="flex justify-end gap-3"><button wire:click="$set('showApproveModal',false)" class="rounded-lg border px-4 py-2 dark:text-gray-300">Batal</button><button
                        wire:click="approve" wire:loading.attr="disabled" class="rounded-lg bg-green-600 px-4 py-2 text-white disabled:opacity-50">Setujui</button></div>
            </div>
        </div>
    @endif

    @if ($showCancelModal)
        <x-cancel-transaction-modal title="Batalkan Stok Opname?" confirm="cancelOpname" close="closeCancel">
            Jika sudah disetujui, penyesuaian stok yang dibuat dari opname ini ikut dibatalkan sehingga stok kembali seperti sebelum opname.
        </x-cancel-transaction-modal>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-zinc-800">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Hapus Draf Stok Opname?</h3>
                <div class="mt-6 flex justify-end gap-3"><button wire:click="$set('showDeleteModal',false)" class="rounded-lg border px-4 py-2 dark:text-gray-300">Batal</button><button
                        wire:click="delete" @disabled(!auth()->user()?->isSuperAdmin()) class="rounded-lg bg-red-600 px-4 py-2 text-white">Hapus</button></div>
            </div>
        </div>
    @endif
</div>
