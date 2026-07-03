<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceive extends Model
{
    use SoftDeletes;

    protected $table = 'goods_receives';

    protected $fillable = [
        'code',
        'date',
        'supplier_id',
        'purchase_order_id',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    const STATUS_DRAFT = 'Draft';

    const STATUS_RECEIVED = 'Received';

    const STATUS_CANCELLED = 'Cancelled';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_RECEIVED,
            self::STATUS_CANCELLED,
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiveItem::class, 'goods_receive_id');
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

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'purchase_order_id', 'purchase_order_id');
    }
}
