{{-- resources/views/prints/purchase-order.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <title>{{ $po->code }}</title>
    <style>
        @page {
            size: 210mm 140mm;
            margin: 7mm;
        }

        * {
            box-sizing: border-box;
            font-weight: normal !important;
        }

        html,
        body {
            height: 100%;
        }

        body {
            font-family: "Courier New", monospace;
            font-size: 11px;
            color: #111;
            margin: 0;
            padding: 0;
        }

        .page {
            min-height: calc(140mm - 14mm);
            display: flex;
            flex-direction: column;
        }

        .title {
            text-align: center;
            font-size: 17px;
            letter-spacing: 8px;
            margin-bottom: 1px;
        }

        .po-no {
            text-align: center;
            font-size: 12px;
            border-top: 1px solid #111;
            border-bottom: 1px solid #111;
            width: 230px;
            margin: 0 auto 10px;
            padding: 2px 0;
        }

        .header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 6px;
            line-height: 1.2;
        }

        .divider {
            border-bottom: 1px solid #111;
            margin: 0;
            height: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            border-bottom: 1px solid #111;
            font-weight: normal !important;
            padding: 3px 2px;
            line-height: 1.1;
        }

        td {
            padding: 2px 2px;
            vertical-align: top;
            line-height: 1.15;
        }

        .items-table {
            margin-top: 0;
        }

        .items-table tbody td {
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .product-name {
            white-space: normal;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .summary {
            width: 300px;
            margin-left: auto;
            margin-top: 8px;
        }

        .summary td {
            padding-top: 2px;
            padding-bottom: 2px;
            line-height: 1.1;
        }

        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .items-table {
            flex: 1;
        }

        .items-table tbody {
            height: 100%;
        }

        .items-area {
            flex: 1;
        }

        .footer {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            text-align: center;
            margin-top: auto;
            padding-bottom: 4mm;
        }

        .sign {
            margin-top: 38px;
            border-top: 1px solid #111;
            width: 140px;
            display: inline-block;
        }

        .no-print {
            margin-bottom: 8px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <button class="no-print" onclick="window.print()">Print</button>

    <div class="page">
        <div class="content">
            <div class="title">PURCHASE ORDER</div>
            <div class="po-no">No: {{ $po->code }}</div>

            <div class="header">
                <div>
                    Supplier : {{ $po->supplier?->name ?? '-' }}<br>
                    Tanggal&nbsp; : {{ $po->date->format('d-m-Y') }}<br>
                    Note&nbsp;&nbsp;&nbsp;&nbsp; : {{ $po->purchase_note ?: '-' }}
                </div>

                <div>
                    Dibuat Oleh : {{ $po->user?->name ?? '-' }}<br>
                    Status&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $po->status }}<br>
                    Pajak&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $po->tax ? 'PPN 11%' : '-' }}
                </div>
            </div>

            <div class="divider"></div>


            <div class="items-area">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="35">NO</th>
                            <th>NAMA BARANG</th>
                            <th width="105">BANYAKNYA</th>
                            <th width="95">HARGA</th>
                            <th width="80">DISC</th>
                            <th width="110">JUMLAH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($po->items as $i => $item)
                            <tr>
                                <td class="center">{{ $i + 1 }}</td>
                                <td class="product-name">
                                    {{ $item->product?->name ?? '-' }}
                                </td>
                                <td class="right">
                                    {{ number_format($item->qty, 0, ',', '.') }}
                                    {{ $item->unit?->name ?? '' }}
                                </td>
                                <td class="right">{{ number_format($item->price, 0, ',', '.') }}</td>
                                <td class="right">{{ number_format($item->disc, 0, ',', '.') }}</td>
                                <td class="right">{{ number_format($item->total_harga, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divider" style="margin-top: 4px;"></div>

            @php
                $poGross = $po->items->sum(fn($item) => (int) $item->qty * (int) $item->price);
                $poDiscount = $po->items->sum('disc');
                $poAfterDiscount = max(0, $poGross - $poDiscount);
                $poPpn = $po->tax ? (int) round($poAfterDiscount * 0.11) : 0;
                $poNett = $poAfterDiscount + $poPpn;
            @endphp

            <table class="summary">
                <tr>
                    <td>SUB TOTAL</td>
                    <td class="right">{{ number_format($poGross, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>DISCOUNT</td>
                    <td class="right">{{ number_format($poDiscount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>PAJAK</td>
                    <td class="right">{{ number_format($poPpn, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>TOTAL</td>
                    <td class="right">{{ number_format($poNett, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <div>
                Dibuat Oleh
                <div class="sign"></div>
            </div>
            <div>
                Disetujui
                <div class="sign"></div>
            </div>
            <div>
                Supplier
                <div class="sign"></div>
            </div>
        </div>
    </div>

</body>

</html>
