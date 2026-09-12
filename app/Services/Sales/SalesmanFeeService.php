<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesmanFee;
use App\Models\Setting;

/**
 * Fee perekrutan customer untuk salesman.
 *
 * Salesman yang menginput (merekrut) customer berhak atas fee dari SEMUA faktur penjualan
 * customer tersebut — termasuk order yang dibuat manual, bukan lewat sales canvas-nya —
 * selama salesman masih aktif saat faktur dikonfirmasi.
 */
class SalesmanFeeService
{
    public function defaultPercent(): float
    {
        return (float) Setting::get(Setting::SALESMAN_FEE_PERCENT, 0);
    }

    public function setDefaultPercent(float $percent, ?int $userId = null): void
    {
        Setting::set(Setting::SALESMAN_FEE_PERCENT, number_format($percent, 2, '.', ''), $userId);
    }

    /**
     * Persentase fee efektif untuk customer (khusus customer, atau default global).
     */
    public function percentFor(Customer $customer): float
    {
        return $customer->acquisition_fee_percent !== null
            ? (float) $customer->acquisition_fee_percent
            : $this->defaultPercent();
    }

    /**
     * Catat fee untuk faktur yang baru dikonfirmasi. Mengembalikan null bila tidak berhak
     * (customer tanpa perekrut, salesman nonaktif/terhapus, atau persentase 0).
     */
    public function recordForInvoice(SalesInvoice $invoice): ?SalesmanFee
    {
        $customer = $invoice->customer ?? Customer::withTrashed()->find($invoice->customer_id);
        $salesman = $customer?->acquiredBySalesman;

        if (! $customer || ! $salesman || ! $salesman->is_active || $salesman->trashed()) {
            return null;
        }

        $percent = $this->percentFor($customer);
        // Dasar fee = DPP faktur (total tanpa PPN).
        $base = max(0, (int) $invoice->grand_total - (int) $invoice->tax_amount);

        if ($percent <= 0 || $base <= 0) {
            return null;
        }

        return SalesmanFee::query()->updateOrCreate(
            ['sales_invoice_id' => $invoice->id],
            [
                'salesman_id' => $salesman->id,
                'customer_id' => $customer->id,
                'invoice_date' => $invoice->invoice_date,
                'base_amount' => $base,
                'fee_percent' => $percent,
                'fee_amount' => (int) round($base * $percent / 100),
            ],
        );
    }

    public function removeForInvoice(SalesInvoice $invoice): void
    {
        SalesmanFee::query()->where('sales_invoice_id', $invoice->id)->delete();
    }

    /**
     * Sinkronkan tanggal fee bila tanggal faktur diubah.
     */
    public function syncInvoiceDate(SalesInvoice $invoice): void
    {
        SalesmanFee::query()
            ->where('sales_invoice_id', $invoice->id)
            ->update(['invoice_date' => $invoice->invoice_date]);
    }
}
