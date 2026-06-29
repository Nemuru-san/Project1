{{-- resources/views/prints/goods-receive.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <title>{{ $gr->code }}</title>
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

        .doc-no {
            text-align: center;
            font-size: 12px;
            border-top: 1px solid #111;
            border-bottom: 1px solid #111;
            width: 260px;
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

        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .items-area {
            flex: 1;
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
            <div class="title">GOODS RECEIVE</div>
            <div class="doc-no">No: {{ $gr->code }}</div>

            <div class="header">
                <div>
                    Supplier : {{ $gr->supplier?->name ?? '-' }}<br>
                    Tanggal&nbsp; : {{ $gr->date?->format('d-m-Y') }}<br>
                    PO No&nbsp;&nbsp;&nbsp; : {{ $gr->purchaseOrder?->code ?? '-' }}
                </div>

                <div>
                    Status&nbsp;&nbsp;&nbsp; : {{ $gr->status }}<br>
                    Note&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $gr->note ?? '-' }}
                </div>
            </div>

            <div class="divider"></div>

            <div class="items-area">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="35">NO</th>
                            <th>NAMA BARANG</th>
                            <th width="110">QTY ORDER</th>
                            <th width="120">QTY RECEIVE</th>
                            <th width="90">UNIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gr->items as $i => $item)
                            <tr>
                                <td class="center">{{ $i + 1 }}</td>
                                <td class="product-name">
                                    {{ $item->product?->name ?? '-' }}
                                </td>
                                <td class="right">
                                    {{ number_format($item->qty_order ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="right">
                                    {{ number_format($item->qty_received ?? ($item->qty_receive ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="center">
                                    {{ $item->unit?->name ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divider" style="margin-top: 4px;"></div>
        </div>

        <div class="footer">
            <div>
                Diterima Oleh
                <div class="sign"></div>
            </div>
            <div>
                Diperiksa
                <div class="sign"></div>
            </div>
            <div>
                Gudang
                <div class="sign"></div>
            </div>
        </div>
    </div>

</body>

</html>
