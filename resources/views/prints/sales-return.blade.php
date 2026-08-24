<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $return->return_no }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 32px
        }

        h1 {
            font-size: 20px;
            margin: 0 0 20px
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
            background: #eee;
            text-align: left
        }

        .right {
            text-align: right
        }

        @media print {
            button {
                display: none
            }
        }
    </style>
</head>

<body><button onclick="window.print()">Cetak</button>
    <h1>RETUR PENJUALAN</h1>
    <div class="meta">
        <div><b>Nomor:</b> {{ $return->return_no }}</div>
        <div><b>Tanggal:</b> {{ $return->return_date?->format('d/m/Y') }}</div>
        <div><b>Pelanggan:</b> {{ $return->customer?->name }}</div>
        <div><b>Surat Jalan:</b> {{ $return->deliveryOrder?->delivery_no }}</div>
        <div><b>Sales Order:</b> {{ $return->salesOrder?->order_no }}</div>
        <div><b>Status:</b> {{ $return->status }}</div>
    </div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Produk</th>
                <th>Gudang</th>
                <th>Satuan</th>
                <th class="right">Qty</th>
                <th>Alasan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($return->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->product?->name }}</td>
                    <td>{{ $item->warehouse?->name }}</td>
                    <td>{{ $item->unit?->name }}</td>
                    <td class="right">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td>{{ $item->reason }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p><b>Catatan:</b> {{ $return->notes ?: '-' }}</p>
</body>

</html>
