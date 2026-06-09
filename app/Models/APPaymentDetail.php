<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class APPaymentDetail extends Model
{
    use SoftDeletes;

    protected $table = 'ap_payment_details';

    protected $fillable = [
        'ap_payment_id',
        'purchase_invoice_id',
        'amount',
        'note',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function apPayment(): BelongsTo
    {
        return $this->belongsTo(APPayment::class, 'ap_payment_id');
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }
}
