<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laporan daftar transaksi (cetak / CSV) untuk Pesanan Pembelian, Faktur Pembelian,
 * Pesanan Penjualan, dan Faktur Penjualan. Filter mengikuti filter di halaman daftarnya.
 * Query string: search, status, payment_status, date_from, date_to, format (print|csv).
 */
class TransactionReportController extends Controller
{
    private const STATUS_LABELS = [
        'Draft' => 'Draf', 'draft' => 'Draf', 'Approved' => 'Disetujui', 'Received' => 'Diterima',
        'Partially Received' => 'Diterima Sebagian', 'Partial Paid' => 'Dibayar Sebagian', 'Paid' => 'Lunas',
        'Unpaid' => 'Belum Lunas', 'Cancelled' => 'Dibatalkan', 'cancelled' => 'Dibatalkan', 'Posted' => 'Diposting',
        'Confirmed' => 'Dikonfirmasi', 'verified' => 'Dikonfirmasi', 'processing' => 'Diproses', 'completed' => 'Selesai',
    ];

    public function purchaseOrders(Request $request): Response
    {
        $orders = PurchaseOrder::with('supplier')
            ->when($request->query('search'), fn (Builder $query, $search) => $query->where('code', 'like', "%{$search}%"))
            ->when($request->query('status'), fn (Builder $query, $status) => $status === 'Closed'
                ? $query->whereNotNull('closed_at')
                : $query->where('status', $status)->whereNull('closed_at'))
            ->tap(fn (Builder $query) => $this->applyDates($query, $request, 'date'))
            ->orderBy('date')->orderBy('id')
            ->get();

        return $this->respond($request, 'Laporan Pesanan Pembelian', [
            'No. PO', 'Tanggal', 'Supplier', 'Status', 'Bruto', 'PPN', 'Neto',
        ], $orders->map(fn (PurchaseOrder $order) => [
            $order->code,
            $order->date?->format('d/m/Y'),
            $order->supplier?->name ?? '-',
            $order->isClosed() ? 'Ditutup' : $this->status($order->status),
            (int) $order->gross,
            (int) $order->ppn,
            (int) $order->nett,
        ]), [4, 5, 6]);
    }

    public function purchaseInvoices(Request $request): Response
    {
        $invoices = PurchaseInvoice::with(['supplier', 'purchaseOrder'])
            ->when($request->query('search'), fn (Builder $query, $search) => $query->where(fn (Builder $query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn (Builder $supplier) => $supplier->where('name', 'like', "%{$search}%"))
                ->orWhereHas('purchaseOrder', fn (Builder $order) => $order->where('code', 'like', "%{$search}%"))))
            ->when($request->query('status'), fn (Builder $query, $status) => $query->where('status', $status))
            ->when($request->query('payment_status'), fn (Builder $query, $status) => $query->where('payment_status', $status))
            ->tap(fn (Builder $query) => $this->applyDates($query, $request, 'date'))
            ->orderBy('date')->orderBy('id')
            ->get();

        return $this->respond($request, 'Laporan Faktur Pembelian', [
            'No. Faktur', 'Tanggal', 'Jatuh Tempo', 'No. PO', 'Supplier', 'Status', 'Pembayaran', 'Total', 'Dibayar', 'Sisa',
        ], $invoices->map(fn (PurchaseInvoice $invoice) => [
            $invoice->code,
            $invoice->date?->format('d/m/Y'),
            $invoice->due_date?->format('d/m/Y') ?? '-',
            $invoice->purchaseOrder?->code ?? '-',
            $invoice->supplier?->name ?? '-',
            $this->status($invoice->status),
            $this->status($invoice->payment_status),
            (int) $invoice->grand_total,
            (int) $invoice->paid_amount,
            (int) $invoice->remaining_amount,
        ]), [7, 8, 9]);
    }

    public function salesOrders(Request $request): Response
    {
        $user = $request->user();
        $salesmanId = $user->salesman()->where('is_active', true)->value('id');

        $orders = SalesOrder::with(['customer', 'preOrder', 'salesCanvas'])
            // Sama dengan daftar SO: salesman hanya melihat pesanannya sendiri.
            ->when(! $user->isSuperAdmin() && ! $user->canPerform('sales.transaction.salesOrder', 'verify'), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('created_by', Auth::id())
                ->orWhere('salesman_id', $salesmanId ?? 0)
                ->orWhereHas('salesCanvas', fn (Builder $canvas) => $canvas->where('salesman_id', $salesmanId ?? 0))))
            ->when($request->query('search'), fn (Builder $query, $search) => $query->where(fn (Builder $query) => $query
                ->where('order_no', 'like', "%{$search}%")
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$search}%"))))
            ->when($request->query('status'), fn (Builder $query, $status) => $query->where('status', $status))
            ->tap(fn (Builder $query) => $this->applyDates($query, $request, 'date'))
            ->orderBy('date')->orderBy('id')
            ->get();

        return $this->respond($request, 'Laporan Pesanan Penjualan', [
            'No. SO', 'Tanggal', 'Pelanggan', 'Referensi', 'Status', 'Total', 'DP', 'Sisa Tagihan',
        ], $orders->map(fn (SalesOrder $order) => [
            $order->order_no,
            $order->date?->format('d/m/Y'),
            $order->customer?->name ?? '-',
            $order->order_type === 'direct'
                ? 'Penjualan Langsung'
                : ($order->preOrder ? 'Pre Order '.$order->preOrder->pre_order_no : ($order->salesCanvas?->canvas_no ? 'Kanvas '.$order->salesCanvas->canvas_no : 'Manual')),
            $this->status($order->status),
            (int) $order->grand_total,
            (int) $order->dp_amount,
            (int) $order->amount_due,
        ]), [5, 6, 7]);
    }

    public function salesInvoices(Request $request): Response
    {
        $user = $request->user();
        $salesmanId = $user->salesman()->where('is_active', true)->value('id');

        $invoices = SalesInvoice::with(['customer', 'salesOrder'])
            // Sama dengan daftar faktur: selain Super Admin hanya melihat faktur miliknya.
            ->when(! $user->isSuperAdmin(), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('created_by', Auth::id())
                ->orWhereHas('salesOrder', fn (Builder $order) => $order->where('salesman_id', $salesmanId ?? 0))
                ->orWhereHas('salesOrder.salesCanvas', fn (Builder $canvas) => $canvas->where('salesman_id', $salesmanId ?? 0))))
            ->when($request->query('search'), fn (Builder $query, $search) => $query->where(fn (Builder $query) => $query
                ->where('invoice_no', 'like', "%{$search}%")
                ->orWhereHas('salesOrder', fn (Builder $order) => $order->where('order_no', 'like', "%{$search}%"))
                ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', "%{$search}%"))))
            ->when($request->query('status'), fn (Builder $query, $status) => $query->where('status', $status))
            ->tap(fn (Builder $query) => $this->applyDates($query, $request, 'invoice_date'))
            ->orderBy('invoice_date')->orderBy('id')
            ->get();

        return $this->respond($request, 'Laporan Faktur Penjualan', [
            'No. Faktur', 'Tanggal', 'Jatuh Tempo', 'No. SO', 'Pelanggan', 'Status', 'Total', 'DP', 'Dibayar', 'Sisa',
        ], $invoices->map(fn (SalesInvoice $invoice) => [
            $invoice->invoice_no,
            $invoice->invoice_date?->format('d/m/Y'),
            $invoice->due_date?->format('d/m/Y') ?? '-',
            $invoice->salesOrder?->order_no ?? '-',
            $invoice->customer?->name ?? '-',
            $this->status($invoice->status),
            (int) $invoice->grand_total,
            (int) $invoice->dp_amount,
            (int) $invoice->paid_amount,
            (int) $invoice->amount_due,
        ]), [6, 7, 8, 9]);
    }

    private function applyDates(Builder $query, Request $request, string $column): void
    {
        $query->when($request->query('date_from'), fn (Builder $query, $date) => $query->whereDate($column, '>=', $date))
            ->when($request->query('date_to'), fn (Builder $query, $date) => $query->whereDate($column, '<=', $date));
    }

    private function status(?string $status): string
    {
        return self::STATUS_LABELS[$status] ?? (string) $status;
    }

    /**
     * @param  list<int>  $moneyColumns  indeks kolom nominal (dijumlahkan & diformat Rupiah)
     */
    private function respond(Request $request, string $title, array $headers, Collection $rows, array $moneyColumns): Response
    {
        $totals = collect($moneyColumns)->mapWithKeys(fn (int $index) => [$index => $rows->sum(fn (array $row) => $row[$index])])->all();
        $period = $this->periodLabel($request);

        if ($request->query('format') === 'csv') {
            $filename = str($title)->slug('_').'_'.now()->format('Ymd_His').'.csv';

            return response()->streamDownload(function () use ($title, $period, $headers, $rows, $totals) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8.
                fputcsv($handle, [$title]);
                fputcsv($handle, ['Periode', $period]);
                fputcsv($handle, []);
                fputcsv($handle, $headers);
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
                fputcsv($handle, collect($headers)->keys()->map(fn (int $index) => $index === 0 ? 'TOTAL' : ($totals[$index] ?? ''))->all());
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        return response()->view('prints.transaction-report', [
            'title' => $title,
            'period' => $period,
            'headers' => $headers,
            'rows' => $rows,
            'moneyColumns' => $moneyColumns,
            'totals' => $totals,
            'autoPrint' => $request->query('format') !== 'view',
        ]);
    }

    private function periodLabel(Request $request): string
    {
        $from = $request->query('date_from') ? Carbon::parse($request->query('date_from'))->format('d/m/Y') : null;
        $to = $request->query('date_to') ? Carbon::parse($request->query('date_to'))->format('d/m/Y') : null;

        return match (true) {
            $from && $to => "$from s.d. $to",
            (bool) $from => "Sejak $from",
            (bool) $to => "Sampai $to",
            default => 'Semua tanggal',
        };
    }
}
