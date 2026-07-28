<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreOrder extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_SALES_ORDER = 'sales_order';

    public const DP_STATUS_UNPAID = 'unpaid';

    public const DP_STATUS_PARTIAL_PAID = 'partial_paid';

    public const DP_STATUS_PAID = 'paid';

    protected $fillable = [
        'pre_order_no', 'date', 'customer_id', 'customer_address_id', 'is_taxed',
        'tax_rate', 'subtotal', 'discount_total', 'tax_amount', 'grand_total',
        'dp_amount', 'dp_payment_status', 'notes', 'status', 'created_by',
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
        ];
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
        return $this->hasMany(PreOrderItem::class);
    }

    public function dpPayments(): HasMany
    {
        return $this->hasMany(ArDpPayment::class);
    }

    public function dpAllocations(): HasMany
    {
        return $this->hasMany(ArDpPaymentAllocation::class);
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class);
    }

    public function getPostedDpAmountAttribute(): int
    {
        if (array_key_exists('posted_dp_amount', $this->attributes)) {
            return (int) $this->attributes['posted_dp_amount'];
        }

        return (int) $this->dpAllocations()
            ->whereHas('payment', fn ($query) => $query->where('status', ArDpPayment::STATUS_POSTED))
            ->sum('amount');
    }

    public function getRemainingDpAmountAttribute(): int
    {
        return max(0, (int) $this->dp_amount - $this->posted_dp_amount);
    }

    public function syncDpPaymentStatus(): string
    {
        $postedAmount = (int) $this->dpAllocations()
            ->whereHas('payment', fn ($query) => $query->where('status', ArDpPayment::STATUS_POSTED))
            ->sum('amount');

        $status = match (true) {
            (int) $this->dp_amount > 0 && $postedAmount >= (int) $this->dp_amount => self::DP_STATUS_PAID,
            $postedAmount > 0 => self::DP_STATUS_PARTIAL_PAID,
            default => self::DP_STATUS_UNPAID,
        };

        if ($this->dp_payment_status !== $status) {
            $this->update(['dp_payment_status' => $status]);
        }

        return $status;
    }
}
