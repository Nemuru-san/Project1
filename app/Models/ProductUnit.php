<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    protected $fillable = ['name', 'desc'];

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
