@php
    $autoPrint = $autoPrint ?? false;

    $company = config('company');

    // Baris kosong agar tinggi form tetap sama saat item sedikit (mengikuti form continuous).
    $minRows = 8;
    $fillerRows = max(0, $minRows - $transfer->items->count());
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transfer Stok {{ $transfer->trf_no }}</title>
    @include('prints.partials.nota-style')
</head>
<body>
<div class="toolbar">
    <button class="primary" onclick="window.print()">Cetak Transfer Stok</button>
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
            <div>{{ strtoupper($company['city'] ?: '-') }}, {{ $transfer->date?->format('d-m-y') }}</div>
            <div class="nm">{{ $transfer->warehouseTo?->name ?? '-' }}</div>
            @if($transfer->warehouseTo?->address)<div>{{ $transfer->warehouseTo->address }}</div>@endif
        </div>
    </div>

    <div class="title">
        <h1>TRANSFER STOK</h1>
        <div class="nota">Nota : {{ $transfer->trf_no }}</div>
    </div>

    <div class="meta">
        <div class="cell">GUDANG ASAL : <span class="u">{{ strtoupper($transfer->warehouseFrom?->name ?? '-') }}</span></div>
        <div class="cell">GUDANG TUJUAN : <span class="u">{{ strtoupper($transfer->warehouseTo?->name ?? '-') }}</span></div>
        <div class="cell">Tgl {{ $transfer->date?->format('d-m-y') }}</div>
        <div class="cell">Hal 1 / 1</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:26px">NO</th>
                <th style="width:110px">KODE BARANG</th>
                <th class="l">N A M A &nbsp; B A R A N G</th>
                <th style="width:110px">BANYAKNYA</th>
                <th style="width:80px">KONVERSI</th>
                <th style="width:110px">JML DASAR</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfer->items as $index => $item)
                <tr>
                    <td class="c">{{ $index + 1 }}.</td>
                    <td>{{ $item->product?->sku ?? '-' }}</td>
                    <td>{{ strtoupper($item->product?->name ?? '-') }}</td>
                    <td class="r">{{ number_format((float) $item->qty, 0, ',', '.') }} {{ strtoupper($item->unit?->name ?? '') }}</td>
                    <td class="c">{{ number_format((float) $item->conversion, 0, ',', '.') }}</td>
                    <td class="r">{{ number_format((float) $item->qty * (float) $item->conversion, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            @for($i = 0; $i < $fillerRows; $i++)
                <tr class="filler"><td colspan="6">&nbsp;</td></tr>
            @endfor
            <tr class="sep"><td colspan="6"></td></tr>
        </tbody>
    </table>

    <div class="lower">
        <div class="left">
            @if($transfer->notes)<div class="bank">Catatan : {{ $transfer->notes }}</div>@endif
            <div class="signs">
                <div>Dibuat oleh,<div class="line"></div></div>
                <div>Disetujui oleh,<div class="line"></div></div>
                <div>Gudang asal,<div class="line"></div></div>
                <div>Diterima oleh,<div class="line"></div></div>
            </div>
        </div>
    </div>

    <div class="foot">
        <div>ID : {{ strtoupper($transfer->creator?->name ?? '-') }} &nbsp;|&nbsp; {{ $transfer->trf_no }}</div>
        <div>Prt. {{ now()->format('d-M-Y H:i') }}</div>
    </div>

    @if(strtolower((string) $transfer->status) === 'draft')
        <div class="status-draft">** DRAF - BELUM DISETUJUI **</div>
    @endif

    <div class="tearline" style="padding-top:8px">@for($i = 0; $i < 22; $i++)<span></span>@endfor</div>
</div>

@if($autoPrint)
    <script>window.addEventListener('load', () => window.print());</script>
@endif
</body>
</html>
