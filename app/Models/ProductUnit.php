<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductUnit extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'name'];

    public function productPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }
}
