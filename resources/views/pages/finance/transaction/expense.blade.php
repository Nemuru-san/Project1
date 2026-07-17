<x-layouts::app :title="__('Pengeluaran')">
    <div class="flex min-h-full w-full flex-col items-center justify-between gap-6">
        <div class="flex w-full flex-col gap-6">
            <x-layouts::page-header :title="__('Pengeluaran')"
                description="Catat pengeluaran operasional dan hasilkan jurnal keuangan secara otomatis.">
                <x-slot:breadcrumbs>
                    <li class="inline-flex items-center text-sm font-medium">Keuangan</li>
                    <li class="inline-flex items-center gap-1.5 text-sm font-medium">
                        <span>/</span><span>Transaksi</span>
                    </li>
                    <li class="inline-flex items-center gap-1.5 text-sm font-medium">
                        <span>/</span><span>Pengeluaran</span>
                    </li>
                </x-slot:breadcrumbs>
            </x-layouts::page-header>

            <div class="h-full w-full overflow-hidden rounded-md bg-zinc-50 p-4 dark:border dark:border-zinc-700 dark:bg-zinc-900">
                <livewire:finance.transaction.expense />
            </div>
        </div>
    </div>
</x-layouts::app>
