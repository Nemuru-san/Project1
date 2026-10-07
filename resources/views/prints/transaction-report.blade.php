@php
    $company = \App\Support\CompanyProfile::get();
    $rupiah = fn ($value) => number_format((int) $value, 0, ',', '.');
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font: 12px/1.45 Arial, Helvetica, sans-serif; color: #111; background: #fff; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 14px; }
        .company .nm { font-size: 16px; font-weight: 700; }
        .title { text-align: right; }
        .title h1 { margin: 0; font-size: 18px; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #eee; font-size: 11px; text-transform: uppercase; }
        td.n, th.n { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        tfoot td { font-weight: 700; background: #f5f5f5; }
        .foot { margin-top: 12px; display: flex; justify-content: space-between; color: #555; font-size: 11px; }
        .toolbar { margin-bottom: 14px; display: flex; gap: 8px; }
        .toolbar button, .toolbar a { padding: 6px 12px; border: 1px solid #999; background: #f5f5f5; border-radius: 4px; color: #111; text-decoration: none; font-size: 12px; cursor: pointer; }
        /* Kertas mengikuti driver printer (continuous form LX-310, 1 lembar = 14cm). Laporan bisa
           lebih dari satu lembar, jadi isinya tidak dipotong; header tabel diulang tiap lembar. */
        @page { size: auto; margin: 4mm 6mm; }
        @media print {
            body { padding: 0; font-size: 10.5px; min-height: 14cm; }
            .toolbar { display: none; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Cetak</button>
        <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}">Unduh Excel (CSV)</a>
    </div>

    <div class="head">
        <div class="company">
            <div class="nm">{{ $company['name'] }}</div>
            @if ($company['address'])<div>{{ $company['address'] }}</div>@endif
            @if ($company['city'])<div>{{ $company['city'] }}</div>@endif
            @if ($company['phone'])<div>Telp. {{ $company['phone'] }}</div>@endif
        </div>
        <div class="title">
            <h1>{{ strtoupper($title) }}</h1>
            <div class="muted">Periode: {{ $period }}</div>
            <div class="muted">{{ $rows->count() }} transaksi</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="n">No.</th>
                @foreach ($headers as $index => $header)
                    <th class="{{ in_array($index, $moneyColumns, true) ? 'n' : '' }}">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $number => $row)
                <tr>
                    <td class="n">{{ $number + 1 }}</td>
                    @foreach ($row as $index => $value)
                        <td class="{{ in_array($index, $moneyColumns, true) ? 'n' : '' }}">{{ in_array($index, $moneyColumns, true) ? $rupiah($value) : $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($headers) + 1 }}" style="text-align:center;padding:16px" class="muted">Tidak ada transaksi pada filter ini.</td></tr>
            @endforelse
        </tbody>
        @if ($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <td></td>
                    @foreach ($headers as $index => $header)
                        <td class="{{ in_array($index, $moneyColumns, true) ? 'n' : '' }}">{{ $index === 0 ? 'TOTAL' : (array_key_exists($index, $totals) ? $rupiah($totals[$index]) : '') }}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>

    <div class="foot">
        <span>Dicetak oleh {{ auth()->user()?->name }} pada {{ now(config('session.weekly_reset_timezone'))->format('d/m/Y H:i') }}</span>
        <span>{{ $company['name'] }}</span>
    </div>

    @if ($autoPrint)
        <script>window.addEventListener('load', () => window.print());</script>
    @endif
</body>

</html>
