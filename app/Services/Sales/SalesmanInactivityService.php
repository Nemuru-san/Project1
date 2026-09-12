<?php

namespace App\Services\Sales;

use App\Models\Salesman;
use App\Models\User;
use App\Notifications\SalesmanDeactivated;
use App\Notifications\SalesmanInactivityWarning;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SalesmanInactivityService
{
    /** Lama tidak ada sales canvas terverifikasi sebelum akun dinonaktifkan. */
    public const INACTIVE_MONTHS = 3;

    /** Berapa hari sebelum nonaktif notifikasi peringatan dikirim. */
    public const WARNING_DAYS = 14;

    /**
     * Periksa semua salesman aktif, kirim peringatan / nonaktifkan bila perlu.
     *
     * @return array{warned: int, deactivated: int}
     */
    public function run(?CarbonImmutable $today = null): array
    {
        $today = ($today ?? CarbonImmutable::now())->startOfDay();
        $result = ['warned' => 0, 'deactivated' => 0];

        Salesman::query()
            ->where('is_active', true)
            ->with('user')
            ->withMax('verifiedSalesCanvases as last_verified_canvas_date', 'date')
            ->each(function (Salesman $salesman) use ($today, &$result): void {
                $lastActivity = $this->lastActivityDate($salesman);
                $deadline = $lastActivity->addMonths(self::INACTIVE_MONTHS)->startOfDay();
                $warningDate = $deadline->subDays(self::WARNING_DAYS);

                if ($today->greaterThanOrEqualTo($deadline)) {
                    $this->deactivate($salesman, $lastActivity, $deadline);
                    $result['deactivated']++;

                    return;
                }

                // Peringatan hanya dikirim sekali per periode inaktivitas: bila belum pernah,
                // atau peringatan sebelumnya dikirim sebelum aktivitas terakhir (sudah reset).
                $alreadyWarned = $salesman->inactivity_warned_at !== null
                    && $salesman->inactivity_warned_at->greaterThanOrEqualTo($lastActivity);

                if ($today->greaterThanOrEqualTo($warningDate) && ! $alreadyWarned) {
                    $this->warn($salesman, $lastActivity, $deadline);
                    $result['warned']++;
                }
            });

        return $result;
    }

    /**
     * Titik acuan aktivitas terakhir: sales canvas terverifikasi terakhir, atau checkpoint
     * (tanggal dibuat / diaktifkan kembali) bila belum pernah ada canvas.
     */
    public function lastActivityDate(Salesman $salesman): CarbonImmutable
    {
        $lastCanvas = $salesman->last_verified_canvas_date
            ?? $salesman->verifiedSalesCanvases()->max('date');

        $checkpoint = $salesman->activity_checkpoint_at ?? $salesman->created_at;

        $dates = collect([$lastCanvas, $checkpoint])
            ->filter()
            ->map(fn ($d) => CarbonImmutable::parse($d)->startOfDay());

        return $dates->max() ?? CarbonImmutable::now()->startOfDay();
    }

    /**
     * Tanggal rencana nonaktif untuk ditampilkan di UI.
     */
    public function deactivationDate(Salesman $salesman): CarbonImmutable
    {
        return $this->lastActivityDate($salesman)->addMonths(self::INACTIVE_MONTHS);
    }

    private function warn(Salesman $salesman, CarbonImmutable $lastActivity, CarbonImmutable $deadline): void
    {
        $salesman->forceFill(['inactivity_warned_at' => now()])->save();

        $notification = new SalesmanInactivityWarning($salesman, $lastActivity, $deadline);

        $recipients = $this->owners();
        if ($salesman->user) {
            $recipients->push($salesman->user);
        }

        Notification::send($recipients->unique('id'), $notification);
    }

    private function deactivate(Salesman $salesman, CarbonImmutable $lastActivity, CarbonImmutable $deadline): void
    {
        DB::transaction(function () use ($salesman): void {
            $salesman->forceFill([
                'is_active' => false,
                'deactivated_at' => now(),
            ])->save();

            // Konsisten dengan master salesman: akun login di-soft-delete agar tidak bisa login.
            $salesman->user?->delete();
        });

        Notification::send(
            $this->owners(),
            new SalesmanDeactivated($salesman, $lastActivity, $deadline)
        );
    }

    /**
     * Pengguna dengan peran Owner; bila belum ada, fallback ke Super Admin.
     *
     * @return Collection<int, User>
     */
    private function owners(): Collection
    {
        $owners = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'Owner'))
            ->get();

        if ($owners->isEmpty()) {
            $owners = User::query()
                ->whereHas('role', fn ($q) => $q->where('name', 'Super Admin'))
                ->get();
        }

        return $owners;
    }
}
