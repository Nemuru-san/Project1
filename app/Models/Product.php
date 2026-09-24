<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'image',
        'sku',
        'desc',
        'category_id',
        'base_unit_id',
        'barcode',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Tabel transaksi yang mereferensikan produk. Produk yang sudah dipakai
     * di salah satu tabel ini tidak boleh dihapus, hanya boleh dinonaktifkan.
     */
    public const TRANSACTION_TABLES = [
        'purchase_order_items',
        'goods_receive_items',
        'purchase_invoice_items',
        'purchase_return_items',
        'stock_transfer_items',
        'stock_adjustment_items',
        'stock_balances',
        'sales_canvas_items',
        'sales_order_items',
        'pre_order_items',
        'delivery_order_items',
        'sales_invoice_items',
        'sales_return_items',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function hasTransactions(): bool
    {
        return in_array($this->id, self::idsWithTransactions([$this->id]), true);
    }

    /**
     * Dari daftar id produk, kembalikan id yang sudah punya transaksi.
     *
     * @param  array<int>  $ids
     * @return array<int>
     */
    public static function idsWithTransactions(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $found = [];

        foreach (self::TRANSACTION_TABLES as $table) {
            $remaining = array_values(array_diff($ids, $found));

            if (empty($remaining)) {
                break;
            }

            $found = array_merge($found, DB::table($table)
                ->whereIn('product_id', $remaining)
                ->distinct()
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all());
        }

        return $found;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'base_unit_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function goodsReceiveItems(): HasMany
    {
        return $this->hasMany(GoodsReceiveItem::class, 'product_id');
    }

    public function purchaseInvoiceItems(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'product_id');
    }

    public function stockTransferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function salesCanvasItems(): HasMany
    {
        return $this->hasMany(SalesCanvasItem::class);
    }

    public function salesOrderItems(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}
