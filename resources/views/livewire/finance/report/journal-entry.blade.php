<div x-data="{ toastMsg: '', toastType: '' }"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">

    {{-- TOAST --}}
    <div x-show="toastMsg" x-transition :class="toastType === 'success' ? 'bg-green-500' : 'bg-red-500'"
        class="fixed top-5 right-5 z-50 text-white px-4 py-2 rounded shadow-lg text-sm">
        <span x-text="toastMsg"></span>
    </div>

    {{-- FILTER BAR --}}
    <div
        class="flex flex-col md:flex-row items-center justify-between space-y-3 md:space-y-0 md:space-x-4 my-4 dark:bg-zinc-900">
        <p class="dark:text-white text-base font-semibold">Data Journal Entries</p>

        <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
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
                    placeholder="Cari kode, source, deskripsi..." />
            </div>

            <select wire:model.live="statusFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Status</option>
                @foreach ($statusOptions as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>

            <select wire:model.live="sourceFilter"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 focus:ring-primary-500 w-full sm:w-auto">
                <option value="">Semua Source</option>
                @foreach ($sourceOptions as $source)
                    <option value="{{ $source }}">{{ $source }}</option>
                @endforeach
            </select>

            <select wire:model.live="perPage"
                class="dark:bg-zinc-800 border border-gray-600 dark:text-white text-sm rounded-lg px-8 py-2.5 w-full sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>

            <label class="flex items-center gap-2 text-sm dark:text-gray-300 cursor-pointer whitespace-nowrap">
                <input type="checkbox" wire:model.live="showTrashed"
                    class="w-4 h-4 rounded border-gray-600 dark:bg-zinc-800 text-blue-600">
                Tampilkan Terhapus
            </label>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto dark:border-zinc-700 dark:bg-zinc-900">
        <table class="w-full text-base text-left text-gray-500 dark:text-gray-400 mt-4">
            <thead class="text-lg font-bold uppercase bg-gray-50 dark:bg-zinc-800 dark:text-white">
                <tr>
                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('code')">
                        <div class="flex items-center gap-1">
                            Journal No
                            @if ($sortField === 'code')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4 cursor-pointer select-none" wire:click="sortBy('date')">
                        <div class="flex items-center gap-1">
                            Date
                            @if ($sortField === 'date')
                                <span class="text-xs">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </div>
                    </th>

                    <th class="px-4 py-4">Source</th>
                    <th class="px-4 py-4">Description</th>
                    <th class="px-4 py-4 text-center">Lines</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Created By</th>
                    <th class="px-4 py-4">Actions</th>
                </tr>
            </thead>

            <tbody class="dark:bg-zinc-950 text-base">
                @forelse ($journals as $journal)
                    <tr
                        class="border-b dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-zinc-800 {{ $journal->trashed() ? 'opacity-60' : '' }}">
                        <td class="px-4 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white font-mono">
                            {{ $journal->code }}
                        </td>

                        <td class="px-4 py-4">
                            {{ $journal->date?->format('d/m/Y') }}
                        </td>

                        <td class="px-4 py-4">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ $journal->source_type ?: '-' }}
                            </div>
                            <div class="text-xs text-gray-400">
                                ID: {{ $journal->source_id ?: '-' }}
                            </div>
                        </td>

                        <td class="px-4 py-4 max-w-md">
                            <div class="truncate">
                                {{ $journal->description ?: '-' }}
                            </div>
                        </td>

                        <td class="px-4 py-4 text-center">
                            {{ $journal->lines_count }}
                        </td>

                        <td class="px-4 py-4">
                            @if ($journal->trashed())
                                <span class="text-sm px-2.5 py-0.5 rounded bg-red-700 text-white">Terhapus</span>
                            @elseif ($journal->status === 'Draft')
                                <span class="text-sm px-2.5 py-0.5 rounded bg-gray-600 text-white">Draft</span>
                            @elseif ($journal->status === 'Posted')
                                <span class="text-sm px-2.5 py-0.5 rounded bg-green-700 text-white">Posted</span>
                            @elseif ($journal->status === 'Cancelled')
                                <span class="text-sm px-2.5 py-0.5 rounded bg-red-700 text-white">Cancelled</span>
                            @else
                                <span
                                    class="text-sm px-2.5 py-0.5 rounded bg-gray-700 text-white">{{ $journal->status }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            {{ $journal->creator?->name ?? '-' }}
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
                                    @if ($journal->trashed())
                                        <div class="px-4 py-2 text-sm text-gray-400">
                                            Data sudah terhapus
                                        </div>
                                    @else
                                        @php
                                            $locked = $journal->status !== 'Draft';
                                        @endphp

                                        <ul class="py-1 text-base text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button wire:click="openDetail({{ $journal->id }})"
                                                    @click="open = false"
                                                    class="flex items-center gap-2 w-full py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white cursor-pointer">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Detail
                                                </button>
                                            </li>
                                        </ul>

                                        {{-- <div class="py-1">
                                            <button
                                                @if (!$locked) wire:click="confirmDelete({{ $journal->id }})" @endif
                                                @click="open = false" @disabled($locked)
                                                class="flex items-center gap-2 w-full py-2 px-4 text-base {{ $locked ? 'opacity-40 cursor-not-allowed text-gray-400 dark:text-gray-500' : 'text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200 dark:hover:bg-red-600 dark:hover:text-white cursor-pointer' }}">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Delete
                                            </button>
                                        </div> --}}
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-400 dark:text-gray-500">
                            Tidak ada data journal entry.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $journals->links() }}
    </div>

    {{-- DETAIL MODAL --}}
    @if ($showDetail && $selectedJournal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm px-4">
            <div
                class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto">

                <div class="flex items-center justify-between px-6 py-4 border-b dark:border-zinc-700">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Detail Journal Entry</h2>
                        <p class="text-sm text-gray-400 font-mono mt-0.5">{{ $selectedJournal->code }}</p>
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
                                <span class="text-gray-400">Journal No</span>
                                <span class="font-mono font-medium text-gray-800 dark:text-white">
                                    {{ $selectedJournal->code }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Tanggal</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedJournal->date?->format('d F Y') ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Source Type</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedJournal->source_type ?: '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Source ID</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedJournal->source_id ?: '-' }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Status</span>
                                <span>
                                    @php
                                        $statusClass = match ($selectedJournal->status) {
                                            'Draft' => 'bg-zinc-600 text-white',
                                            'Posted' => 'bg-green-700 text-white',
                                            'Cancelled' => 'bg-red-700 text-white',
                                            default => 'bg-zinc-600 text-white',
                                        };
                                    @endphp

                                    <span class="text-sm px-2.5 py-0.5 rounded {{ $statusClass }}">
                                        {{ $selectedJournal->status }}
                                    </span>
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Dibuat Oleh</span>
                                <span class="text-gray-800 dark:text-white">
                                    {{ $selectedJournal->creator?->name ?? '-' }}
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Balance</span>
                                <span>
                                    @if ($this->selectedJournalIsBalance)
                                        <span
                                            class="text-sm px-2.5 py-0.5 rounded bg-green-700 text-white">Balance</span>
                                    @else
                                        <span class="text-sm px-2.5 py-0.5 rounded bg-red-700 text-white">Not
                                            Balance</span>
                                    @endif
                                </span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-400">Description</span>
                                <span class="text-gray-800 dark:text-white text-right max-w-xs">
                                    {{ $selectedJournal->description ?: '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-lg border dark:border-zinc-700">
                        <table class="w-full text-sm text-left">
                            <thead
                                class="bg-gray-50 dark:bg-zinc-800 text-gray-500 dark:text-gray-300 uppercase text-xs">
                                <tr>
                                    <th class="px-4 py-3 w-8">No</th>
                                    <th class="px-4 py-3">Account Code</th>
                                    <th class="px-4 py-3">Account Name</th>
                                    <th class="px-4 py-3">Description</th>
                                    <th class="px-4 py-3 text-right">Debit</th>
                                    <th class="px-4 py-3 text-right">Credit</th>
                                </tr>
                            </thead>

                            <tbody class="dark:bg-zinc-900 divide-y divide-gray-100 dark:divide-zinc-700">
                                @forelse($selectedJournal->lines as $i => $line)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white font-mono">
                                            {{ $line->chartOfAccount?->code ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            {{ $line->chartOfAccount?->name ?? '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-gray-800 dark:text-white">
                                            {{ $line->description ?: '-' }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            Rp {{ number_format($line->debit, 0, ',', '.') }}
                                        </td>

                                        <td class="px-4 py-3 text-right text-gray-800 dark:text-white">
                                            Rp {{ number_format($line->credit, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-6 text-center text-gray-400">
                                            Tidak ada journal line.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>

                            <tfoot class="bg-gray-50 dark:bg-zinc-800">
                                <tr>
                                    <td colspan="4"
                                        class="px-4 py-3 text-right font-bold text-gray-800 dark:text-white">
                                        Total
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-800 dark:text-white">
                                        Rp {{ number_format($this->selectedJournalDebitTotal, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-gray-800 dark:text-white">
                                        Rp {{ number_format($this->selectedJournalCreditTotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
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

                    <h3 class="text-base font-semibold dark:text-white">Hapus Journal Entry?</h3>
                </div>

                <p class="text-sm text-gray-400 mb-5">
                    Journal hanya bisa dihapus jika masih Draft. Journal Posted tidak boleh dihapus.
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
