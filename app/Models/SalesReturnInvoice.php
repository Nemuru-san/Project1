<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesReturnInvoice extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_POSTED = 'Posted';

    protected $fillable = ['credit_note_no', 'customer_reference_no', 'invoice_date', 'sales_return_id', 'sales_invoice_id', 'customer_id', 'subtotal', 'tax_amount', 'grand_total', 'status', 'notes', 'posted_at', 'posted_by', 'created_by'];

    protected $casts = ['invoice_date' => 'date', 'subtotal' => 'integer', 'tax_amount' => 'integer', 'grand_total' => 'integer', 'posted_at' => 'datetime'];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by')->withTrashed();
    }
}
