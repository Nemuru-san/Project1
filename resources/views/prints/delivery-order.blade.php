<div class="mx-auto max-w-4xl bg-white p-8 text-sm text-gray-900 print:max-w-none print:p-0">
    <style>
        @media print { body { background: white !important; } aside, header, nav { display: none !important; } main { padding: 0 !important; } }
    </style>
    <div class="mb-8 flex items-start justify-between border-b-2 border-gray-900 pb-5">
        <div><h1 class="text-2xl font-bold uppercase">Surat Jalan</h1><p class="mt-1 font-mono text-base">{{ $deliveryOrder->delivery_no }}</p></div>
        <button onclick="window.print()" class="rounded bg-gray-900 px-4 py-2 text-white print:hidden">Cetak</button>
    </div>

    <div class="grid grid-cols-2 gap-8">
        <dl class="space-y-2"><div><dt class="text-xs uppercase text-gray-500">Pesanan Penjualan</dt><dd class="font-semibold">{{ $deliveryOrder->salesOrder?->order_no }}</dd></div><div><dt class="text-xs uppercase text-gray-500">Referensi Pesanan Awal/Kanvas</dt><dd>{{ $deliveryOrder->salesOrder?->preOrder?->pre_order_no ?? $deliveryOrder->salesOrder?->salesCanvas?->canvas_no ?? 'Manual' }}</dd></div><div><dt class="text-xs uppercase text-gray-500">Tanggal Pengiriman</dt><dd>{{ $deliveryOrder->delivery_date->format('d/m/Y') }}</dd></div></dl>
        <dl class="space-y-2"><div><dt class="text-xs uppercase text-gray-500">Pelanggan</dt><dd class="font-semibold">{{ $deliveryOrder->customer?->name }}</dd></div><div><dt class="text-xs uppercase text-gray-500">Alamat Pengiriman</dt><dd>{{ $deliveryOrder->customerAddress?->address ?? '-' }}{{ $deliveryOrder->customerAddress?->city ? ', '.$deliveryOrder->customerAddress->city : '' }}</dd></div></dl>
    </div>

    <table class="mt-8 w-full border-collapse"><thead><tr class="bg-gray-100"><th class="border p-2 text-left">No.</th><th class="border p-2 text-left">Kode</th><th class="border p-2 text-left">Nama Barang</th><th class="border p-2 text-left">Gudang</th><th class="border p-2 text-left">Satuan</th><th class="border p-2 text-right">Qty Dikirim</th></tr></thead><tbody>@foreach($deliveryOrder->items as $index => $item)<tr><td class="border p-2">{{ $index + 1 }}</td><td class="border p-2">{{ $item->product?->sku }}</td><td class="border p-2">{{ $item->product?->name }}</td><td class="border p-2">{{ $item->warehouse?->name }}</td><td class="border p-2">{{ $item->unit?->name }}</td><td class="border p-2 text-right">{{ number_format($item->qty_delivered,0,',','.') }}</td></tr>@endforeach</tbody></table>

    @if($deliveryOrder->notes)<div class="mt-5"><div class="text-xs uppercase text-gray-500">Catatan</div><p>{{ $deliveryOrder->notes }}</p></div>@endif
    <div class="mt-16 grid grid-cols-3 gap-10 text-center"><div><div class="mb-20">Dibuat oleh,</div><div class="border-t pt-2">{{ $deliveryOrder->creator?->name ?? '________________' }}</div></div><div><div class="mb-20">Pengirim,</div><div class="border-t pt-2">________________</div></div><div><div class="mb-20">Penerima,</div><div class="border-t pt-2">________________</div></div></div>
</div>
