<x-layouts::app :title="__('Retur Pembelian')">
    <div class="flex w-full flex-col gap-6"><x-layouts::page-header title="Retur Pembelian"
            description="Kembalikan barang yang sudah diterima kepada supplier."><x-slot:breadcrumbs>
                <li class="text-sm font-medium">Pembelian</li>
                <li class="text-sm font-medium">/ Retur</li>
                <li class="text-sm font-medium">/ Retur Pembelian</li>
            </x-slot:breadcrumbs></x-layouts::page-header>
        <div class="w-full overflow-hidden rounded-md border bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <livewire:purchasing.return-transaction.purchase-return /></div>
    </div>
</x-layouts::app>
