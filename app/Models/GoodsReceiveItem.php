<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiveItem extends Model
{
    protected $table = 'goods_receive_items';

    protected $fillable = [
        'goods_receive_id',
        'purchase_order_item_id',
        'product_id',
        'warehouse_id',
        'unit_id',
        'conversion',
        'qty_order',
        'qty_received',
        'qty_outstanding',
        'qty_base',
        'note',
    ];

    protected $casts = [
        'conversion' => 'integer',
        'qty_order' => 'integer',
        'qty_received' => 'integer',
        'qty_outstanding' => 'integer',
        'qty_base' => 'integer',
    ];

    public function goodsReceive(): BelongsTo
    {
        return $this->belongsTo(GoodsReceive::class, 'goods_receive_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }
}
