<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'trf_no',
        'date',
        'warehouse_from_id',
        'warehouse_to_id',
        'notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Transfer Stok beserta relasi yang dibutuhkan halaman cetak.
     */
    public static function forPrint(int|string $id): self
    {
        return static::with([
            'warehouseFrom', 'warehouseTo', 'creator', 'items.product', 'items.unit',
        ])->findOrFail($id);
    }

    public function warehouseFrom(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_from_id');
    }

    public function warehouseTo(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_to_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
