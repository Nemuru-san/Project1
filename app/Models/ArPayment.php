<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArPayment extends Model
{
    use LogsActivity, SoftDeletes;

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_POSTED = 'Posted';

    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'code', 'payment_date', 'sales_order_id', 'sales_invoice_id', 'customer_id', 'bank_account_id',
        'amount', 'payment_method', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'amount' => 'integer'];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
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
