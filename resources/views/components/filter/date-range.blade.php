@props(['from' => 'dateFrom', 'to' => 'dateTo', 'label' => 'Rentang tanggal'])

<fieldset {{ $attributes->merge(['class' => 'sm:col-span-2 xl:w-auto']) }}>
    <legend class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</legend>
    <div class="flex items-center gap-2">
        <input wire:model.live="{{ $from }}" type="date" aria-label="Tanggal mulai"
            class="min-w-0 w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
        <span class="shrink-0 text-sm text-gray-400">s.d.</span>
        <input wire:model.live="{{ $to }}" type="date" aria-label="Tanggal akhir"
            class="min-w-0 w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
    </div>
</fieldset>
