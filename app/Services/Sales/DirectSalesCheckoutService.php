<?php

namespace App\Services\Sales;

use App\Models\ArPayment;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\DeliveryOrder;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DirectSalesCheckoutService
{
    public function handle(
        int $salesOrderId,
        int $paidAmount,
        ?int $bankAccountId,
        string $paymentMethod,
        int $userId,
    ): array {
        return DB::transaction(function () use ($salesOrderId, $paidAmount, $bankAccountId, $paymentMethod, $userId): array {
            $order = SalesOrder::with(['items.product', 'items.warehouse', 'customer'])
                ->lockForUpdate()
                ->findOrFail($salesOrderId);

            if ($order->order_type !== 'direct' || $order->status !== 'draft') {
                throw ValidationException::withMessages(['checkout' => 'Hanya Penjualan Langsung berstatus Draf yang dapat di-checkout.']);
            }

            if ($paidAmount < 0 || $paidAmount > $order->grand_total) {
                throw ValidationException::withMessages(['paidAmount' => 'Jumlah pembayaran tidak valid.']);
            }

            $remaining = (int) $order->grand_total - $paidAmount;
            $customer = Customer::lockForUpdate()->findOrFail($order->customer_id);
            app(CustomerCreditService::class)->assertAvailable($customer, $remaining);

            $bankAccount = null;
            if ($paidAmount > 0) {
                $bankAccount = BankAccount::with('chartOfAccount')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->findOrFail($bankAccountId);
                if (! $bankAccount->chart_of_account_id || ! $bankAccount->chartOfAccount?->is_postable || ! $bankAccount->chartOfAccount?->is_active) {
                    throw ValidationException::withMessages(['bankAccountId' => 'Akun kas/bank belum terhubung ke akun aktif yang dapat diposting.']);
                }
            }

            foreach ($order->items as $item) {
                $required = (int) $item->qty * (int) $item->conversion;
                $stock = StockBalance::query()
                    ->where('warehouse_id', $item->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();
                $available = (int) ($stock?->quantity ?? 0);

                if (! $stock || $available < $required) {
                    throw ValidationException::withMessages([
                        'checkout' => "Stok {$item->product?->name} di {$item->warehouse?->name} tidak cukup. Tersedia {$available}, dibutuhkan {$required}.",
                    ]);
                }
            }

            $order->forceFill([
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $userId,
            ])->save();

            $deliveryOrder = DeliveryOrder::create([
                'delivery_no' => $this->deliveryCode(),
                'delivery_date' => $order->date,
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'customer_address_id' => $order->customer_address_id,
                'notes' => 'Dibuat otomatis dari checkout Penjualan Langsung.',
                'status' => DeliveryOrder::STATUS_SHIPPED,
                'created_by' => $userId,
            ]);

            foreach ($order->items as $item) {
                $required = (int) $item->qty * (int) $item->conversion;
                $deliveryOrder->items()->create([
                    'sales_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->warehouse_id,
                    'unit_id' => $item->unit_id,
                    'conversion' => $item->conversion,
                    'qty_order' => $item->qty,
                    'qty_delivered' => $item->qty,
                    'qty_outstanding' => 0,
                    'qty_base' => $required,
                ]);
                StockBalance::query()
                    ->where('warehouse_id', $item->warehouse_id)
                    ->where('product_id', $item->product_id)
                    ->decrement('quantity', $required);
            }

            $dueDate = $remaining > 0
                ? $order->date->copy()->addDays((int) $customer->payment_terms_days)
                : $order->date;
            $invoice = SalesInvoice::create([
                'invoice_no' => $this->invoiceCode(),
                'invoice_date' => $order->date,
                'due_date' => $dueDate,
                'sales_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'subtotal' => $order->subtotal,
                'discount_total' => $order->discount_total,
                'tax_amount' => $order->tax_amount,
                'grand_total' => $order->grand_total,
                'dp_amount' => 0,
                'paid_amount' => 0,
                'amount_due' => $order->grand_total,
                'status' => SalesInvoice::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => $userId,
                'notes' => 'Dibuat otomatis dari checkout Penjualan Langsung.',
                'created_by' => $userId,
            ]);

            foreach ($order->items as $item) {
                $invoice->items()->create([
                    'sales_order_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->warehouse_id,
                    'unit_id' => $item->unit_id,
                    'qty' => $item->qty,
                    'conversion' => $item->conversion,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'line_total' => $item->line_total,
                ]);
            }
            $invoice->deliveryOrders()->attach($deliveryOrder->id);
            $this->postInvoiceJournal($invoice, $userId);

            $payment = $paidAmount > 0
                ? $this->postPayment($invoice, $order, $bankAccount, $paidAmount, $paymentMethod, $userId)
                : null;

            $order->update(['status' => 'completed', 'amount_due' => $remaining]);

            return compact('order', 'deliveryOrder', 'invoice', 'payment');
        });
    }

    private function postInvoiceJournal(SalesInvoice $invoice, int $userId): void
    {
        $journal = JournalEntry::create([
            'code' => $this->journalCode(),
            'date' => $invoice->invoice_date,
            'source_type' => JournalEntry::SOURCE_SALES_INVOICE,
            'source_id' => $invoice->id,
            'description' => 'Faktur Penjualan '.$invoice->invoice_no,
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => $userId,
        ]);
        $journal->lines()->create(['chart_of_account_id' => $this->accountId('1300'), 'debit' => $invoice->grand_total, 'credit' => 0, 'description' => 'Piutang pelanggan']);
        $revenue = (int) $invoice->grand_total - (int) $invoice->tax_amount;
        if ($revenue > 0) {
            $journal->lines()->create(['chart_of_account_id' => $this->accountId('4100'), 'debit' => 0, 'credit' => $revenue, 'description' => 'Pendapatan penjualan']);
        }
        if ($invoice->tax_amount > 0) {
            $journal->lines()->create(['chart_of_account_id' => $this->accountId('2200'), 'debit' => 0, 'credit' => $invoice->tax_amount, 'description' => 'PPN keluaran']);
        }
    }

    private function postPayment(SalesInvoice $invoice, SalesOrder $order, BankAccount $bankAccount, int $amount, string $method, int $userId): ArPayment
    {
        $payment = ArPayment::create([
            'code' => $this->paymentCode(),
            'payment_date' => $order->date,
            'sales_order_id' => $order->id,
            'sales_invoice_id' => $invoice->id,
            'customer_id' => $order->customer_id,
            'bank_account_id' => $bankAccount->id,
            'amount' => $amount,
            'payment_method' => $method,
            'status' => ArPayment::STATUS_POSTED,
            'notes' => 'Dibuat otomatis dari checkout Penjualan Langsung.',
            'created_by' => $userId,
        ]);
        $invoice->increment('paid_amount', $amount);
        $invoice->decrement('amount_due', $amount);

        $journal = JournalEntry::create([
            'code' => $this->journalCode(),
            'date' => $payment->payment_date,
            'source_type' => JournalEntry::SOURCE_AR_PAYMENT,
            'source_id' => $payment->id,
            'description' => 'Pembayaran Piutang '.$payment->code,
            'status' => JournalEntry::STATUS_POSTED,
            'created_by' => $userId,
        ]);
        $journal->lines()->create(['chart_of_account_id' => $bankAccount->chart_of_account_id, 'debit' => $amount, 'credit' => 0, 'description' => 'Penerimaan pelanggan']);
        $journal->lines()->create(['chart_of_account_id' => $this->accountId('1300'), 'debit' => 0, 'credit' => $amount, 'description' => 'Pelunasan '.$invoice->invoice_no]);

        return $payment;
    }

    private function accountId(string $code): int
    {
        $id = ChartOfAccount::where('code', $code)->where('is_active', true)->where('is_postable', true)->value('id');
        if (! $id) {
            throw ValidationException::withMessages(['checkout' => "Akun {$code} belum tersedia atau tidak dapat diposting."]);
        }

        return (int) $id;
    }

    private function deliveryCode(): string
    {
        return $this->nextCode(DeliveryOrder::class, 'delivery_no', 'SJ-'.now()->format('ymd').'-');
    }

    private function invoiceCode(): string
    {
        return $this->nextCode(SalesInvoice::class, 'invoice_no', 'FP-'.now()->format('ymd').'-');
    }

    private function paymentCode(): string
    {
        return $this->nextCode(ArPayment::class, 'code', 'ARP-'.now()->format('dmy').'-');
    }

    private function journalCode(): string
    {
        return $this->nextCode(JournalEntry::class, 'code', 'JE-'.now()->format('dmy').'-');
    }

    private function nextCode(string $model, string $column, string $prefix): string
    {
        $last = $model::withTrashed()->where($column, 'like', $prefix.'%')->orderByDesc($column)->lockForUpdate()->value($column);
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }
}
