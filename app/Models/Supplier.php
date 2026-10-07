<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Supplier extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'suppliers';

    protected $fillable = [
        'code',
        'name',
        'address',
        'contact',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Pemasok yang sudah dipakai di Pesanan Pembelian tidak boleh dihapus,
     * hanya bisa dinonaktifkan.
     */
    public function hasTransactions(): bool
    {
        return in_array($this->id, self::idsWithTransactions([$this->id]), true);
    }

    /**
     * Dari daftar id pemasok, kembalikan id yang sudah punya Pesanan Pembelian
     * (termasuk PO yang sudah terhapus).
     *
     * @param  array<int>  $ids
     * @return array<int>
     */
    public static function idsWithTransactions(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table('purchase_orders')
            ->whereIn('supplier_id', array_map('intval', $ids))
            ->distinct()
            ->pluck('supplier_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'supplier_id');
    }

    public function purchaseInvoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    public function apPayments(): HasMany
    {
        return $this->hasMany(APPayment::class, 'supplier_id');
    }
}
