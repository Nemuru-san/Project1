<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArDpPayment extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_POSTED = 'Posted';

    protected $table = 'ar_dp_payments';

    protected $fillable = [
        'code', 'payment_date', 'pre_order_id', 'customer_id', 'bank_account_id',
        'amount', 'payment_method', 'status', 'notes', 'created_by',
    ];

    protected $casts = ['payment_date' => 'date', 'amount' => 'integer'];

    public function preOrder(): BelongsTo
    {
        return $this->belongsTo(PreOrder::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ArDpPaymentAllocation::class);
    }

    public function preOrders(): BelongsToMany
    {
        return $this->belongsToMany(PreOrder::class, 'ar_dp_payment_allocations')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
