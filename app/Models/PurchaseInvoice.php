<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'supplier_invoice_number',
        'date',
        'due_date',
        'supplier_id',
        'purchase_order_id',
        'sub_total',
        'discount_total',
        'tax',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'remaining_amount',
        'status',
        'payment_status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'tax' => 'boolean',
        'sub_total' => 'integer',
        'discount_total' => 'integer',
        'tax_amount' => 'integer',
        'grand_total' => 'integer',
        'paid_amount' => 'integer',
        'remaining_amount' => 'integer',
    ];

    const STATUS_DRAFT = 'Draft';
    const STATUS_POSTED = 'Posted';
    const STATUS_CANCELLED = 'Cancelled';

    const PAYMENT_UNPAID = 'Unpaid';
    const PAYMENT_PARTIAL = 'Partial';
    const PAYMENT_PAID = 'Paid';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_POSTED,
            self::STATUS_CANCELLED,
        ];
    }

    public static function paymentStatusOptions(): array
    {
        return [
            self::PAYMENT_UNPAID,
            self::PAYMENT_PARTIAL,
            self::PAYMENT_PAID,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (PurchaseInvoice $invoice) {
            $invoice->items()->each(fn($item) => $item->delete());
        });

        static::restoring(function (PurchaseInvoice $invoice) {
            $invoice->items()->onlyTrashed()->each(fn($item) => $item->restore());
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'purchase_invoice_id');
    }

    public function apPaymentDetails(): HasMany
    {
        return $this->hasMany(APPaymentDetail::class, 'purchase_invoice_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
