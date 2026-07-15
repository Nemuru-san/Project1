<x-layouts::app :title="__('Kartu Stok')">
    <div class="flex flex-col gap-6 w-full">
        <x-layouts::page-header title="Kartu Stok"
            description="Lihat riwayat mutasi dan saldo berjalan stok per produk dan gudang.">
            <x-slot:breadcrumbs>
                <li class="text-sm font-medium">Persediaan</li>
                <li class="flex items-center gap-1.5 text-sm font-medium"><span>/</span><span>Laporan</span></li>
                <li class="flex items-center gap-1.5 text-sm font-medium"><span>/</span><span>Kartu Stok</span></li>
            </x-slot:breadcrumbs>
        </x-layouts::page-header>
        <div class="w-full overflow-hidden rounded-md border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4">
            <livewire:inventory.report.stock-card />
        </div>
    </div>
</x-layouts::app>
