<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryOrderItem extends Model
{
    protected $fillable = [
        'delivery_order_id', 'sales_order_item_id', 'product_id', 'warehouse_id',
        'unit_id', 'conversion', 'qty_order', 'qty_delivered', 'qty_outstanding',
        'qty_base', 'note',
    ];

    protected function casts(): array
    {
        return [
            'conversion' => 'integer',
            'qty_order' => 'integer',
            'qty_delivered' => 'integer',
            'qty_outstanding' => 'integer',
            'qty_base' => 'integer',
        ];
    }

    public function salesReturnItems(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
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
