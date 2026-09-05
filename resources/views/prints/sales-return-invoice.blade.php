<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $invoice->credit_note_no }}</title>
    <style>
        @page {
            size: 16in 9in;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 32px
        }

        h1 {
            font-size: 20px
        }

        .meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 20px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th,
        td {
            border: 1px solid #aaa;
            padding: 8px
        }

        th {
            background: #eee
        }

        .right {
            text-align: right
        }

        .total {
            margin-left: auto;
            width: 300px;
            margin-top: 16px
        }

        .total div {
            display: flex;
            justify-content: space-between;
            padding: 4px
        }

        @media print {
            html,
            body {
                width: 16in;
                height: 9in;
            }

            body {
                margin: 0;
                padding: .18in .35in;
                overflow: hidden;
            }

            table,
            tr,
            .total {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            button {
                display: none
            }
        }
    </style>
</head>

<body><button onclick="window.print()">Cetak</button>
    <h1>FAKTUR RETUR PENJUALAN</h1>
    <div class="meta">
        <div><b>Nomor Kredit:</b> {{ $invoice->credit_note_no }}</div>
        <div><b>Tanggal:</b> {{ $invoice->invoice_date?->format('d/m/Y') }}</div>
        <div><b>Referensi Pelanggan:</b> {{ $invoice->customer_reference_no ?: '-' }}</div>
        <div><b>Pelanggan:</b> {{ $invoice->customer?->name }}</div>
        <div><b>Faktur Penjualan:</b> {{ $invoice->salesInvoice?->invoice_no }}</div>
        <div><b>Retur:</b> {{ $invoice->salesReturn?->return_no }}</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th class="right">Qty</th>
                <th class="right">Harga</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->salesReturn->items as $item)
                <tr>
                    <td>{{ $item->product?->name }}</td>
                    <td class="right">{{ $item->qty }} {{ $item->unit?->name }}</td>
                    <td class="right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="total">
        <div><span>Subtotal</span><b>Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</b></div>
        <div><span>PPN</span><b>Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</b></div>
        <div><span>Total Kredit</span><b>Rp {{ number_format($invoice->grand_total, 0, ',', '.') }}</b></div>
    </div>
</body>

</html>
