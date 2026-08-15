@php
    $autoPrint = $autoPrint ?? false;

    $company = config('company');
    $customer = $deliveryOrder->customer;
    $address = $deliveryOrder->customerAddress ?? $customer?->primaryAddress;
    $addressLines = collect([
        $address?->address,
        collect([$address?->district, $address?->city])->filter()->implode(', '),
        collect([$address?->province, $address?->postal_code])->filter()->implode(' '),
    ])->map(fn ($line) => trim((string) $line))->filter()->values();

    // Baris kosong agar tinggi form tetap sama saat item sedikit (mengikuti form continuous).
    $minRows = 8;
    $fillerRows = max(0, $minRows - $deliveryOrder->items->count());
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Surat Jalan {{ $deliveryOrder->delivery_no }}</title>
    @include('prints.partials.nota-style')
</head>
<body>
<div class="toolbar">
    <button class="primary" onclick="window.print()">Cetak Surat Jalan</button>
    <button onclick="window.close()">Tutup</button>
</div>

<div class="sheet">
    <div class="tearline">@for($i = 0; $i < 22; $i++)<span></span>@endfor</div>

    <div class="head">
        <div class="company">
            <div class="nm">{{ $company['name'] }}</div>
            @if($company['address'])<div>{{ $company['address'] }}</div>@endif
            @if($company['city'])<div>{{ $company['city'] }}</div>@endif
            @if($company['phone'])<div>Telp. {{ $company['phone'] }}</div>@endif
        </div>
        <div class="to">
            <div>{{ strtoupper($company['city'] ?: '-') }}, {{ $deliveryOrder->delivery_date->format('d-m-y') }}</div>
            <div class="nm">{{ $customer?->name ?? '-' }}</div>
            @foreach($addressLines as $line)<div>{{ $line }}</div>@endforeach
            @if($customer?->phone)<div>{{ $customer->phone }}</div>@endif
        </div>
    </div>

    <div class="title">
        <h1>SURAT JALAN</h1>
        <div class="nota">Nota : {{ $deliveryOrder->delivery_no }}</div>
    </div>

    <div class="meta">
        <div class="cell">NO.REF : <span class="u">{{ $deliveryOrder->salesOrder?->order_no }}</span></div>
        <div class="cell">ASAL : {{ $deliveryOrder->salesOrder?->preOrder?->pre_order_no ?? $deliveryOrder->salesOrder?->salesCanvas?->canvas_no ?? 'MANUAL' }}</div>
        <div class="cell">SALES : {{ $deliveryOrder->salesOrder?->salesman?->code ?? $deliveryOrder->salesOrder?->salesman?->name ?? '-' }}</div>
        <div class="cell">Tgl Kirim {{ $deliveryOrder->delivery_date->format('d-m-y') }}</div>
        <div class="cell">Hal 1 / 1</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:26px">NO</th>
                <th style="width:110px">KODE BARANG</th>
                <th class="l">N A M A &nbsp; B A R A N G</th>
                <th style="width:120px">LKS</th>
                <th style="width:110px">BANYAKNYA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($deliveryOrder->items as $index => $item)
                <tr>
                    <td class="c">{{ $index + 1 }}.</td>
                    <td>{{ $item->product?->sku ?? '-' }}</td>
                    <td>{{ strtoupper($item->product?->name ?? '-') }}</td>
                    <td class="c">{{ strtoupper($item->warehouse?->name ?? '-') }}</td>
                    <td class="r">{{ number_format($item->qty_delivered, 0, ',', '.') }} {{ strtoupper($item->unit?->name ?? '') }}</td>
                </tr>
            @endforeach
            @for($i = 0; $i < $fillerRows; $i++)
                <tr class="filler"><td colspan="5">&nbsp;</td></tr>
            @endfor
            <tr class="sep"><td colspan="5"></td></tr>
        </tbody>
    </table>

    <div class="lower">
        <div class="left">
            @if($deliveryOrder->notes)<div class="bank">Catatan : {{ $deliveryOrder->notes }}</div>@endif
            <div class="signs">
                <div>Diterima oleh,<div class="line"></div></div>
                <div>Diantar oleh,<div class="line"></div></div>
                <div>Disetujui oleh,<div class="line"></div></div>
                <div>Hormat kami,<div class="line"></div></div>
            </div>
        </div>
    </div>

    <div class="foot">
        <div>ID : {{ strtoupper($deliveryOrder->creator?->name ?? '-') }} &nbsp;|&nbsp; {{ $deliveryOrder->delivery_no }}</div>
        <div>Prt. {{ now()->format('d-M-Y H:i') }}</div>
    </div>

    @if($deliveryOrder->status === \App\Models\DeliveryOrder::STATUS_DRAFT)
        <div class="status-draft">** DRAF - BELUM DIKIRIM **</div>
    @elseif($deliveryOrder->status === \App\Models\DeliveryOrder::STATUS_CANCELLED)
        <div class="status-draft">** DIBATALKAN **</div>
    @endif

    <div class="tearline" style="padding-top:8px">@for($i = 0; $i < 22; $i++)<span></span>@endfor</div>
</div>

@if($autoPrint)
    <script>window.addEventListener('load', () => window.print());</script>
@endif
</body>
</html>
