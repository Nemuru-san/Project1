@props(['model', 'label' => 'Tampilkan Terhapus'])

<label {{ $attributes->merge(['class' => 'flex cursor-pointer items-center gap-2 self-end rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 dark:border-gray-600 dark:bg-zinc-800 dark:text-gray-300']) }}>
    <input type="checkbox" wire:model.live="{{ $model }}"
        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-zinc-800">
    {{ $label }}
</label>
