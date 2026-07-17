<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'expense_date',
        'bank_account_id',
        'payee',
        'reference',
        'total_amount',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'total_amount' => 'integer',
    ];

    public const STATUS_DRAFT = 'Draft';

    public const STATUS_POSTED = 'Posted';

    protected static function booted(): void
    {
        static::deleting(function (Expense $expense) {
            if ($expense->isForceDeleting()) {
                $expense->details()->delete();
            }
        });
    }

    public function details(): HasMany
    {
        return $this->hasMany(ExpenseDetail::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntry(): HasOne
    {
        return $this->hasOne(JournalEntry::class, 'source_id')
            ->where('source_type', JournalEntry::SOURCE_EXPENSE);
    }
}
