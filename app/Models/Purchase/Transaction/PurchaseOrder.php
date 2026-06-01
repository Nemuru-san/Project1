<?php

namespace App\Models\Purchase\Transaction;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'date',
        'id_supplier',
        'id_user',
        'total_price',
        'tax',
        'purchase_note',
        'gross',
        'nett',
        'status',
    ];

    protected $casts = [
        'date'        => 'date',
        'tax'         => 'boolean',
        'total_price' => 'integer',
        'gross'       => 'integer',
        'nett'        => 'integer',
    ];

    const STATUS_DRAFT           = 'Draft';
    const STATUS_APPROVED        = 'Approved';
    const STATUS_RECEIVED        = 'Received';
    const STATUS_CANCELED        = 'Canceled';
    const STATUS_TAGIHAN         = 'Tagihan';
    const STATUS_BAYAR_FULL      = 'Bayar Full';
    const STATUS_BAYAR_SETENGAH  = 'Bayar Setengah';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_APPROVED,
            self::STATUS_RECEIVED,
            self::STATUS_CANCELED,
            self::STATUS_TAGIHAN,
            self::STATUS_BAYAR_FULL,
            self::STATUS_BAYAR_SETENGAH,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (PurchaseOrder $po) {
            $po->items()->each(fn($item) => $item->delete());
        });

        static::restoring(function (PurchaseOrder $po) {
            $po->items()->onlyTrashed()->each(fn($item) => $item->restore());
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
