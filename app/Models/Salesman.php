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
        'default_customer_id',
        'default_customer_address_id',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function defaultCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'default_customer_id');
    }

    public function defaultCustomerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'default_customer_address_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function salesCanvases(): HasMany
    {
        return $this->hasMany(SalesCanvas::class);
    }
}
