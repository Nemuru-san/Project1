@php
    use App\Models\BankAccount;
    use App\Support\Terbilang;

    $autoPrint = $autoPrint ?? false;

    $company = config('company');
    $bank = collect(config('company.bank'))->filter()->isNotEmpty()
        ? (object) config('company.bank')
        : BankAccount::where('is_active', true)->where('account_type', 'bank')->orderBy('id')->first();

    $customer = $invoice->customer;
    $address = $invoice->salesOrder?->customerAddress ?? $customer?->primaryAddress;
    $addressLines = collect([
        $address?->address,
        collect([$address?->district, $address?->city])->filter()->implode(', '),
        collect([$address?->province, $address?->postal_code])->filter()->implode(' '),
    ])->map(fn ($line) => trim((string) $line))->filter()->values();

    $isKontan = ! $invoice->due_date || $invoice->due_date->equalTo($invoice->invoice_date);
    // Nilai yang ditagih pada nota: bruto - diskon + ppn - panjar (konsisten dengan kolom di bawah).
    $netTotal = max(0, $invoice->grand_total - $invoice->dp_amount);
    // Baris kosong agar tinggi form tetap sama saat item sedikit (mengikuti form continuous).
    $minRows = 8;
    $fillerRows = max(0, $minRows - $invoice->items->count());
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    @include('prints.partials.nota-style')
</head>
<body>
<div class="toolbar">
    <button class="primary" onclick="window.print()">Cetak Invoice</button>
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
            @if($company['tax_number'])<div>NPWP {{ $company['tax_number'] }}</div>@endif
        </div>
        <div class="to">
            <div>{{ strtoupper($company['city'] ?: '-') }}, {{ $invoice->invoice_date->format('d-m-y') }}</div>
            <div class="nm">{{ $customer?->name ?? '-' }}</div>
            @foreach($addressLines as $line)<div>{{ $line }}</div>@endforeach
            @if($customer?->phone)<div>{{ $customer->phone }}</div>@endif
        </div>
    </div>

    <div class="title">
        <h1>INVOICE</h1>
        <div class="nota">Nota : {{ $invoice->invoice_no }}</div>
    </div>

    <div class="meta">
        <div class="cell">NO REF : <span class="u">{{ $invoice->salesOrder?->order_no }} / {{ $invoice->deliveryOrders->pluck('delivery_no')->implode(', ') ?: '-' }}</span></div>
        <div class="cell">SALES : {{ $invoice->salesOrder?->salesman?->code ?? $invoice->salesOrder?->salesman?->name ?? '-' }}</div>
        <div class="cell">J.Tempo {{ $invoice->due_date?->format('d-m-y') ?? '-' }}</div>
        <div class="cell">Nota {{ $isKontan ? 'KONTAN' : 'KREDIT' }}</div>
        <div class="cell">Hal 1 / 1</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:26px">NO</th>
                <th class="l">N A M A &nbsp; B A R A N G</th>
                <th style="width:90px">BANYAKNYA</th>
                <th style="width:90px">HARGA</th>
                <th style="width:88px">D I S C O U N T</th>
                <th style="width:110px">JUMLAH</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $index => $item)
                <tr>
                    <td class="c">{{ $index + 1 }}.</td>
                    <td>{{ strtoupper($item->product?->name ?? '-') }}</td>
                    <td class="r">{{ number_format($item->qty, 0, ',', '.') }} {{ strtoupper($item->unit?->name ?? '') }}</td>
                    <td class="r">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($item->discount_amount, 0, ',', '.') }}</td>
                    <td class="r">{{ number_format($item->line_total, 0, ',', '.') }}</td>
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
            <div>Terbilang : #{{ Terbilang::rupiah($netTotal) }}#</div>
            @if($bank)
                <div class="bank">
                    <div>TRANSFER KE REK BANK {{ strtoupper($bank->bank_name ?? $bank->name ?? '-') }}</div>
                    <div>A/C : {{ $bank->account_number ?? '-' }}</div>
                    <div>A/N : {{ strtoupper($bank->account_holder ?? $company['name']) }}</div>
                </div>
            @endif
            @if($invoice->notes)<div class="bank">Catatan : {{ $invoice->notes }}</div>@endif
            <div class="signs">
                <div>Hormat kami,<div class="line"></div></div>
                <div>Diterima oleh,<div class="line"></div></div>
                <div>Supir,<div class="line"></div></div>
            </div>
        </div>

        <div class="totals">
            <div><span>SUB TOTAL</span><span>{{ number_format($invoice->subtotal, 0, ',', '.') }}</span></div>
            <div><span>DISCOUNT</span><span>{{ number_format($invoice->discount_total, 0, ',', '.') }}</span></div>
            @if($invoice->tax_amount > 0)
                <div><span>PPN</span><span>{{ number_format($invoice->tax_amount, 0, ',', '.') }}</span></div>
            @endif
            <div><span>PANJAR</span><span>{{ number_format($invoice->dp_amount, 0, ',', '.') }}</span></div>
            <div class="grand"><span>TOTAL</span><span>{{ number_format($netTotal, 0, ',', '.') }}</span></div>
        </div>
    </div>

    <div class="foot">
        <div>ID : {{ strtoupper($invoice->creator?->name ?? '-') }} &nbsp;|&nbsp; {{ $invoice->invoice_no }}</div>
        <div>Prt. {{ now()->format('d-M-Y H:i') }}</div>
    </div>

    @if($invoice->status !== \App\Models\SalesInvoice::STATUS_CONFIRMED)
        <div class="status-draft">** DRAF - BELUM DIKONFIRMASI **</div>
    @endif

    <div class="tearline" style="padding-top:8px">@for($i = 0; $i < 22; $i++)<span></span>@endfor</div>
</div>

@if($autoPrint)
    <script>window.addEventListener('load', () => window.print());</script>
@endif
</body>
</html>
