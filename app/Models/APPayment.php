<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class APPayment extends Model
{
    use SoftDeletes;

    protected $table = 'ap_payments';

    protected $fillable = [
        'code',
        'payment_date',
        'supplier_id',
        'bank_account_id',
        'total_amount',
        'payment_method',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'integer',
    ];

    const STATUS_DRAFT = 'Draft';
    const STATUS_POSTED = 'Posted';
    const STATUS_CANCELLED = 'Cancelled';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_POSTED,
            self::STATUS_CANCELLED,
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (APPayment $payment) {
            $payment->details()->each(fn($detail) => $detail->delete());
        });

        static::restoring(function (APPayment $payment) {
            $payment->details()->onlyTrashed()->each(fn($detail) => $detail->restore());
        });
    }

    public function details(): HasMany
    {
        return $this->hasMany(APPaymentDetail::class, 'ap_payment_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
