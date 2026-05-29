<?php

namespace App\Models\Inventory\Product;

use App\Models\Inventory\Product\ProductUnit as ProductProductUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'unit_id',
        'conversion',
        'price',
    ];

    protected $casts = [
        'price'      => 'integer',
        'conversion' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductProductUnit::class);
    }
}
