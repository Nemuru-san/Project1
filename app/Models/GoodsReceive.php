<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceive extends Model
{
    use SoftDeletes;

    protected $table = 'goods_receives';

    protected $fillable = [
        'code',
        'date',
        'supplier_id',
        'purchase_order_id',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    const STATUS_DRAFT = 'Draft';

    const STATUS_RECEIVED = 'Received';

    /** Sudah masuk ke Faktur Pembelian. Barang tetap dihitung sebagai diterima. */
    const STATUS_INVOICED = 'Invoiced';

    const STATUS_CANCELLED = 'Cancelled';

    /** Status yang berarti barang sudah benar-benar masuk stok. */
    const STOCK_STATUSES = [
        self::STATUS_RECEIVED,
        self::STATUS_INVOICED,
    ];

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_RECEIVED,
            self::STATUS_INVOICED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Penerimaan Barang hanya bisa diubah/dihapus selama masih Draf.
     */
    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function editLockReason(): ?string
    {
        return $this->isEditable()
            ? null
            : 'Penerimaan Barang berstatus '.$this->status.' tidak dapat diubah lagi.';
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiveItem::class, 'goods_receive_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(PurchaseReturn::class, 'goods_receive_id');
    }

    public function purchaseInvoices(): BelongsToMany
    {
        return $this->belongsToMany(PurchaseInvoice::class, 'goods_receive_purchase_invoice')
            ->withTimestamps();
    }
}
