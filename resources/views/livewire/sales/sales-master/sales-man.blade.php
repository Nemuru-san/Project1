<div x-data="{ toastMsg: '', toastType: '' }"
    x-effect="document.body.style.overflow = ($wire.showModal || $wire.showDeleteModal || $wire.showTargetModal) ? 'hidden' : ''"
    @toast.window="toastMsg = $event.detail.message; toastType = $event.detail.type; setTimeout(() => toastMsg = '', 3000)">
    <div x-cloak x-show="toastMsg" x-transition
        :class="toastType === 'success' ? 'bg-green-600' : 'bg-red-600'"
        class="fixed right-5 top-5 z-50 rounded-lg px-4 py-2 text-sm text-white shadow-lg">
        <span x-text="toastMsg"></span>
    </div>

    <div class="my-4 flex flex-col items-center justify-between gap-3 md:flex-row">
        <h1 class="text-lg font-semibold dark:text-white">Data Tenaga Penjualan</h1>

        <div class="flex w-full flex-col items-center gap-3 sm:flex-row md:w-auto">
            <div class="relative w-full sm:w-72">
                <svg class="pointer-events-none absolute left-3 top-3 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="search"
                    class="block w-full rounded-lg border border-gray-300 p-2.5 pl-10 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white"
                    placeholder="Cari kode, nama, atau email...">
            </div>

            <select wire:model.live="perPage"
                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white sm:w-auto">
                <option value="10">10 / hal</option>
                <option value="25">25 / hal</option>
                <option value="50">50 / hal</option>
            </select>

            <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-sm dark:text-gray-300">
                <input type="checkbox" wire:model.live="showTrashed" class="h-4 w-4 rounded">
                Tampilkan terhapus
            </label>

            <label class="flex w-full items-center gap-2 whitespace-nowrap text-sm dark:text-gray-300 sm:w-auto">
                Bulan Target
                <input type="month" wire:model.live="targetMonth"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
            </label>

            <button wire:click="openCreate"
                class="inline-flex w-full cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 sm:w-auto">
                <span class="text-lg leading-none">+</span> Tambah Salesman
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                <tr>
                    <th class="cursor-pointer px-4 py-3" wire:click="sortBy('code')">Kode</th>
                    <th class="cursor-pointer px-4 py-3" wire:click="sortBy('name')">Nama</th>
                    <th class="px-4 py-3">Login ERP</th>
                    <th class="px-4 py-3 text-right">Target</th>
                    <th class="px-4 py-3 text-right">Realisasi</th>
                    <th class="min-w-40 px-4 py-3">Pencapaian</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                @forelse ($salesmen as $salesman)
                    <tr wire:key="salesman-{{ $salesman->id }}" class="hover:bg-gray-50 dark:hover:bg-zinc-800 {{ $salesman->trashed() ? 'opacity-60' : '' }}">
                        <td class="whitespace-nowrap px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $salesman->code }}</td>
                        <td class="px-4 py-3">{{ $salesman->name }}</td>
                        <td class="px-4 py-3">
                            @if ($salesman->user)
                                <div>{{ $salesman->user->name }}</div>
                                <div class="text-xs text-gray-400">{{ $salesman->user->email }}</div>
                            @else
                                <span class="text-gray-400">Belum terhubung</span>
                            @endif
                        </td>
                        @php
                            $monthlyTarget = (int) ($salesman->monthlyTargets->first()?->target_amount ?? 0);
                            $monthlySales = (int) ($salesman->monthly_sales_total ?? 0);
                            $achievement = $monthlyTarget > 0 ? round(($monthlySales / $monthlyTarget) * 100, 1) : 0;
                        @endphp
                        <td class="whitespace-nowrap px-4 py-3 text-right font-medium">
                            {{ $monthlyTarget > 0 ? 'Rp '.number_format($monthlyTarget, 0, ',', '.') : '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            Rp {{ number_format($monthlySales, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="mb-1 flex items-center justify-between text-xs">
                                <span>{{ $monthlyTarget > 0 ? number_format($achievement, 1, ',', '.').'%' : 'Belum diatur' }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-zinc-700">
                                <div class="h-full rounded-full {{ $achievement >= 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                                    style="width: {{ min(100, $achievement) }}%"></div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($salesman->trashed())
                                <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs text-red-700 dark:bg-red-900/40 dark:text-red-300">Terhapus</span>
                            @elseif ($salesman->is_active)
                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs text-green-700 dark:bg-green-900/40 dark:text-green-300">Aktif</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600 dark:bg-zinc-700 dark:text-gray-300">Nonaktif</span>
                            @endif
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
                                    aria-label="Buka aksi salesman"
                                    class="inline-flex cursor-pointer items-center rounded-lg p-0.5 text-center text-gray-500 hover:text-gray-800 focus:outline-none dark:text-gray-400 dark:hover:text-gray-100">
                                    <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </button>

                                <div x-cloak x-show="open" :style="`position: fixed; top: ${top}px; left: ${left}px;`"
                                    class="z-50 w-44 divide-y divide-gray-100 rounded bg-white shadow dark:divide-gray-600 dark:bg-gray-700">
                                    @unless ($salesman->trashed())
                                        <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <button type="button" wire:click="openTarget({{ $salesman->id }})" @click="open = false"
                                                    class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-emerald-700 hover:bg-emerald-600 hover:text-white dark:text-emerald-300">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6M4 4h16v16H4z" />
                                                    </svg>
                                                    Atur Target
                                                </button>
                                            </li>
                                            <li>
                                                <button type="button" wire:click="openEdit({{ $salesman->id }})" @click="open = false"
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
                                        @if ($salesman->trashed())
                                            <button type="button" wire:click="restore({{ $salesman->id }})" @click="open = false"
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-green-600 hover:bg-green-600 hover:text-white dark:text-green-400">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                Pulihkan
                                            </button>
                                        @else
                                            <button type="button" wire:click="confirmDelete({{ $salesman->id }})" @click="open = false" @disabled(! auth()->user()->isSuperAdmin())
                                                class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-red-600 hover:text-white dark:text-gray-200">
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
                    <tr><td colspan="8" class="px-4 py-10 text-center text-gray-400">Belum ada data salesman.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $salesmen->links() }}</div>

    @if ($showTargetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <div class="mb-5 flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold dark:text-white">Target Bulanan Salesman</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $targetSalesmanName }} · {{ \Carbon\Carbon::createFromFormat('Y-m', $targetMonth)->translatedFormat('F Y') }}</p>
                    </div>
                    <button type="button" wire:click="$set('showTargetModal', false)" class="cursor-pointer text-gray-400 hover:text-gray-700 dark:hover:text-white">✕</button>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium dark:text-white">Target Omzet <span class="text-red-500">*</span></label>
                    <div class="flex rounded-lg border border-gray-300 dark:border-gray-600">
                        <span class="flex items-center bg-gray-100 px-3 text-sm text-gray-500 dark:bg-zinc-700 dark:text-gray-300">Rp</span>
                        <input wire:model="targetAmount" type="number" min="1" step="1000" autofocus
                            class="w-full rounded-r-lg border-0 p-2.5 text-sm focus:ring-blue-500 dark:bg-zinc-700 dark:text-white"
                            placeholder="Contoh: 100000000">
                    </div>
                    @error('targetAmount') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Realisasi dihitung dari faktur penjualan berstatus Confirmed pada bulan target.</p>
                </div>

                <div class="mt-6 flex justify-between gap-2">
                    <button type="button" wire:click="deleteTarget" @disabled($targetAmount <= 0)
                        class="cursor-pointer rounded-lg px-4 py-2 text-sm text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-red-950/30">Hapus Target</button>
                    <div class="flex gap-2">
                        <button type="button" wire:click="$set('showTargetModal', false)" class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-200">Batal</button>
                        <button type="button" wire:click="saveTarget" wire:loading.attr="disabled" wire:target="saveTarget"
                            class="cursor-pointer rounded-lg bg-emerald-600 px-4 py-2 text-sm text-white hover:bg-emerald-700 disabled:opacity-50">Simpan Target</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showModal)
        <div class="fixed inset-0 z-40 flex items-start justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm">
            <div class="mx-auto flex max-h-[calc(100dvh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-zinc-800">
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-zinc-50 px-8 py-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold dark:text-white">{{ $salesmanId ? 'Ubah' : 'Tambah' }} Salesman</h3>
                    <button type="button" wire:click="$set('showModal', false)" class="cursor-pointer text-gray-400 hover:text-gray-700 dark:hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="save" x-on:keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()" class="flex min-h-0 flex-1 flex-col">
                    <div class="min-h-0 flex-1 touch-pan-y overflow-y-auto px-8 py-6"
                        style="overscroll-behavior: contain; scrollbar-gutter: stable; -webkit-overflow-scrolling: touch;">
                        <div class="space-y-5">
                        <div>
                            <label class="mb-1 block text-sm font-medium dark:text-white">Kode Salesman <span class="text-red-500">*</span></label>
                            <input wire:model="code" type="text" placeholder="SM-001"
                                class="w-full rounded-lg border border-gray-300 p-2.5 text-sm uppercase dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                            @error('code') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium dark:text-white">Nama Salesman <span class="text-red-500">*</span></label>
                            <input wire:model="name" type="text" placeholder="Nama salesman"
                                class="w-full rounded-lg border border-gray-300 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                            @error('name') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <h4 class="mb-3 text-sm font-semibold text-blue-900 dark:text-blue-200">Akun Login ERP</h4>

                            <div class="space-y-4">
                                <div>
                                    <label class="mb-1 block text-sm font-medium dark:text-white">Email / Username <span class="text-red-500">*</span></label>
                                    <input wire:model="login" type="text" autocomplete="username" placeholder="salesman@perusahaan.com"
                                        class="w-full rounded-lg border border-gray-300 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                                    @error('login') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium dark:text-white">Password {{ $salesmanId ? '(opsional)' : '' }} <span class="text-red-500">{{ $salesmanId ? '' : '*' }}</span></label>
                                        <input wire:model="password" type="password" autocomplete="new-password" placeholder="Minimal 8 karakter"
                                            class="w-full rounded-lg border border-gray-300 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                                        @error('password') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-sm font-medium dark:text-white">Konfirmasi Password <span class="text-red-500">{{ $salesmanId ? '' : '*' }}</span></label>
                                        <input wire:model="passwordConfirmation" type="password" autocomplete="new-password" placeholder="Ulangi password"
                                            class="w-full rounded-lg border border-gray-300 p-2.5 text-sm dark:border-gray-600 dark:bg-zinc-700 dark:text-white">
                                        @error('passwordConfirmation') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                @if ($salesmanId)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Kosongkan password bila tidak ingin mengubahnya.</p>
                                @else
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Akun otomatis dibuat dengan role Salesman saat data disimpan.</p>
                                @endif
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-center gap-2 text-sm dark:text-white">
                            <input wire:model="isActive" type="checkbox" class="h-4 w-4 rounded">
                            Salesman aktif
                        </label>
                        </div>
                    </div>

                    <div class="flex shrink-0 justify-end gap-2 border-t border-gray-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                        <button type="button" wire:click="$set('showModal', false)" class="cursor-pointer rounded-lg border border-gray-600 px-4 py-2 text-sm dark:text-gray-300">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="save" class="cursor-pointer rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700 disabled:opacity-50">
                            <span wire:loading.remove wire:target="save">Simpan Salesman</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <h3 class="mb-2 font-semibold dark:text-white">Hapus salesman?</h3>
                <p class="mb-5 text-sm text-gray-500 dark:text-gray-400">Profil dan akun login ERP akan dinonaktifkan. Keduanya dapat dipulihkan kembali.</p>
                <div class="flex justify-end gap-2">
                    <button wire:click="$set('showDeleteModal', false)" class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-200">Batal</button>
                    <button wire:click="delete" @disabled(! auth()->user()->isSuperAdmin()) class="cursor-pointer rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Ya, Hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
