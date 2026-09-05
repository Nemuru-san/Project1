<?php

namespace App\Services;

use App\Models\ArDpPayment;
use App\Models\PreOrder;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConvertPreOrderToSalesOrder
{
    public function handle(int $preOrderId): SalesOrder
    {
        return DB::transaction(function () use ($preOrderId) {
            $preOrder = PreOrder::with('items')->lockForUpdate()->findOrFail($preOrderId);

            if ($preOrder->salesOrder()->exists()) {
                throw new RuntimeException('Pesanan Awal sudah pernah dikonversi.');
            }

            if ($preOrder->status !== PreOrder::STATUS_CONFIRMED) {
                throw new RuntimeException('Pesanan Awal harus dikonfirmasi sebelum dijadikan Pesanan Penjualan.');
            }

            if ($preOrder->dp_payment_status !== PreOrder::DP_STATUS_PAID) {
                throw new RuntimeException('Target DP Pesanan Awal harus dibayar lunas sebelum dijadikan Pesanan Penjualan.');
            }

            $dpAmount = (int) $preOrder->dpAllocations()
                ->whereHas('payment', fn ($query) => $query->where('status', ArDpPayment::STATUS_POSTED))
                ->sum('amount');
            $dpAmount = min($dpAmount, (int) $preOrder->grand_total);

            $order = SalesOrder::create([
                'order_no' => $this->generateCode(),
                'date' => now()->toDateString(),
                'pre_order_id' => $preOrder->id,
                'salesman_id' => $preOrder->customer?->default_salesman_id,
                'customer_id' => $preOrder->customer_id,
                'customer_address_id' => $preOrder->customer_address_id,
                'is_taxed' => $preOrder->is_taxed,
                'tax_rate' => $preOrder->tax_rate,
                'subtotal' => $preOrder->subtotal,
                'discount_total' => $preOrder->discount_total,
                'tax_amount' => $preOrder->tax_amount,
                'grand_total' => $preOrder->grand_total,
                'dp_amount' => $dpAmount,
                'amount_due' => max(0, (int) $preOrder->grand_total - $dpAmount),
                'notes' => $preOrder->notes,
                'status' => 'draft',
                'created_by' => Auth::id(),
            ]);

            foreach ($preOrder->items as $item) {
                $order->items()->create($item->only([
                    'product_id', 'warehouse_id', 'unit_id', 'qty', 'conversion',
                    'unit_price', 'discount_amount', 'line_total',
                ]));
            }

            $preOrder->update(['status' => PreOrder::STATUS_SALES_ORDER]);

            return $order;
        });
    }

    private function generateCode(): string
    {
        $prefix = 'SO-PO-'.now()->format('ymd').'-';
        $last = SalesOrder::withTrashed()->where('order_no', 'like', $prefix.'%')->orderByDesc('order_no')->value('order_no');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
