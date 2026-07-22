<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreOrderItem extends Model
{
    protected $fillable = [
        'pre_order_id', 'product_id', 'warehouse_id', 'unit_id', 'qty',
        'conversion', 'unit_price', 'discount_amount', 'line_total',
    ];

    protected $casts = [
        'qty' => 'integer', 'conversion' => 'integer', 'unit_price' => 'integer',
        'discount_amount' => 'integer', 'line_total' => 'integer',
    ];

    public function preOrder(): BelongsTo
    {
        return $this->belongsTo(PreOrder::class);
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
