<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesmanFee extends Model
{
    protected $fillable = [
        'salesman_id',
        'customer_id',
        'sales_invoice_id',
        'invoice_date',
        'base_amount',
        'fee_percent',
        'fee_amount',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'base_amount' => 'integer',
            'fee_percent' => 'decimal:2',
            'fee_amount' => 'integer',
        ];
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(Salesman::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class)->withTrashed();
    }
}
