<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $table = "products";

    protected $fillable = [
        'prd_code',
        'prd_name',
        'prd_desc',
        'prd_category',
        'prd_brand',
        'prd_unit',
        'prd_barcode',
        'prd_image',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
