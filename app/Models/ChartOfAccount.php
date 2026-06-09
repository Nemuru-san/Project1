<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends Model
{
    use SoftDeletes;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'parent_id',
        'is_postable',
        'is_active',
    ];

    protected $casts = [
        'is_postable' => 'boolean',
        'is_active' => 'boolean',
    ];

    const TYPE_ASSET = 'Asset';
    const TYPE_LIABILITY = 'Liability';
    const TYPE_EQUITY = 'Equity';
    const TYPE_REVENUE = 'Revenue';
    const TYPE_EXPENSE = 'Expense';
    const TYPE_COGS = 'COGS';

    const NORMAL_DEBIT = 'Debit';
    const NORMAL_CREDIT = 'Credit';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_ASSET,
            self::TYPE_LIABILITY,
            self::TYPE_EQUITY,
            self::TYPE_REVENUE,
            self::TYPE_EXPENSE,
            self::TYPE_COGS,
        ];
    }

    public static function normalBalanceOptions(): array
    {
        return [
            self::NORMAL_DEBIT,
            self::NORMAL_CREDIT,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class, 'chart_of_account_id');
    }

    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'chart_of_account_id');
    }
}
