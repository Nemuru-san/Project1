<?php

namespace App\Models\Inventory\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    protected $fillable = ['code', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
