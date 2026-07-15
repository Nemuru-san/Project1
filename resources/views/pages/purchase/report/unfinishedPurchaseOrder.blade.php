<x-layouts::app :title="__('PO Belum Selesai')">
    <div class="flex flex-col gap-6 w-full">
        <x-layouts::page-header title="PO Belum Selesai"
            description="Pantau pesanan pembelian yang barangnya belum diterima seluruhnya.">
            <x-slot:breadcrumbs>
                <li class="text-sm font-medium">Pembelian</li>
                <li class="flex items-center gap-1.5 text-sm font-medium"><span>/</span><span>Laporan</span></li>
                <li class="flex items-center gap-1.5 text-sm font-medium"><span>/</span><span>PO Belum Selesai</span></li>
            </x-slot:breadcrumbs>
        </x-layouts::page-header>
        <div class="w-full overflow-hidden rounded-md border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4">
            <livewire:purchasing.report.unfinished-purchase-order />
        </div>
    </div>
</x-layouts::app>
