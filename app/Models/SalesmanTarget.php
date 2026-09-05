<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesmanTarget extends Model
{
    protected $fillable = [
        'salesman_id',
        'target_month',
        'target_amount',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_month' => 'date',
            'target_amount' => 'integer',
        ];
    }

    public function salesman(): BelongsTo
    {
        return $this->belongsTo(Salesman::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
