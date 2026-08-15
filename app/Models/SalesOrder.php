<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_no', 'date', 'sales_canvas_id', 'pre_order_id', 'customer_id',
        'customer_address_id', 'is_taxed', 'tax_rate', 'subtotal',
        'discount_total', 'tax_amount', 'grand_total', 'dp_amount',
        'amount_due', 'notes', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_taxed' => 'boolean',
            'tax_rate' => 'decimal:2',
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_amount' => 'integer',
            'grand_total' => 'integer',
            'dp_amount' => 'integer',
            'amount_due' => 'integer',
        ];
    }

    public function salesCanvas(): BelongsTo
    {
        return $this->belongsTo(SalesCanvas::class);
    }

    public function preOrder(): BelongsTo
    {
        return $this->belongsTo(PreOrder::class);
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(Salesman::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function salesInvoice(): HasOne
    {
        return $this->hasOne(SalesInvoice::class);
    }
}
