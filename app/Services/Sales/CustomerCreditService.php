<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\SalesInvoice;
use Illuminate\Validation\ValidationException;

class CustomerCreditService
{
    public function outstandingReceivable(int $customerId): int
    {
        return (int) SalesInvoice::query()
            ->where('customer_id', $customerId)
            ->where('status', SalesInvoice::STATUS_CONFIRMED)
            ->sum('amount_due');
    }

    public function assertAvailable(Customer $customer, int $additionalReceivable): void
    {
        if ($customer->credit_limit === null || $additionalReceivable <= 0) {
            return;
        }

        $outstanding = $this->outstandingReceivable($customer->id);

        if ($outstanding + $additionalReceivable <= $customer->credit_limit) {
            return;
        }

        throw ValidationException::withMessages([
            'credit_limit' => sprintf(
                'Plafon kredit %s terlampaui. Plafon Rp %s, piutang berjalan Rp %s, transaksi ini Rp %s.',
                $customer->name,
                number_format($customer->credit_limit, 0, ',', '.'),
                number_format($outstanding, 0, ',', '.'),
                number_format($additionalReceivable, 0, ',', '.'),
            ),
        ]);
    }
}
