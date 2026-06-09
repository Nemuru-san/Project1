<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable = [
        'code',
        'date',
        'source_type',
        'source_id',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    const STATUS_DRAFT = 'Draft';
    const STATUS_POSTED = 'Posted';
    const STATUS_CANCELLED = 'Cancelled';

    const SOURCE_PURCHASE_INVOICE = 'PurchaseInvoice';
    const SOURCE_AP_PAYMENT = 'APPayment';
    const SOURCE_MANUAL_JOURNAL = 'ManualJournal';

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
        static::deleting(function (JournalEntry $journalEntry) {
            $journalEntry->lines()->delete();
        });
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
