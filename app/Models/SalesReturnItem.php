<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    protected $fillable = ['sales_return_id', 'delivery_order_item_id', 'sales_order_item_id', 'product_id', 'warehouse_id', 'unit_id', 'conversion', 'qty', 'qty_base', 'unit_price', 'subtotal', 'reason'];

    protected $casts = ['conversion' => 'integer', 'qty' => 'integer', 'qty_base' => 'integer', 'unit_price' => 'integer', 'subtotal' => 'integer'];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function deliveryOrderItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrderItem::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class)->withTrashed();
    }
}
