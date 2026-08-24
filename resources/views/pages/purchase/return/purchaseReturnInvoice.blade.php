<x-layouts::app :title="__('Faktur Retur Pembelian')">
    <div class="flex w-full flex-col gap-6"><x-layouts::page-header title="Faktur Retur Pembelian"
            description="Catat nota kredit supplier dari Retur Pembelian dan kurangi sisa utang."><x-slot:breadcrumbs>
                <li class="text-sm font-medium">Pembelian</li>
                <li class="text-sm font-medium">/ Retur</li>
                <li class="text-sm font-medium">/ Faktur Retur Pembelian</li>
            </x-slot:breadcrumbs></x-layouts::page-header>
        <div class="w-full overflow-hidden rounded-md border bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <livewire:purchasing.return-transaction.purchase-return-invoice /></div>
    </div>
</x-layouts::app>
