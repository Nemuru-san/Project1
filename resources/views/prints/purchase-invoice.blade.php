{{-- resources/views/prints/purchase-invoice.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <title>{{ $invoice->code }}</title>
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
            <div class="title">PURCHASE INVOICE</div>
            <div class="doc-no">No: {{ $invoice->code }}</div>

            <div class="header">
                <div>
                    Supplier : {{ $invoice->supplier?->name ?? '-' }}<br>
                    Tanggal&nbsp; : {{ $invoice->date?->format('d-m-Y') }}<br>
                    PO No&nbsp;&nbsp;&nbsp; : {{ $invoice->purchaseOrder?->code ?? '-' }}
                </div>

                <div>
                    Supplier Inv : {{ $invoice->supplier_invoice_number ?: '-' }}<br>
                    Status&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $invoice->status }}<br>
                    Payment&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : {{ $invoice->payment_status }}
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
                        @foreach ($invoice->items as $i => $item)
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
                                <td class="right">{{ number_format($item->discount, 0, ',', '.') }}</td>
                                <td class="right">{{ number_format($item->total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="divider" style="margin-top: 4px;"></div>

            <table class="summary">
                <tr>
                    <td>SUB TOTAL</td>
                    <td class="right">{{ number_format($invoice->sub_total, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>DISCOUNT</td>
                    <td class="right">{{ number_format($invoice->discount_total ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>PAJAK</td>
                    <td class="right">{{ number_format($invoice->tax_amount ?? 0, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>TOTAL</td>
                    <td class="right">{{ number_format($invoice->grand_total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <div>
                Dibuat Oleh
                <div class="sign"></div>
            </div>
            <div>
                Diperiksa
                <div class="sign"></div>
            </div>
            <div>
                Finance
                <div class="sign"></div>
            </div>
        </div>
    </div>

</body>

</html>
