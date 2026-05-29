<?php

namespace App\Models\Inventory\Product;

use App\Models\Inventory\Product\Product as InventoryProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $fillable = ['name', 'desc', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(InventoryProduct::class, 'category_id');
    }
}
