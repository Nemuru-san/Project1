<x-layouts::app :title="__('Surat Jalan')">
    @if(request()->integer('print'))
        @php
            $deliveryOrder = \App\Models\DeliveryOrder::with([
                'salesOrder.preOrder', 'salesOrder.salesCanvas', 'customer',
                'customerAddress', 'creator', 'items.product', 'items.warehouse', 'items.unit',
            ])->findOrFail(request()->integer('print'));

            abort_unless(
                auth()->user()?->isSuperAdmin()
                || $deliveryOrder->created_by === auth()->id()
                || ($deliveryOrder->salesOrder?->salesCanvas
                    && $deliveryOrder->salesOrder->salesCanvas->salesman_id === auth()->user()?->salesman()->where('is_active', true)->value('id')),
                403
            );
        @endphp
        @include('prints.delivery-order', ['deliveryOrder' => $deliveryOrder])
    @else
        <div class="flex min-h-full w-full flex-col items-center justify-between gap-6">
            <div class="flex w-full flex-col gap-6">
                <x-layouts::page-header title="Surat Jalan" description="Kelola pengiriman barang berdasarkan Pesanan Penjualan, termasuk pengiriman parsial dalam beberapa Surat Jalan.">
                    <x-slot:breadcrumbs>
                        <li class="inline-flex items-center"><span class="text-sm font-medium">Penjualan</span></li>
                        <li><div class="flex items-center space-x-1.5"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" /></svg><span class="text-sm font-medium">Transaksi</span></div></li>
                        <li aria-current="page"><div class="flex items-center space-x-1.5"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" /></svg><span class="text-sm font-medium">Surat Jalan</span></div></li>
                    </x-slot:breadcrumbs>
                </x-layouts::page-header>

                <div class="h-full w-full overflow-hidden rounded-md bg-zinc-50 p-4 dark:border dark:border-zinc-700 dark:bg-zinc-900">
                    <livewire:sales.transaction.delivery-order />
                </div>
            </div>
        </div>
    @endif
</x-layouts::app>
