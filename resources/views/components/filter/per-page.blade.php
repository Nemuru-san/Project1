{{-- Dropdown "Tampilkan" + tombol reset (tampil bila $showReset true). --}}
@props(['model' => 'perPage', 'options' => [10, 25, 50], 'showReset' => false, 'resetAction' => 'resetFilters', 'id' => null])
@php $id ??= 'filter-per-page-'.\Illuminate\Support\Str::random(4); @endphp

<div {{ $attributes->merge(['class' => 'flex items-end gap-2']) }}>
    <div class="min-w-0 flex-1 xl:w-32 xl:flex-none">
        <label for="{{ $id }}" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Tampilkan</label>
        <select id="{{ $id }}" wire:model.live="{{ $model }}"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-zinc-800 dark:text-white">
            @foreach ($options as $option)
                <option value="{{ $option }}">{{ $option }} / hal</option>
            @endforeach
        </select>
    </div>
    @if ($showReset)
        <button wire:click="{{ $resetAction }}" type="button" title="Reset filter" aria-label="Reset filter"
            class="mb-0.5 inline-flex h-10 w-10 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-gray-300 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-800 dark:border-zinc-600 dark:text-gray-300 dark:hover:bg-zinc-800 dark:hover:text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.001 8.001 0 01-15.357-2M15 20h4" />
            </svg>
        </button>
    @endif
</div>
