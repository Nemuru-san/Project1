@props(['title', 'description' => null])

<div
    class="grid sm:grid-cols-1 lg:grid-cols w-full overflow-hidden rounded-md dark:border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4 items-center justify-between gap-4">
    <div class="flex flex-col gap-2">
        <nav class="flex" aria-label="Breadcrumb">
            <ol
                class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse text-lg font-light text-body-subtle">
                {{ $breadcrumbs ?? '' }}
            </ol>
        </nav>

        <p class="lg:text-4xl my-2 font-bold">{{ $title }}</p>
        @if ($description)
            <p class="font-light text-body-subtle text-lg">{{ $description }}</p>
        @endif
    </div>

    <div class="flex flex-col sm:flex-row items-center justify-end gap-4">
        {{ $actions ?? '' }}
    </div>
</div>
