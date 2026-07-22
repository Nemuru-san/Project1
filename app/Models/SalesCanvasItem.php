<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesCanvasItem extends Model
{
    protected $fillable = [
        'sales_canvas_id',
        'product_id',
        'warehouse_id',
        'unit_id',
        'qty',
        'conversion',
        'unit_price',
        'discount_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'conversion' => 'integer',
            'unit_price' => 'integer',
            'discount_amount' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function salesCanvas(): BelongsTo
    {
        return $this->belongsTo(SalesCanvas::class);
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
