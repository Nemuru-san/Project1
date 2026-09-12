<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salesman extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'user_id',
        'is_active',
        'activity_checkpoint_at',
        'inactivity_warned_at',
        'deactivated_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'activity_checkpoint_at' => 'datetime',
            'inactivity_warned_at' => 'datetime',
            'deactivated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesCanvases(): HasMany
    {
        return $this->hasMany(SalesCanvas::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'default_salesman_id');
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function monthlyTargets(): HasMany
    {
        return $this->hasMany(SalesmanTarget::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(SalesmanFee::class);
    }

    /**
     * Customer yang direkrut salesman ini.
     */
    public function acquiredCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'acquired_by_salesman_id');
    }

    /**
     * Sales canvas yang sudah diverifikasi (dikonfirmasi atau sudah menjadi sales order).
     */
    public function verifiedSalesCanvases(): HasMany
    {
        return $this->salesCanvases()->whereIn('status', [
            SalesCanvas::STATUS_CONFIRMED,
            SalesCanvas::STATUS_SALES_ORDER,
        ]);
    }
}
