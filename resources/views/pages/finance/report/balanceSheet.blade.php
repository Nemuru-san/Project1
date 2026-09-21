<x-layouts::app :title="__('Neraca')">
    <div class="flex flex-col gap-6 w-full">
        <x-layouts::page-header title="Neraca" description="Posisi aset, kewajiban, dan ekuitas per tanggal tertentu.">
            <x-slot:breadcrumbs>
                <li class="text-sm font-medium">Keuangan</li>
                <li class="flex items-center gap-1.5 text-sm font-medium"><span>/</span><span>Laporan</span></li>
                <li class="flex items-center gap-1.5 text-sm font-medium"><span>/</span><span>Neraca</span></li>
            </x-slot:breadcrumbs>
        </x-layouts::page-header>
        <div class="w-full overflow-hidden rounded-md border bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4">
            <livewire:finance.report.balance-sheet />
        </div>
    </div>
</x-layouts::app>
