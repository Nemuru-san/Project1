{{-- Kartu filter standar (mengikuti desain Penjualan → Transaksi). Slot `actions` untuk tombol Tambah, dsb. --}}
@props(['title', 'description' => null])

<section {{ $attributes->merge(['class' => 'my-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900']) }}>
    <div class="flex flex-col gap-4 border-b border-gray-100 px-4 py-4 sm:px-5 lg:flex-row lg:items-center lg:justify-between dark:border-zinc-700">
        <div>
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">{{ $actions }}</div>
        @endisset
    </div>
    <div class="grid gap-4 p-4 sm:grid-cols-2 sm:px-5 xl:flex xl:flex-wrap xl:items-end">
        {{ $slot }}
    </div>
</section>
