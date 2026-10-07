<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseInvoice extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'code',
        'date',
        'due_date',
        'supplier_id',
        'purchase_order_id',
        'sub_total',
        'discount_total',
        'tax',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'remaining_amount',
        'status',
        'payment_status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'tax' => 'boolean',
        'sub_total' => 'integer',
        'discount_total' => 'integer',
        'tax_amount' => 'integer',
        'grand_total' => 'integer',
        'paid_amount' => 'integer',
        'remaining_amount' => 'integer',
    ];

    const STATUS_DRAFT = 'Draft';

    const STATUS_POSTED = 'Posted';

    // Faktur batal disimpan dengan status ini lalu di-soft-delete, sehingga tidak lagi
    // dihitung sebagai faktur aktif oleh PO, Penerimaan Barang, maupun laporan utang.
    const STATUS_CANCELLED = 'Cancelled';

    const PAYMENT_UNPAID = 'Unpaid';

    const PAYMENT_PARTIAL_PAID = 'Partial Paid';

    const PAYMENT_PAID = 'Paid';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_POSTED,
        ];
    }

    public static function paymentStatusOptions(): array
    {
        return [
            self::PAYMENT_UNPAID,
            self::PAYMENT_PARTIAL_PAID,
            self::PAYMENT_PAID,
        ];
    }

    /**
     * Faktur bisa diubah/dihapus selama belum ada pembayaran yang diposting.
     * Faktur yang sudah dibayar terkunci sampai pembayarannya dibatalkan.
     */
    public function isEditable(): bool
    {
        return $this->payment_status === self::PAYMENT_UNPAID
            && (int) $this->paid_amount <= 0;
    }

    public function editLockReason(): ?string
    {
        if ($this->isEditable()) {
            return null;
        }

        return 'Faktur Pembelian sudah dibayar ('.$this->payment_status.'). Batalkan pembayarannya terlebih dahulu di menu Pembayaran Utang untuk mengubah faktur.';
    }

    /**
     * Faktur bisa dibatalkan selama belum ada Pembayaran Utang aktif (draf maupun posting) dan retur.
     */
    public function cancelLockReason(): ?string
    {
        if ($this->trashed() || $this->status === self::STATUS_CANCELLED) {
            return 'Faktur Pembelian sudah dibatalkan.';
        }

        $hasPayment = (int) $this->paid_amount > 0 || $this->apPaymentDetails()
            ->whereHas('apPayment', fn ($payment) => $payment->where('status', '!=', APPayment::STATUS_CANCELLED))
            ->exists();

        if ($hasPayment) {
            return 'Faktur Pembelian tidak dapat dibatalkan karena sudah memiliki Pembayaran Utang.';
        }

        if ($this->purchaseReturnInvoices()->exists()) {
            return 'Faktur Pembelian tidak dapat dibatalkan karena sudah memiliki Faktur Retur.';
        }

        return null;
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class, 'purchase_invoice_id');
    }

    public function purchaseReturnInvoices(): HasMany
    {
        return $this->hasMany(PurchaseReturnInvoice::class, 'purchase_invoice_id');
    }

    public function apPaymentDetails(): HasMany
    {
        return $this->hasMany(APPaymentDetail::class, 'purchase_invoice_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function goodsReceives(): BelongsToMany
    {
        return $this->belongsToMany(GoodsReceive::class, 'goods_receive_purchase_invoice')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
