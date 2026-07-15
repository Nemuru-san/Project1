<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'product_id',
        'purchase_order_id',
        'price_id',
        'unit_id',
        'qty',
        'price',
        'conversion',
        'qty_base',
        'total_harga',
        'disc',
    ];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'integer',
        'conversion' => 'integer',
        'qty_base' => 'integer',
        'total_harga' => 'integer',
        'disc' => 'integer',
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

    public function purchaseInvoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'purchase_order_item_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class, 'price_id');
    }
}
