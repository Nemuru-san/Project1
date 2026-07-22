<div x-data="{ toastMsg: '', toastType: '' }"
    x-effect="document.body.style.overflow = ($wire.showModal || $wire.showDetailModal || $wire.showPostModal || $wire.showDeleteModal) ? 'hidden' : ''"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3500)">
    <div x-cloak x-show="toastMsg" x-transition
        :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[80] rounded-lg px-4 py-2 text-sm text-white shadow-lg" x-text="toastMsg"></div>

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
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari kode, penerima, referensi..."
                    class="block w-full rounded-lg border border-gray-600 p-2.5 pl-10 text-sm placeholder-gray-400 focus:border-primary-500 focus:ring-primary-500 dark:bg-zinc-800 dark:text-white">
            </div>

            <select wire:model.live="statusFilter"
                class="w-full rounded-lg border border-gray-600 px-8 py-2.5 text-sm focus:ring-primary-500 sm:w-auto dark:bg-zinc-800 dark:text-white">
                <option value="">Semua Status</option>
                <option value="Draft">Draf</option>
                <option value="Posted">Diposting</option>
            </select>

            <select wire:model.live="perPage"
                class="w-full rounded-lg border border-gray-600 px-8 py-2.5 text-sm sm:w-auto dark:bg-zinc-800 dark:text-white">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>

            <label
                class="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-lg border border-gray-600 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300">
                <input type="checkbox" wire:model.live="showTrashed"
                    class="h-4 w-4 rounded border-gray-600 text-blue-600 dark:bg-zinc-800">
                Tampilkan Terhapus
            </label>

            <button type="button" wire:click="openCreate"
                class="order-last inline-flex w-full cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 sm:ml-auto sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Pengeluaran
            </button>
        </div>

        <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
            <span class="text-sm font-medium text-gray-600 sm:min-w-28 dark:text-gray-300">Rentang tanggal</span>
            <input wire:model.live="dateFrom" type="date" title="Tanggal mulai" aria-label="Tanggal mulai"
                class="w-full rounded-lg border border-gray-600 px-3 py-2.5 text-sm sm:w-auto dark:bg-zinc-800 dark:text-white">
            <span class="hidden text-gray-400 sm:inline">s.d.</span>
            <input wire:model.live="dateTo" type="date" title="Tanggal akhir" aria-label="Tanggal akhir"
                class="w-full rounded-lg border border-gray-600 px-3 py-2.5 text-sm sm:w-auto dark:bg-zinc-800 dark:text-white">
            <button type="button" wire:click="resetFilters"
                class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-zinc-800">
                Bersihkan Filter
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-zinc-700">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-gray-100 text-xs uppercase text-gray-600 dark:bg-zinc-800 dark:text-gray-300">
                <tr>
                    <th class="px-4 py-3">No.</th>
                    <th class="cursor-pointer px-4 py-3" wire:click="sortBy('code')">Kode</th>
                    <th class="cursor-pointer px-4 py-3" wire:click="sortBy('expense_date')">Tanggal</th>
                    <th class="px-4 py-3">Penerima</th>
                    <th class="px-4 py-3">Rekening</th>
                    <th class="cursor-pointer px-4 py-3 text-right" wire:click="sortBy('total_amount')">Total</th>
                    <th class="cursor-pointer px-4 py-3 text-center" wire:click="sortBy('status')">Status</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($expenses as $expense)
                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/60">
                        <td class="px-4 py-3 dark:text-gray-200">{{ $expenses->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3 font-mono font-medium dark:text-white">{{ $expense->code }}</td>
                        <td class="px-4 py-3 dark:text-gray-200">{{ $expense->expense_date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 dark:text-gray-200">{{ $expense->payee ?: '-' }}</td>
                        <td class="px-4 py-3 dark:text-gray-200">{{ $expense->bankAccount?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-right font-semibold dark:text-white">Rp {{ number_format($expense->total_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-center">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' => $expense->status === 'Draft',
                                'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' => $expense->status === 'Posted',
                            ])>{{ $expense->status === 'Draft' ? 'Draf' : 'Diposting' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div x-data="{ open: false }" class="relative inline-block text-left">
                                <button type="button" @click="open = !open" class="cursor-pointer rounded-lg border border-gray-300 px-3 py-1.5 text-sm dark:border-zinc-600 dark:text-gray-200">Aksi ▾</button>
                                <div x-cloak x-show="open" @click.outside="open = false" x-transition
                                    class="absolute right-0 z-20 mt-1 w-40 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 text-left shadow-xl dark:border-zinc-700 dark:bg-zinc-800">
                                    <button type="button" wire:click="openDetail({{ $expense->id }})" @click="open = false" class="block w-full cursor-pointer px-4 py-2 text-left text-sm hover:bg-gray-100 dark:text-white dark:hover:bg-zinc-700">Rincian</button>
                                    @if ($expense->trashed())
                                        <button type="button" wire:click="restore({{ $expense->id }})" @click="open = false" class="block w-full cursor-pointer px-4 py-2 text-left text-sm text-blue-600 hover:bg-gray-100 dark:hover:bg-zinc-700">Pulihkan</button>
                                    @endif
                                    @if (!$expense->trashed() && $expense->status === 'Draft')
                                        <button type="button" wire:click="openEdit({{ $expense->id }})" @click="open = false" class="block w-full cursor-pointer px-4 py-2 text-left text-sm hover:bg-gray-100 dark:text-white dark:hover:bg-zinc-700">Ubah</button>
                                        <button type="button" wire:click="confirmPost({{ $expense->id }})" @click="open = false" class="block w-full cursor-pointer px-4 py-2 text-left text-sm text-green-600 hover:bg-gray-100 dark:hover:bg-zinc-700">Posting</button>
                                        <button type="button" wire:click="confirmDelete({{ $expense->id }})" @click="open = false" @disabled(! auth()->user()->isSuperAdmin()) class="block w-full cursor-pointer px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-zinc-700">Hapus</button>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-gray-500">Belum ada data pengeluaran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $expenses->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-hidden bg-black/50 p-4 backdrop-blur-sm">
            <div class="mx-auto flex h-[80vh] max-h-[calc(100dvh-2rem)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-800"
                style="height: min(80vh, calc(100dvh - 2rem));">
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-zinc-50 px-6 py-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">{{ $expenseId ? 'Ubah Pengeluaran' : 'Tambah Pengeluaran' }}</h3>
                    <button type="button" wire:click="$set('showModal', false)" class="cursor-pointer text-gray-500 hover:text-gray-800 dark:hover:text-white">✕</button>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto px-6 py-5">
                    <section class="grid gap-4 rounded-xl border border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-3 dark:border-zinc-700">
                        <div>
                            <label class="mb-2 block text-sm font-medium dark:text-white">Kode</label>
                            <input value="{{ $code ?: 'Otomatis' }}" disabled class="w-full cursor-not-allowed rounded-lg border border-gray-300 bg-gray-100 p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-700 dark:text-gray-300">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium dark:text-white">Tanggal <span class="text-red-500">*</span></label>
                            <input wire:model="expense_date" type="date" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            @error('expense_date') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium dark:text-white">Rekening Pembayaran <span class="text-red-500">*</span></label>
                            <select wire:model="bank_account_id" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                <option value="">-- Pilih Rekening --</option>
                                @foreach ($bankAccounts as $bankAccount)
                                    <option value="{{ $bankAccount->id }}">{{ $bankAccount->name }} ({{ $bankAccount->chartOfAccount?->code }})</option>
                                @endforeach
                            </select>
                            @error('bank_account_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium dark:text-white">Penerima <span class="text-xs font-normal text-gray-500">(opsional)</span></label>
                            <input wire:model="payee" type="text" placeholder="Nama penerima/vendor" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium dark:text-white">Referensi <span class="text-xs font-normal text-gray-500">(opsional)</span></label>
                            <input wire:model="reference" type="text" placeholder="Nomor kuitansi/invoice" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium dark:text-white">Catatan <span class="text-xs font-normal text-gray-500">(opsional)</span></label>
                            <input wire:model="note" type="text" placeholder="Catatan transaksi" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                        </div>
                    </section>

                    <section class="rounded-xl border border-gray-200 p-4 dark:border-zinc-700">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h4 class="font-semibold dark:text-white">Rincian Biaya</h4>
                                <p class="text-xs text-gray-500">Pilih hanya akun dengan jenis biaya.</p>
                            </div>
                            <button type="button" wire:click="addDetailRow" class="cursor-pointer rounded-lg bg-blue-600 px-3 py-2 text-sm text-white">+ Tambah Baris</button>
                        </div>
                        @error('detailRows') <p class="mb-3 text-xs text-red-500">{{ $message }}</p> @enderror

                        <div class="space-y-3">
                            @foreach ($detailRows as $index => $row)
                                <div wire:key="expense-row-{{ $index }}" class="grid gap-3 rounded-lg bg-gray-50 p-3 lg:grid-cols-[1.2fr_1.5fr_0.8fr_auto] dark:bg-zinc-900">
                                    <div>
                                        <label class="mb-1 block text-xs text-gray-500">Akun Biaya *</label>
                                        <select wire:model="detailRows.{{ $index }}.chart_of_account_id" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                            <option value="">-- Pilih Akun --</option>
                                            @foreach ($expenseAccounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                        @error("detailRows.$index.chart_of_account_id") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs text-gray-500">Keterangan *</label>
                                        <input wire:model="detailRows.{{ $index }}.description" type="text" placeholder="Contoh: Tagihan listrik Juli" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                        @error("detailRows.$index.description") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs text-gray-500">Nominal (Rp) *</label>
                                        <input wire:model.live.debounce.300ms="detailRows.{{ $index }}.amount" type="number" min="1" placeholder="0" class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                                        @error("detailRows.$index.amount") <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button" wire:click="removeDetailRow({{ $index }})" @disabled(count($detailRows) <= 1)
                                            class="cursor-pointer rounded-lg border border-red-300 px-3 py-2.5 text-sm text-red-600 disabled:cursor-not-allowed disabled:opacity-40">Hapus</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="flex shrink-0 flex-col items-stretch justify-between gap-3 border-t border-gray-200 bg-white px-6 py-4 sm:flex-row sm:items-center dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-base font-semibold dark:text-white">Total: Rp {{ number_format($total_amount, 0, ',', '.') }}</div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="$set('showModal', false)" class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-zinc-600 dark:text-gray-200">Batal</button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="cursor-pointer rounded-lg bg-blue-600 px-5 py-2 text-sm text-white disabled:opacity-50">
                            <span wire:loading.remove wire:target="save">Simpan Draf</span><span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showDetailModal && $selectedExpense)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-hidden bg-black/60 p-4 backdrop-blur-sm">
            <div class="flex max-h-[min(80vh,calc(100dvh-2rem))] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-800"
                style="max-height: min(80vh, calc(100dvh - 2rem));">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-zinc-700">
                    <div><h3 class="font-semibold dark:text-white">Rincian Pengeluaran</h3><p class="font-mono text-sm text-gray-500">{{ $selectedExpense->code }}</p></div>
                    <button type="button" wire:click="$set('showDetailModal', false)" class="cursor-pointer dark:text-white">✕</button>
                </div>
                <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-6">
                    <div class="grid gap-4 rounded-xl border border-gray-200 p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-zinc-700">
                        <div><p class="text-xs text-gray-500">Tanggal</p><p class="font-medium dark:text-white">{{ $selectedExpense->expense_date?->format('d/m/Y') }}</p></div>
                        <div><p class="text-xs text-gray-500">Status</p><p class="font-medium dark:text-white">{{ $selectedExpense->status === 'Draft' ? 'Draf' : 'Diposting' }}</p></div>
                        <div><p class="text-xs text-gray-500">Penerima</p><p class="font-medium dark:text-white">{{ $selectedExpense->payee ?: '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Referensi</p><p class="font-medium dark:text-white">{{ $selectedExpense->reference ?: '-' }}</p></div>
                        <div class="sm:col-span-2"><p class="text-xs text-gray-500">Rekening</p><p class="font-medium dark:text-white">{{ $selectedExpense->bankAccount?->name }} ({{ $selectedExpense->bankAccount?->chartOfAccount?->code }})</p></div>
                        <div><p class="text-xs text-gray-500">Jurnal</p><p class="font-mono font-medium dark:text-white">{{ $selectedExpense->journalEntry?->code ?? '-' }}</p></div>
                        <div><p class="text-xs text-gray-500">Total</p><p class="font-semibold dark:text-white">Rp {{ number_format($selectedExpense->total_amount, 0, ',', '.') }}</p></div>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
                        <table class="w-full min-w-[600px] text-sm"><thead class="bg-gray-100 dark:bg-zinc-900 dark:text-gray-200"><tr><th class="px-4 py-3 text-left">Akun</th><th class="px-4 py-3 text-left">Keterangan</th><th class="px-4 py-3 text-right">Nominal</th></tr></thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">@foreach ($selectedExpense->details as $detail)<tr><td class="px-4 py-3 dark:text-white">{{ $detail->chartOfAccount?->code }} - {{ $detail->chartOfAccount?->name }}</td><td class="px-4 py-3 dark:text-gray-200">{{ $detail->description }}</td><td class="px-4 py-3 text-right dark:text-white">Rp {{ number_format($detail->amount, 0, ',', '.') }}</td></tr>@endforeach</tbody>
                        </table>
                    </div>
                    @if ($selectedExpense->note)<div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-zinc-700 dark:text-gray-200"><span class="font-medium">Catatan:</span> {{ $selectedExpense->note }}</div>@endif
                </div>
                <div class="flex justify-end border-t border-gray-200 px-6 py-4 dark:border-zinc-700"><button type="button" wire:click="$set('showDetailModal', false)" class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-zinc-600 dark:text-white">Tutup</button></div>
            </div>
        </div>
    @endif

    @if ($showPostModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Posting Pengeluaran?</h3>
                <p class="mb-6 text-sm text-gray-500">Transaksi akan dikunci dan jurnal otomatis akan dibuat. Tindakan ini tidak dapat dibatalkan dari modul Pengeluaran.</p>
                <div class="flex justify-end gap-2"><button wire:click="$set('showPostModal', false)" class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm dark:text-white">Batal</button><button wire:click="postExpense" class="cursor-pointer rounded-lg bg-green-600 px-4 py-2 text-sm text-white">Ya, Posting</button></div>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Hapus Pengeluaran?</h3>
                <p class="mb-6 text-sm text-gray-500">Pengeluaran berstatus Draf akan dipindahkan ke data terhapus.</p>
                <div class="flex justify-end gap-2"><button wire:click="$set('showDeleteModal', false)" class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm dark:text-white">Batal</button><button wire:click="delete" @disabled(! auth()->user()->isSuperAdmin()) class="cursor-pointer rounded-lg bg-red-600 px-4 py-2 text-sm text-white">Hapus</button></div>
            </div>
        </div>
    @endif
</div>
