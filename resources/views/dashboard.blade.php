<x-layouts::app :title="__('Dashboard')">
    <div class="flex flex-col gap-6 w-full">
        <x-layouts::page-header title="Dashboard"
            description="Ringkasan penjualan, piutang, utang, kas, dan hal yang perlu ditindaklanjuti hari ini.">
            <x-slot:breadcrumbs>
                <li class="text-sm font-medium">Platform</li>
                <li class="flex items-center gap-1.5 text-sm font-medium"><span>/</span><span>Dashboard</span></li>
            </x-slot:breadcrumbs>
        </x-layouts::page-header>
        <livewire:dashboard />
    </div>
</x-layouts::app>
