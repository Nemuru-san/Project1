<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceWeeklyLogin
{
    public const SESSION_KEY = 'authenticated_week_started_at';

    public const EXPIRED_MESSAGE = 'Sesi mingguan telah berakhir. Silakan login kembali.';

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        // Siklus mingguan dimulai Minggu 00:00 waktu lokal (default WIB), bukan UTC aplikasi.
        $currentWeek = now(config('session.weekly_reset_timezone'))
            ->startOfWeek(Carbon::SUNDAY)
            ->toDateString();
        $authenticatedWeek = $request->session()->get(self::SESSION_KEY);

        if ($authenticatedWeek === null) {
            $request->session()->put(self::SESSION_KEY, $currentWeek);

            return $next($request);
        }

        if (hash_equals($currentWeek, (string) $authenticatedWeek)) {
            return $next($request);
        }

        Auth::guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Penanda di URL supaya pesan tetap tampil saat logout terjadi lewat request Livewire,
        // karena flash session ikut terpakai oleh request AJAX sebelum halaman login dibuka.
        return redirect()->route('login', ['expired' => 1])->with('status', self::EXPIRED_MESSAGE);
    }
}
