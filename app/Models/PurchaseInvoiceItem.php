<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoiceItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_invoice_id',
        'purchase_order_item_id',
        'product_id',
        'unit_id',
        'conversion',
        'qty',
        'qty_base',
        'price',
        'discount',
        'tax_amount',
        'total',
        'note',
    ];

    protected $casts = [
        'conversion' => 'integer',
        'qty' => 'integer',
        'qty_base' => 'integer',
        'price' => 'integer',
        'discount' => 'integer',
        'tax_amount' => 'integer',
        'total' => 'integer',
    ];

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'unit_id');
    }
}
