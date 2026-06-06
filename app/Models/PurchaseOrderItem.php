<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'purchase_order_id',
        'qty',
        'total_harga',
        'disc',
    ];

    protected $casts = [
        'qty'         => 'integer',
        'total_harga' => 'integer',
        'disc'        => 'integer',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function goodsReceiveItems(): HasMany
    {
        return $this->hasMany(GoodsReceiveItem::class, 'purchase_order_item_id');
    }
}
