{{-- Gaya bersama dokumen cetak bergaya nota continuous-form (invoice, surat jalan, transfer stok). --}}
<style>
    * { box-sizing: border-box; }
    body {
        margin: 0;
        padding: 24px 16px 48px;
        background: #e5e7eb;
        font-family: "Consolas", "Courier New", monospace;
        font-size: 12px;
        line-height: 1.45;
        color: #000;
    }
    .toolbar {
        max-width: 940px;
        margin: 0 auto 16px;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }
    .toolbar button, .toolbar a {
        font-family: inherit;
        font-size: 13px;
        padding: 8px 16px;
        border-radius: 6px;
        border: 1px solid #94a3b8;
        background: #fff;
        color: #0f172a;
        cursor: pointer;
        text-decoration: none;
    }
    .toolbar .primary { background: #2563eb; border-color: #2563eb; color: #fff; }
    .sheet {
        max-width: 940px;
        margin: 0 auto;
        background: #fff;
        padding: 18px 22px 14px;
        border: 1px solid #cbd5e1;
    }
    .tearline { display: flex; justify-content: space-between; padding: 0 4px 6px; }
    .tearline span {
        width: 9px; height: 9px; border-radius: 50%;
        border: 1px solid #94a3b8; display: inline-block;
    }
    .head { display: flex; justify-content: space-between; gap: 24px; }
    .company { max-width: 46%; }
    .company .nm { font-weight: bold; font-size: 14px; letter-spacing: .5px; text-transform: uppercase; }
    .to { min-width: 40%; text-transform: uppercase; }
    .to .nm { font-weight: bold; }
    .title { text-align: center; margin: 10px 0 2px; }
    .title h1 { margin: 0; font-size: 17px; letter-spacing: 7px; font-weight: bold; }
    .title .nota { display: inline-block; margin-top: 2px; border-bottom: 1px solid #000; padding: 0 10px 1px; letter-spacing: 1px; }
    .meta { display: flex; justify-content: space-between; gap: 12px; margin-top: 8px; }
    .meta .cell { white-space: nowrap; }
    .u { display: inline-block; min-width: 90px; border-bottom: 1px dotted #000; }
    table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    thead th {
        border-top: 1px solid #000; border-bottom: 1px solid #000;
        padding: 3px 4px; font-weight: bold; text-align: center; letter-spacing: .5px;
        white-space: nowrap;
    }
    thead th.l { text-align: left; }
    tbody td { padding: 2px 4px; vertical-align: top; }
    tbody tr.filler td { height: 18px; }
    .r { text-align: right; }
    .c { text-align: center; }
    .sep td { border-bottom: 1px solid #000; padding: 0; height: 0; }
    .lower { display: flex; justify-content: space-between; gap: 24px; margin-top: 6px; }
    .lower .left { flex: 1 1 auto; }
    .totals { width: 260px; flex: none; }
    .totals div { display: flex; justify-content: space-between; letter-spacing: .5px; }
    .totals .grand { font-weight: bold; border-top: 1px solid #000; margin-top: 2px; padding-top: 2px; }
    .bank { margin-top: 10px; line-height: 1.35; }
    .signs { display: flex; gap: 40px; margin-top: 12px; }
    .signs div { text-align: center; min-width: 150px; }
    .signs .line { margin-top: 42px; border-top: 1px solid #000; }
    .foot { display: flex; justify-content: space-between; margin-top: 10px; font-size: 11px; }
    .status-draft {
        margin-top: 6px; text-align: center; font-weight: bold; letter-spacing: 3px; color: #b91c1c;
    }
    /* Continuous form Epson LX-310: lebar 16 inci, tinggi 9 inci. */
    @page { size: 16in 9in; margin: 0; }
    @media print {
        html, body {
            width: 16in;
            height: 9in;
            margin: 0;
            padding: 0;
        }
        body {
            background: #fff;
            font-size: 11px;
            overflow: hidden;
        }
        .toolbar, .tearline { display: none !important; }
        .sheet {
            width: 16in;
            height: 9in;
            max-width: none;
            margin: 0;
            border: 0;
            padding: .18in .35in;
            overflow: hidden;
            break-inside: avoid;
            break-after: avoid-page;
            page-break-inside: avoid;
            page-break-after: avoid;
        }
    }
</style>
