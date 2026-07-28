<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArDpPaymentAllocation extends Model
{
    protected $fillable = ['ar_dp_payment_id', 'pre_order_id', 'amount'];

    protected $casts = ['amount' => 'integer'];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(ArDpPayment::class, 'ar_dp_payment_id');
    }

    public function preOrder(): BelongsTo
    {
        return $this->belongsTo(PreOrder::class);
    }
}
