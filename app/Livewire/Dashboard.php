<?php

namespace App\Livewire;

use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\DeliveryOrder;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Services\Finance\LedgerService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Ringkasan operasional. Setiap bagian hanya dihitung & ditampilkan bila peran pengguna
 * boleh membuka modul sumbernya (mengikuti izin akses modul di sidebar).
 */
class Dashboard extends Component
{
    public function render(LedgerService $ledger)
    {
        $user = auth()->user();
        $can = fn (string $module) => $user->canAccessModule($module);
        $monthStart = now()->startOfMonth()->toDateString();
        $today = now()->toDateString();
        $lastMonthStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lastMonthEnd = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $data = [
            'canSales' => $can('sales.transaction.sales-invoice'),
            'canPurchase' => $can('purchases.transaction.purchase-invoice'),
            'canFinance' => $can('finance.report.profit-loss'),
            'canBank' => $can('finance.master.bank-accounts'),
            'canSalesOrder' => $can('sales.transaction.salesOrder'),
            'canDelivery' => $can('sales.transaction.delivery-order'),
            'canPurchaseOrder' => $can('purchases.transaction.purchase-order'),
        ];

        if ($data['canSales']) {
            $confirmed = SalesInvoice::query()->where('status', SalesInvoice::STATUS_CONFIRMED);
            $data['salesThisMonth'] = (int) (clone $confirmed)->whereDate('invoice_date', '>=', $monthStart)->whereDate('invoice_date', '<=', $today)->sum('grand_total');
            $data['salesLastMonth'] = (int) (clone $confirmed)->whereDate('invoice_date', '>=', $lastMonthStart)->whereDate('invoice_date', '<=', $lastMonthEnd)->sum('grand_total');
            $data['receivable'] = (int) (clone $confirmed)->sum('amount_due');
            $data['overdueInvoices'] = (clone $confirmed)->where('amount_due', '>', 0)->whereDate('due_date', '<', $today)->count();
            $data['salesTrend'] = $this->salesTrend();
            $data['topProducts'] = $this->topProducts($monthStart, $today);
            $data['recentInvoices'] = SalesInvoice::query()->with('customer')->latest('invoice_date')->latest('id')->limit(6)->get();
        }

        if ($data['canPurchase']) {
            $posted = PurchaseInvoice::query()->where('status', PurchaseInvoice::STATUS_POSTED);
            $data['payable'] = (int) (clone $posted)->sum('remaining_amount');
            $data['overduePurchase'] = (clone $posted)->where('remaining_amount', '>', 0)->whereDate('due_date', '<', $today)->count();
        }

        if ($data['canFinance']) {
            $data['netIncomeThisMonth'] = $ledger->netIncome($monthStart, $today);
        }

        if ($data['canBank']) {
            $accountIds = BankAccount::query()->where('is_active', true)->whereNotNull('chart_of_account_id')->pluck('chart_of_account_id')->unique()->all();
            $totals = $ledger->totalsByAccount(to: $today, accountIds: $accountIds);
            $data['cashAndBank'] = ChartOfAccount::query()->whereIn('id', $accountIds)->get()
                ->sum(fn (ChartOfAccount $account) => LedgerService::signed($account, ...array_values($totals->get($account->id, ['debit' => 0, 'credit' => 0]))));
        }

        // Daftar hal yang menunggu tindakan.
        $data['pending'] = collect([
            $data['canSalesOrder'] ? ['label' => 'Pesanan Penjualan draf menunggu konfirmasi', 'count' => SalesOrder::query()->where('status', 'draft')->count(), 'route' => 'sales.transaction.salesOrder'] : null,
            $data['canDelivery'] ? ['label' => 'Surat Jalan terkirim belum difakturkan', 'count' => DeliveryOrder::query()->where('status', DeliveryOrder::STATUS_SHIPPED)->count(), 'route' => 'sales.transaction.deliveryOrder'] : null,
            $data['canSales'] ? ['label' => 'Faktur Penjualan lewat jatuh tempo', 'count' => $data['overdueInvoices'], 'route' => 'sales.report.invoice-outstanding'] : null,
            $data['canPurchaseOrder'] ? ['label' => 'Pesanan Pembelian belum diterima penuh', 'count' => PurchaseOrder::query()->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED])->count(), 'route' => 'purchases.report.unfinished-purchase-order'] : null,
            $data['canPurchase'] ? ['label' => 'Faktur Pembelian lewat jatuh tempo', 'count' => $data['overduePurchase'], 'route' => 'purchases.report.unfinished-purchase-invoice'] : null,
        ])->filter()->values();

        return view('livewire.dashboard', $data);
    }

    /**
     * Penjualan terkonfirmasi 6 bulan terakhir (termasuk bulan ini), untuk grafik batang.
     *
     * @return array<int, array{label:string, total:int}>
     */
    private function salesTrend(): array
    {
        $from = now()->subMonthsNoOverflow(5)->startOfMonth();
        // Dikelompokkan di PHP agar tidak bergantung fungsi tanggal spesifik MySQL/SQLite.
        $totals = SalesInvoice::query()
            ->where('status', SalesInvoice::STATUS_CONFIRMED)
            ->whereDate('invoice_date', '>=', $from->toDateString())
            ->get(['invoice_date', 'grand_total'])
            ->groupBy(fn (SalesInvoice $invoice) => $invoice->invoice_date->format('Y-m'))
            ->map(fn ($invoices) => (int) $invoices->sum('grand_total'));

        return collect(range(0, 5))
            ->map(fn (int $i) => $from->copy()->addMonthsNoOverflow($i))
            ->map(fn ($month) => ['label' => $month->translatedFormat('M y'), 'total' => (int) ($totals[$month->format('Y-m')] ?? 0)])
            ->all();
    }

    private function topProducts(string $from, string $to)
    {
        return SalesInvoiceItem::query()
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_items.sales_invoice_id')
            ->join('products', 'products.id', '=', 'sales_invoice_items.product_id')
            ->where('sales_invoices.status', SalesInvoice::STATUS_CONFIRMED)
            ->whereDate('sales_invoices.invoice_date', '>=', $from)->whereDate('sales_invoices.invoice_date', '<=', $to)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get([
                'products.name',
                DB::raw('SUM(sales_invoice_items.qty * sales_invoice_items.conversion) AS qty'),
                DB::raw('SUM(sales_invoice_items.line_total) AS total'),
            ]);
    }
}
