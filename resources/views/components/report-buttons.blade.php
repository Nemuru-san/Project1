{{-- Tombol cetak laporan & unduh CSV untuk daftar transaksi; $params = filter aktif di halaman. --}}
@props(['route', 'params' => []])
@php $query = array_filter($params, fn ($value) => filled($value)); @endphp

<div class="flex gap-2">
    <a href="{{ route($route, $query) }}" target="_blank"
        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 sm:flex-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-gray-200 dark:hover:bg-zinc-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V3h12v6M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v7H6v-7z" />
        </svg>Cetak Laporan
    </a>
    <a href="{{ route($route, $query + ['format' => 'csv']) }}"
        class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-green-600 bg-white px-4 py-2.5 text-sm font-medium text-green-700 transition-colors hover:bg-green-50 sm:flex-none dark:bg-zinc-800 dark:text-green-400 dark:hover:bg-zinc-700">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16" />
        </svg>Excel
    </a>
</div>
