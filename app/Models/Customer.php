<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'tax_number',
        'credit_limit',
        'payment_terms_days',
        'default_salesman_id',
        'acquired_by_salesman_id',
        'acquisition_fee_percent',
        'notes',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'integer',
            'payment_terms_days' => 'integer',
            'acquisition_fee_percent' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pics(): HasMany
    {
        return $this->hasMany(CustomerPic::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function defaultSalesman(): BelongsTo
    {
        return $this->belongsTo(Salesman::class, 'default_salesman_id')->withTrashed();
    }

    /**
     * Salesman yang merekrut customer ini (berhak fee dari seluruh penjualan customer).
     */
    public function acquiredBySalesman(): BelongsTo
    {
        return $this->belongsTo(Salesman::class, 'acquired_by_salesman_id')->withTrashed();
    }

    public function salesmanFees(): HasMany
    {
        return $this->hasMany(SalesmanFee::class);
    }

    public function salesCanvases(): HasMany
    {
        return $this->hasMany(SalesCanvas::class);
    }

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    /**
     * Faktur terkonfirmasi — dasar perhitungan piutang berjalan untuk plafon kredit.
     */
    public function confirmedSalesInvoices(): HasMany
    {
        return $this->salesInvoices()->where('status', SalesInvoice::STATUS_CONFIRMED);
    }

    public function primaryPic(): HasOne
    {
        return $this->hasOne(CustomerPic::class)->where('is_primary', true);
    }

    public function primaryAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class)->where('is_primary', true);
    }
}
