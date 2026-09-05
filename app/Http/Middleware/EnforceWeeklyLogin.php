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

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $currentWeek = now()->startOfWeek(Carbon::SUNDAY)->toDateString();
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

        return redirect()->route('login')->with(
            'status',
            'Sesi mingguan telah berakhir. Silakan login kembali.',
        );
    }
}
