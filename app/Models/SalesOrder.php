<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_no', 'date', 'order_type', 'sales_canvas_id', 'pre_order_id', 'salesman_id', 'customer_id',
        'customer_address_id', 'is_taxed', 'tax_rate', 'subtotal',
        'discount_total', 'tax_amount', 'grand_total', 'dp_amount',
        'amount_due', 'notes', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_taxed' => 'boolean',
            'tax_rate' => 'decimal:2',
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'tax_amount' => 'integer',
            'grand_total' => 'integer',
            'dp_amount' => 'integer',
            'amount_due' => 'integer',
        ];
    }

    public static function forThermalPrint(int|string $id): self
    {
        return static::with([
            'customer', 'creator', 'items.product', 'items.unit',
            'salesInvoice.payments.bankAccount',
        ])->where('order_type', 'direct')->findOrFail($id);
    }

    public function isAccessibleBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin() || $this->created_by === $user->id) {
            return true;
        }

        $salesmanId = $user->salesman()->where('is_active', true)->value('id');

        return $this->salesman_id === $salesmanId
            || $user->canPerform('sales.transaction.salesOrder', 'verify');
    }

    /**
     * Ikut memuat total qty terkirim per item (termasuk Surat Jalan draf, karena
     * qty-nya sudah dialokasikan), supaya sisa kiriman bisa dihitung tanpa query per SO.
     */
    public function scopeWithDeliveryProgress(Builder $query): void
    {
        $query->with(['items' => fn ($item) => $item->withSum([
            'deliveryOrderItems as delivered_qty' => fn ($deliveryItem) => $deliveryItem->whereHas(
                'deliveryOrder',
                fn ($delivery) => $delivery->whereIn('status', [
                    DeliveryOrder::STATUS_DRAFT,
                    ...DeliveryOrder::STOCK_STATUSES,
                ])
            ),
        ], 'qty_delivered')]);
    }

    /**
     * Masih ada item yang belum dikirim penuh. Butuh scopeWithDeliveryProgress().
     */
    public function hasOutstandingDelivery(): bool
    {
        return $this->items->contains(fn ($item) => (int) $item->qty > (int) ($item->delivered_qty ?? 0));
    }

    /**
     * Pesanan Penjualan hanya bisa diubah/dihapus selama masih Draf.
     */
    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function editLockReason(): ?string
    {
        return $this->isEditable()
            ? null
            : 'Pesanan Penjualan yang sudah diproses tidak dapat diubah.';
    }

    public function salesCanvas(): BelongsTo
    {
        return $this->belongsTo(SalesCanvas::class);
    }

    public function preOrder(): BelongsTo
    {
        return $this->belongsTo(PreOrder::class);
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(Salesman::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }

    public function salesInvoice(): HasOne
    {
        return $this->hasOne(SalesInvoice::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }
}
