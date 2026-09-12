{{-- Input tunggal (mis. type="month"). --}}
@props(['model', 'label', 'type' => 'text', 'id' => null])
@php $id ??= 'filter-'.\Illuminate\Support\Str::slug($model).'-'.\Illuminate\Support\Str::random(4); @endphp

<div {{ $attributes->merge(['class' => 'min-w-0 xl:w-44']) }}>
    <label for="{{ $id }}" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</label>
    <input id="{{ $id }}" wire:model.live="{{ $model }}" type="{{ $type }}"
        class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
</div>
