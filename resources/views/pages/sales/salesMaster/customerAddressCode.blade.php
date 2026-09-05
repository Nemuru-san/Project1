<x-layouts::app :title="__('Kode Alamat Pelanggan')">
    <div class="flex min-h-full w-full flex-col items-center justify-between gap-6">
        <div class="flex w-full flex-col gap-6">
            <x-layouts::page-header :title="__('Kode Alamat Pelanggan')"
                description="Kelola kode unik untuk setiap alamat pelanggan secara terpisah.">
                <x-slot:breadcrumbs>
                    <li class="inline-flex items-center"><span class="text-sm font-medium">Penjualan</span></li>
                    <li><span class="mx-2 text-gray-400">/</span><span class="text-sm font-medium">Master</span></li>
                    <li aria-current="page"><span class="mx-2 text-gray-400">/</span><span class="text-sm font-medium">Kode Alamat</span></li>
                </x-slot:breadcrumbs>
            </x-layouts::page-header>

            <div class="h-full w-full overflow-hidden rounded-md bg-zinc-50 p-4 dark:border dark:border-zinc-700 dark:bg-zinc-900">
                <livewire:sales.sales-master.customer-address-code />
            </div>
        </div>
    </div>
</x-layouts::app>
