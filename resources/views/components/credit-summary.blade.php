@props(['summary', 'name' => null])

@php
    $rp = fn ($v) => 'Rp '.number_format($v, 0, ',', '.');
    $unlimited = $summary['limit'] === null;
    $remaining = $summary['remaining'];
    $remainingAfter = $unlimited ? null : $remaining - $summary['additional'];
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg border p-3 text-sm '.($summary['exceeded']
    ? 'border-red-300 bg-red-50 text-red-800 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200'
    : 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-zinc-800/60 dark:text-gray-200')]) }}>
    @if ($name)
        <div class="mb-2 font-semibold">{{ $name }}</div>
    @endif
    <div class="grid gap-x-4 gap-y-1 sm:grid-cols-2 {{ $summary['additional'] > 0 ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }}">
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Plafon Kredit</div>
            <div class="font-medium">{{ $unlimited ? 'Tanpa batas' : $rp($summary['limit']) }}</div>
        </div>
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Piutang Berjalan</div>
            <div class="font-medium">{{ $rp($summary['outstanding']) }}</div>
        </div>
        @if ($summary['additional'] > 0)
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Transaksi Ini</div>
                <div class="font-medium">{{ $rp($summary['additional']) }}</div>
            </div>
        @endif
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ $summary['additional'] > 0 ? 'Sisa Plafon Setelahnya' : 'Sisa Plafon' }}
            </div>
            <div class="font-semibold {{ ! $unlimited && ($summary['additional'] > 0 ? $remainingAfter : $remaining) < 0 ? 'text-red-600 dark:text-red-400' : '' }}">
                @if ($unlimited)
                    Tanpa batas
                @else
                    {{ $rp($summary['additional'] > 0 ? $remainingAfter : $remaining) }}
                @endif
            </div>
        </div>
    </div>
    @if ($summary['exceeded'])
        <p class="mt-2 text-xs font-medium">
            Plafon kredit terlampaui — transaksi ini tidak akan bisa dikonfirmasi sebelum ada pembayaran piutang.
        </p>
    @endif
</div>
