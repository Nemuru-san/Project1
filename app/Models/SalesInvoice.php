<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_CONFIRMED = 'Confirmed';

    protected $fillable = [
        'invoice_no', 'invoice_date', 'due_date', 'sales_order_id', 'customer_id',
        'subtotal', 'discount_total', 'tax_amount', 'grand_total', 'dp_amount',
        'paid_amount', 'amount_due', 'status', 'confirmed_at', 'confirmed_by',
        'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_amount' => 'integer',
            'grand_total' => 'integer',
            'dp_amount' => 'integer',
            'paid_amount' => 'integer',
            'amount_due' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * Faktur beserta relasi yang dibutuhkan halaman cetak.
     */
    public static function forPrint(int|string $id): self
    {
        return static::with([
            'salesOrder.salesman', 'salesOrder.customerAddress', 'salesOrder.preOrder', 'salesOrder.salesCanvas',
            'customer.primaryAddress', 'creator',
            'items.product', 'items.warehouse', 'items.unit',
        ])->findOrFail($id);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function salesReturnInvoices(): HasMany
    {
        return $this->hasMany(SalesReturnInvoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ArPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by')->withTrashed();
    }
}
