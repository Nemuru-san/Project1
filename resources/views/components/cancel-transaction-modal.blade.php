{{-- Modal konfirmasi pembatalan transaksi. `confirm` dan `close` adalah nama method Livewire. --}}
@props(['title', 'confirm', 'close', 'confirmLabel' => 'Ya, Batalkan'])

<div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="mx-4 w-full max-w-sm rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
        <div class="mb-4 flex items-center gap-3">
            <div class="rounded-full bg-red-100 p-2 dark:bg-red-900">
                <svg class="h-5 w-5 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>

            <h3 class="text-base font-semibold dark:text-white">{{ $title }}</h3>
        </div>

        <div class="mb-5 text-sm text-gray-500 dark:text-gray-400">
            {{ $slot }}
        </div>

        <div class="flex justify-end gap-2">
            <button type="button" wire:click="{{ $close }}"
                class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-zinc-700">
                Kembali
            </button>

            <button type="button" wire:click="{{ $confirm }}" wire:loading.attr="disabled"
                class="cursor-pointer rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="{{ $confirm }}">{{ $confirmLabel }}</span>
                <span wire:loading wire:target="{{ $confirm }}">Membatalkan...</span>
            </button>
        </div>
    </div>
</div>
