<x-layouts::app :title="__('Faktur Penjualan')">
    <div class="flex min-h-full w-full flex-col gap-6">
        <x-layouts::page-header title="Faktur Penjualan" description="Buat faktur dari Pesanan Penjualan dan konfirmasikan sebelum menerima Pembayaran Piutang.">
            <x-slot:breadcrumbs><li class="inline-flex items-center text-sm">Penjualan</li><li class="inline-flex items-center text-sm">/ Transaksi</li><li class="inline-flex items-center text-sm font-medium">/ Faktur Penjualan</li></x-slot:breadcrumbs>
        </x-layouts::page-header>
        <div class="w-full overflow-hidden rounded-md bg-zinc-50 p-4 dark:border dark:border-zinc-700 dark:bg-zinc-900"><livewire:sales.transaction.sales-invoice /></div>
    </div>
</x-layouts::app>