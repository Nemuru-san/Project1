<?php

namespace App\Models\Inventory\Product;

use App\Models\Inventory\Product\ProductPrice as ProductProductPrice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'image',
        'sku',
        'desc',
        'category_id',
        'brand',
        'barcode',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductProductPrice::class);
    }
}
