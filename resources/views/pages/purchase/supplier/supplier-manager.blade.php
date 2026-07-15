<x-layouts::app :title="__('Supplier')">

    <div class="flex flex-col items-center justify-between min-h-full gap-6 w-full">
        <div class="flex flex-col gap-6 w-full">

            {{-- header --}}
            <x-layouts::page-header :title="__('Supplier')"
                description="Kelola data supplier secara terpusat untuk mendukung proses operasional dan pencatatan yang rapi.">
                <x-slot:breadcrumbs>
                    <li class="inline-flex items-center">
                        <a href="#" class="inline-flex items-center text-sm font-medium hover:text-fg-brand">Pembelian
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m9 5 7 7-7 7" />
                            </svg>
                            <a href="#"
                                class="inline-flex items-center text-sm font-medium hover:text-fg-brand">Master</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center space-x-1.5">
                            <svg class="w-3.5 h-3.5 rtl:rotate-180" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m9 5 7 7-7 7" />
                            </svg>
                            <span class="inline-flex items-center text-sm font-medium">Pemasok</span>
                        </div>
                    </li>
                </x-slot:breadcrumbs>
            </x-layouts::page-header>

            {{-- content --}}
            <div
                class="w-full h-full overflow-hidden rounded-md dark:border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4">
                <livewire:supplier.supplier-manager />
            </div>
        </div>
    </div>

</x-layouts::app>
