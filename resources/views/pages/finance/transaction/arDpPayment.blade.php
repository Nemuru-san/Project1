<x-layouts::app :title="__('Penerimaan DP Pelanggan')">
    <div class="flex min-h-full w-full flex-col gap-6">
        <x-layouts::page-header :title="__('Penerimaan DP Pelanggan')" description="Catat dan posting uang muka pelanggan untuk Pesanan Awal.">
            <x-slot:breadcrumbs>
                <li class="inline-flex items-center"><a href="#" class="inline-flex items-center text-sm font-medium hover:text-fg-brand">Keuangan</a></li>
                <li><div class="flex items-center space-x-1.5"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" /></svg><a href="#" class="inline-flex items-center text-sm font-medium hover:text-fg-brand">Transaksi</a></div></li>
                <li aria-current="page"><div class="flex items-center space-x-1.5"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" /></svg><span class="inline-flex items-center text-sm font-medium">Penerimaan DP Pelanggan</span></div></li>
            </x-slot:breadcrumbs>
        </x-layouts::page-header>
        <div class="w-full overflow-hidden rounded-md bg-zinc-50 p-4 dark:border dark:border-zinc-700 dark:bg-zinc-900">
            <livewire:finance.transaction.ar-dp-payment />
        </div>
    </div>
</x-layouts::app>
