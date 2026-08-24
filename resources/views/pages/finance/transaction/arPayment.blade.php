<x-layouts::app :title="__('Pembayaran Piutang')">
    <div class="w-full"><x-layouts::page-header title="Pembayaran Piutang"
            description="Catat penerimaan pembayaran atas Pesanan Penjualan yang telah dikonfirmasi." />
        <div class="mt-6 rounded-md bg-zinc-50 p-4 dark:border dark:border-zinc-700 dark:bg-zinc-900">
            <livewire:finance.transaction.ar-payment /></div>
    </div>
</x-layouts::app>
