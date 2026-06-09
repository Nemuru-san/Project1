<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = "suppliers";

    protected $fillable = [
        'code',
        'name',
        'address',
        'contact',
        'created_by',
    ];

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    public function apPayments(): HasMany
    {
        return $this->hasMany(APPayment::class, 'supplier_id');
    }
}
