<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Mencatat create/update/delete/restore model ke activity_logs beserta nilai sebelum & sesudah.
 * Catatan: update massal lewat query builder (Model::query()->update()) tidak memicu event model.
 */
trait LogsActivity
{
    /** Kolom yang tidak perlu dicatat perubahannya. */
    protected static array $activityIgnored = ['created_at', 'updated_at', 'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    /** Kolom nomor/nama yang dipakai sebagai label dokumen, sesuai urutan prioritas. */
    protected static array $activityLabelColumns = [
        'code', 'order_no', 'invoice_no', 'delivery_no', 'adjustment_no', 'trf_no', 'opname_no',
        'pre_order_no', 'canvas_no', 'return_no', 'credit_note_no', 'sku', 'name', 'key',
    ];

    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => static::recordActivity($model, 'created', static::activityValues($model->getAttributes())));

        static::updated(function (Model $model) {
            $changes = [];
            foreach (static::activityValues($model->getChanges()) as $column => $new) {
                $changes[$column] = ['old' => $model->getOriginal($column), 'new' => $new];
            }

            if ($changes !== []) {
                static::recordActivity($model, 'updated', $changes);
            }
        });

        static::deleted(fn (Model $model) => static::recordActivity($model, 'deleted', null));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $model) => static::recordActivity($model, 'restored', null));
        }
    }

    private static function activityValues(array $attributes): array
    {
        return collect($attributes)->except(static::$activityIgnored)->all();
    }

    private static function recordActivity(Model $model, string $event, ?array $changes): void
    {
        $label = collect(static::$activityLabelColumns)
            ->map(fn (string $column) => $model->getAttribute($column))
            ->first(fn ($value) => filled($value));

        ActivityLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'subject_type' => class_basename($model),
            'subject_id' => $model->getKey() !== null && is_numeric($model->getKey()) ? (int) $model->getKey() : null,
            'subject_label' => $label !== null ? mb_substr((string) $label, 0, 255) : null,
            'changes' => $changes,
            'ip_address' => request()?->ip(),
        ]);
    }
}
