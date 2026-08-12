<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    protected $fillable = ['purchase_return_id', 'goods_receive_item_id', 'purchase_order_item_id', 'product_id', 'warehouse_id', 'unit_id', 'conversion', 'qty', 'qty_base', 'unit_price', 'subtotal', 'reason'];

    protected $casts = ['conversion' => 'integer', 'qty' => 'integer', 'qty_base' => 'integer', 'unit_price' => 'integer', 'subtotal' => 'integer'];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function goodsReceiveItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiveItem::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
