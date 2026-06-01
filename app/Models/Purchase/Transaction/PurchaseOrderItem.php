<?php

namespace App\Models\Purchase\Transaction;

use App\Models\Inventory\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id_product',
        'purchase_order_id',
        'qty',
        'total_harga',
        'disc',
    ];

    protected $casts = [
        'qty'         => 'integer',
        'total_harga' => 'integer',
        'disc'        => 'integer',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }
}
