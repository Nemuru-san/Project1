<div x-data="{ toastMsg: '', toastType: '' }"
    x-effect="document.body.style.overflow = ($wire.showModal || $wire.showDeleteModal) ? 'hidden' : ''"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">
    <div x-cloak x-show="toastMsg" x-transition
        :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-[70] rounded-lg px-4 py-2 text-sm text-white shadow-lg">
        <span x-text="toastMsg"></span>
    </div>

    <div class="my-4 flex flex-col gap-3 dark:bg-zinc-900">
        <div class="flex w-full flex-col items-stretch gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <div class="relative w-full sm:w-80">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8A4 4 0 008 4zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="search"
                    class="block w-full rounded-lg border border-gray-600 p-2.5 pl-10 text-sm dark:bg-zinc-800 dark:text-white"
                    placeholder="Cari kode atau keterangan...">
            </div>
            <select wire:model.live="perPage"
                class="w-full rounded-lg border border-gray-600 px-8 py-2.5 text-sm sm:w-auto dark:bg-zinc-800 dark:text-white">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>
            <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-sm dark:text-gray-300">
                <input type="checkbox" wire:model.live="showTrashed" class="h-4 w-4 rounded">
                Tampilkan terhapus
            </label>
            <button wire:click="openCreate" type="button"
                class="order-last inline-flex w-full cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 sm:ml-auto sm:w-auto">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Kode Alamat
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="cursor-pointer px-4 py-3" wire:click="sortBy('code')">Kode Alamat</th>
                    <th class="cursor-pointer px-4 py-3" wire:click="sortBy('description')">Keterangan</th>
                    <th class="px-4 py-3 text-center">Digunakan</th>
                    <th class="cursor-pointer px-4 py-3" wire:click="sortBy('is_active')">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($addressCodes as $addressCode)
                    <tr wire:key="address-code-{{ $addressCode->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800/70 {{ $addressCode->trashed() ? 'opacity-60' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">{{ $addressCode->code }}</td>
                        <td class="px-4 py-3">{{ $addressCode->description ?: '-' }}</td>
                        <td class="px-4 py-3 text-center">{{ $addressCode->usage_count }} alamat</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-1 text-xs {{ $addressCode->trashed() ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : ($addressCode->is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-100 text-gray-600 dark:bg-zinc-700 dark:text-gray-300') }}">
                                {{ $addressCode->trashed() ? 'Terhapus' : ($addressCode->is_active ? 'Aktif' : 'Nonaktif') }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
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
                                <button type="button" @click="toggle($el)" @click.outside="open = false"
                                    aria-label="Buka aksi kode alamat"
                                    class="inline-flex cursor-pointer items-center rounded-lg p-0.5 text-center text-gray-500 hover:text-gray-800 focus:outline-none dark:text-gray-400 dark:hover:text-gray-100">
                                    <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </button>

                                <div x-cloak x-show="open" :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                                    class="z-50 w-44 divide-y divide-gray-100 rounded bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                                    @unless ($addressCode->trashed())
                                        <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button type="button" wire:click="openEdit({{ $addressCode->id }})" @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Ubah
                                                </button>
                                            </li>
                                        </ul>
                                    @endunless

                                    <div class="py-1">
                                        @if ($addressCode->trashed())
                                            <button type="button" wire:click="restore({{ $addressCode->id }})" @click="open = false"
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-green-600 hover:bg-green-600 hover:text-white dark:text-green-400">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                Pulihkan
                                            </button>
                                        @else
                                            <button type="button" wire:click="confirmDelete({{ $addressCode->id }})" @click="open = false" @disabled(! auth()->user()->isSuperAdmin())
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-red-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40 dark:text-gray-200">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">Belum ada kode alamat. Tambahkan kode agar dapat dipilih di Master Pelanggan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $addressCodes->links() }}</div>

    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm">
            <div class="mx-auto flex max-h-[calc(100dvh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-zinc-50 px-8 py-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">{{ $editingId ? 'Ubah Kode Alamat' : 'Tambah Kode Alamat' }}</h3>
                    <button wire:click="$set('showModal', false)" type="button" class="cursor-pointer text-gray-400 hover:text-gray-700 dark:hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="save"
                    x-on:keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
                    class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 touch-pan-y overflow-y-auto px-8 py-6"
                        style="overscroll-behavior: contain; scrollbar-gutter: stable; -webkit-overflow-scrolling: touch;">
                        <div class="grid gap-5 sm:grid-cols-2 sm:gap-x-12 sm:gap-y-6">
                            <div>
                                <label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Kode Alamat <span class="text-red-500">*</span></label>
                                <input wire:model="code" type="text" maxlength="50" autofocus
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 font-mono text-sm uppercase text-gray-900 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"
                                    placeholder="Contoh: GUDANG-JKT">
                                @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-3 block text-base font-medium text-gray-900 dark:text-white">Keterangan</label>
                                <input wire:model="description" type="text" maxlength="255"
                                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-zinc-800 dark:text-white"
                                    placeholder="Contoh: Gudang Jakarta">
                                @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex cursor-pointer items-center gap-2 text-sm sm:col-span-2 dark:text-gray-300">
                                <input wire:model="is_active" type="checkbox" class="h-4 w-4 rounded text-blue-600">
                                Kode dapat dipilih di Master Pelanggan
                            </label>
                        </div>
                    </div>
                    <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <button wire:click="$set('showModal', false)" type="button"
                            class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2 text-sm dark:text-gray-300">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save"
                            class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-zinc-900">
                <h3 class="mb-2 text-lg font-semibold dark:text-white">Hapus Kode Alamat?</h3>
                <p class="mb-6 text-sm text-gray-500">Kode akan dinonaktifkan dari pilihan alamat pelanggan dan dapat dipulihkan kembali.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showDeleteModal', false)"
                        class="cursor-pointer rounded-lg border px-4 py-2 text-sm dark:border-gray-600 dark:text-white">Batal</button>
                    <button type="button" wire:click="delete" wire:loading.attr="disabled" @disabled(! auth()->user()->isSuperAdmin())
                        class="cursor-pointer rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50">Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
