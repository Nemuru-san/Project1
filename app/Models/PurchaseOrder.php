<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'date',
        'supplier_id',
        'user_id',
        'total_price',
        'tax',
        'ppn',
        'purchase_note',
        'gross',
        'nett',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'tax' => 'boolean',
        'total_price' => 'integer',
        'gross' => 'integer',
        'nett' => 'integer',
    ];

    const STATUS_DRAFT = 'Draft';

    const STATUS_APPROVED = 'Approved';

    const STATUS_RECEIVED = 'Received';

    const STATUS_PARTIALLY_RECEIVED = 'Partially Received';

    const STATUS_PARTIAL_PAID = 'Partial Paid';

    const STATUS_PARITAL_PAID = self::STATUS_PARTIAL_PAID;

    const STATUS_PAID = 'Paid';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_APPROVED,
            self::STATUS_RECEIVED,
            self::STATUS_PARTIALLY_RECEIVED,
            self::STATUS_PARITAL_PAID,
            self::STATUS_PAID,
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'purchase_order_id');
    }

    public function goodsReceives(): HasMany
    {
        return $this->hasMany(GoodsReceive::class, 'purchase_order_id');
    }
}
