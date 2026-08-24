<x-layouts::app :title="__('SO Belum Selesai')">
    <div class="flex min-h-full w-full flex-col gap-6"><x-layouts::page-header title="SO Belum Selesai"
            description="Pantau Sales Order terkonfirmasi yang belum selesai dikirim."><x-slot:breadcrumbs>
                <li class="text-sm">Penjualan</li>
                <li class="text-sm">/ Laporan</li>
                <li class="text-sm font-medium">/ SO Belum Selesai</li>
            </x-slot:breadcrumbs></x-layouts::page-header>
        <div class="w-full overflow-hidden rounded-md bg-zinc-50 p-4 dark:border dark:border-zinc-700 dark:bg-zinc-900">
            <livewire:sales.report.unfinished-sales-order /></div>
    </div>
</x-layouts::app>
