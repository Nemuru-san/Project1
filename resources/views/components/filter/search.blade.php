@props(['model' => 'search', 'placeholder' => 'Cari...', 'label' => 'Pencarian', 'id' => null])
@php $id ??= 'filter-search-'.\Illuminate\Support\Str::random(6); @endphp

<div {{ $attributes->merge(['class' => 'sm:col-span-2 xl:min-w-[18rem] xl:flex-1']) }}>
    <label for="{{ $id }}" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
    <div class="relative">
        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
        </svg>
        <input id="{{ $id }}" wire:model.live.debounce.300ms="{{ $model }}" type="search" placeholder="{{ $placeholder }}"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pl-10 text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
    </div>
</div>
