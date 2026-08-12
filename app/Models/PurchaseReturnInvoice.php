<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturnInvoice extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_POSTED = 'Posted';

    protected $fillable = ['credit_note_no', 'supplier_credit_no', 'invoice_date', 'purchase_return_id', 'purchase_invoice_id', 'supplier_id', 'subtotal', 'tax_amount', 'grand_total', 'status', 'notes', 'posted_at', 'posted_by', 'created_by'];

    protected $casts = ['invoice_date' => 'date', 'subtotal' => 'integer', 'tax_amount' => 'integer', 'grand_total' => 'integer', 'posted_at' => 'datetime'];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
