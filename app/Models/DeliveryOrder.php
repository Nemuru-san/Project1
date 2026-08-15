<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryOrder extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SHIPPED = 'shipped';

    public const STATUS_CANCELLED = 'cancelled';

    /** @deprecated Status lama, dipertahankan agar data/kode lama tetap terbaca. */
    public const STATUS_ISSUED = self::STATUS_DRAFT;

    protected $fillable = [
        'delivery_no', 'delivery_date', 'sales_order_id', 'customer_id',
        'customer_address_id', 'notes', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['delivery_date' => 'date'];
    }

    /**
     * Surat Jalan beserta relasi yang dibutuhkan halaman cetak.
     */
    public static function forPrint(int|string $id): self
    {
        return static::with([
            'salesOrder.salesman', 'salesOrder.preOrder', 'salesOrder.salesCanvas',
            'customer.primaryAddress', 'customerAddress', 'creator',
            'items.product', 'items.warehouse', 'items.unit',
        ])->findOrFail($id);
    }

    /**
     * Super admin, pembuat dokumen, dan salesman pemilik kanvas asal boleh melihat/mencetak.
     */
    public function isAccessibleBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->isSuperAdmin() || $this->created_by === $user->id) {
            return true;
        }

        $salesmanId = $user->salesman()->where('is_active', true)->value('id');

        return (bool) $this->salesOrder?->salesCanvas
            && $this->salesOrder->salesCanvas->salesman_id === $salesmanId;
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }
}
