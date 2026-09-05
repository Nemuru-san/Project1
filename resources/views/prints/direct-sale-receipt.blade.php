@php
    $autoPrint = $autoPrint ?? false;
    $company = config('company');
    $invoice = $order->salesInvoice;
    $payments = $invoice->payments->where('status', \App\Models\ArPayment::STATUS_POSTED);
    $paymentMethods = $payments->pluck('payment_method')->filter()->unique()->implode(', ');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Struk {{ $invoice->invoice_no }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { width: 80mm; margin: 0; padding: 0; }
        body { color: #000; background: #fff; font-family: "Courier New", monospace; font-size: 11px; line-height: 1.3; }
        .toolbar { width: 76mm; margin: 8px auto; display: flex; justify-content: flex-end; gap: 6px; }
        .toolbar button { border: 1px solid #999; border-radius: 4px; background: #fff; padding: 6px 10px; cursor: pointer; }
        .receipt { width: 76mm; margin: 0 auto; padding: 2mm; }
        .center { text-align: center; }
        .company { font-size: 14px; font-weight: bold; }
        .muted { font-size: 10px; }
        .divider { margin: 5px 0; border-top: 1px dashed #000; }
        .meta div, .total-row { display: flex; justify-content: space-between; gap: 8px; }
        .meta span:last-child { text-align: right; }
        .item { margin: 4px 0; }
        .item-name { overflow-wrap: anywhere; }
        .item-calc { display: flex; justify-content: space-between; gap: 8px; }
        .total-row.grand { margin-top: 3px; padding-top: 3px; border-top: 1px solid #000; font-size: 13px; font-weight: bold; }
        .status { margin-top: 5px; padding: 3px; border: 1px solid #000; text-align: center; font-weight: bold; }
        @media print {
            .toolbar { display: none !important; }
            .receipt { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <main class="receipt">
        <header class="center">
            <div class="company">{{ strtoupper($company['name']) }}</div>
            @if($company['address'])<div>{{ $company['address'] }}</div>@endif
            @if($company['city'])<div>{{ $company['city'] }}</div>@endif
            @if($company['phone'])<div>Telp. {{ $company['phone'] }}</div>@endif
        </header>

        <div class="divider"></div>
        <div class="meta">
            <div><span>Faktur</span><span>{{ $invoice->invoice_no }}</span></div>
            <div><span>SO</span><span>{{ $order->order_no }}</span></div>
            <div><span>Tanggal</span><span>{{ $invoice->invoice_date->format('d/m/Y') }}</span></div>
            <div><span>Kasir</span><span>{{ $order->creator?->name ?? '-' }}</span></div>
            <div><span>Customer</span><span>{{ $order->customer?->name ?? '-' }}</span></div>
        </div>

        <div class="divider"></div>
        @foreach($order->items as $item)
            <div class="item">
                <div class="item-name">{{ $item->product?->name ?? '-' }}</div>
                <div class="item-calc">
                    <span>{{ number_format($item->qty, 0, ',', '.') }} {{ $item->unit?->code ?? $item->unit?->name }} × {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                    <span>{{ number_format($item->line_total, 0, ',', '.') }}</span>
                </div>
                @if($item->discount_amount > 0)
                    <div class="item-calc muted"><span>Diskon</span><span>-{{ number_format($item->discount_amount, 0, ',', '.') }}</span></div>
                @endif
            </div>
        @endforeach

        <div class="divider"></div>
        <div class="total-row"><span>Subtotal</span><span>{{ number_format($order->subtotal, 0, ',', '.') }}</span></div>
        @if($order->discount_total > 0)<div class="total-row"><span>Diskon</span><span>-{{ number_format($order->discount_total, 0, ',', '.') }}</span></div>@endif
        @if($order->tax_amount > 0)<div class="total-row"><span>PPN</span><span>{{ number_format($order->tax_amount, 0, ',', '.') }}</span></div>@endif
        <div class="total-row grand"><span>TOTAL</span><span>Rp {{ number_format($order->grand_total, 0, ',', '.') }}</span></div>
        <div class="total-row"><span>Dibayar{{ $paymentMethods ? ' ('.$paymentMethods.')' : '' }}</span><span>Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</span></div>
        <div class="total-row"><span>Sisa Tagihan</span><span>Rp {{ number_format($invoice->amount_due, 0, ',', '.') }}</span></div>

        <div class="status">{{ $invoice->amount_due > 0 ? 'BELUM LUNAS' : 'LUNAS' }}</div>
        @if($invoice->amount_due > 0)
            <div class="center muted">Jatuh tempo: {{ $invoice->due_date?->format('d/m/Y') ?? '-' }}</div>
        @endif
        <div class="divider"></div>
        <footer class="center">
            <div>Terima kasih</div>
            <div class="muted">{{ now()->format('d/m/Y H:i:s') }}</div>
        </footer>
    </main>

    @if($autoPrint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>
</html>
