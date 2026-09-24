<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'date',
        'supplier_id',
        'user_id',
        'total_price',
        'tax',
        'ppn',
        'purchase_note',
        'gross',
        'nett',
        'status',
        'closed_at',
        'closed_by',
        'close_note',
    ];

    protected $casts = [
        'date' => 'date',
        'tax' => 'boolean',
        'total_price' => 'integer',
        'gross' => 'integer',
        'nett' => 'integer',
        'closed_at' => 'datetime',
    ];

    const STATUS_DRAFT = 'Draft';

    const STATUS_APPROVED = 'Approved';

    const STATUS_RECEIVED = 'Received';

    const STATUS_PARTIALLY_RECEIVED = 'Partially Received';

    const STATUS_PARTIAL_PAID = 'Partial Paid';

    const STATUS_PARITAL_PAID = self::STATUS_PARTIAL_PAID;

    const STATUS_PAID = 'Paid';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_APPROVED,
            self::STATUS_RECEIVED,
            self::STATUS_PARTIALLY_RECEIVED,
            self::STATUS_PARITAL_PAID,
            self::STATUS_PAID,
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    /**
     * PO hanya bisa diubah/dihapus selama masih Draf dan belum ditutup.
     */
    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT && ! $this->isClosed();
    }

    public function editLockReason(): ?string
    {
        if ($this->isEditable()) {
            return null;
        }

        if ($this->isClosed()) {
            return 'Pesanan Pembelian sudah ditutup sehingga tidak dapat diubah.';
        }

        return 'Pesanan Pembelian berstatus '.$this->status.' tidak dapat diubah lagi.';
    }

    /**
     * Total qty yang sudah diterima (GR Received / Invoiced) untuk seluruh item PO.
     */
    public function receivedQty(): int
    {
        return (int) GoodsReceiveItem::query()
            ->whereIn('purchase_order_item_id', $this->items()->select('id'))
            ->whereHas('goodsReceive', fn ($query) => $query->whereIn('status', GoodsReceive::STOCK_STATUSES))
            ->sum('qty_received');
    }

    public function orderedQty(): int
    {
        return (int) $this->items()->sum('qty');
    }

    /**
     * Ikut memuat total qty diterima per item, supaya sisa penerimaan bisa
     * dihitung tanpa query tambahan per PO saat mengisi daftar pilihan.
     */
    public function scopeWithReceiptProgress(Builder $query): void
    {
        $query->with(['items' => fn ($item) => $item->withSum([
            'goodsReceiveItems as received_qty' => fn ($receiveItem) => $receiveItem->whereHas(
                'goodsReceive',
                fn ($receive) => $receive->whereIn('status', GoodsReceive::STOCK_STATUSES)
            ),
        ], 'qty_received')]);
    }

    /**
     * Masih ada item yang belum diterima penuh. Butuh scopeWithReceiptProgress().
     */
    public function hasOutstandingReceipt(): bool
    {
        return $this->items->contains(fn ($item) => (int) $item->qty > (int) ($item->received_qty ?? 0));
    }

    /**
     * PO yang sudah disetujui, belum ditutup, dan masih punya sisa barang belum diterima.
     */
    public function canBeClosed(): bool
    {
        return ! $this->isClosed()
            && $this->status !== self::STATUS_DRAFT
            && $this->receivedQty() < $this->orderedQty();
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'purchase_order_id');
    }

    public function goodsReceives(): HasMany
    {
        return $this->hasMany(GoodsReceive::class, 'purchase_order_id');
    }

    /**
     * Hitung ulang status pembayaran PO dari seluruh fakturnya.
     *
     * Satu PO bisa punya beberapa faktur (penerimaan bertahap), jadi lunas berarti
     * total yang dibayar sudah menutup nilai PO, bukan sekadar satu faktur lunas.
     */
    public function refreshPaymentStatus(): void
    {
        $paidAmount = (int) $this->purchaseInvoices()->sum('paid_amount');

        // Belum ada pembayaran: status penerimaan dibiarkan apa adanya.
        if ($paidAmount <= 0) {
            return;
        }

        $orderTotal = (int) ($this->nett ?: $this->total_price);

        $this->update([
            'status' => $orderTotal > 0 && $paidAmount >= $orderTotal
                ? self::STATUS_PAID
                : self::STATUS_PARTIAL_PAID,
        ]);
    }
}
