<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak akses halaman modul bila peran pengguna tidak memiliki izin modul tersebut.
 * Kunci izin diturunkan dari nama route (lihat config/permissions.php).
 */
class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $permission = self::permissionForRoute($request->route()?->getName());

        if ($permission !== null && ! $request->user()?->canAccessModule($permission)) {
            abort(403, 'Anda tidak memiliki akses ke modul ini.');
        }

        return $next($request);
    }

    /**
     * Kunci izin modul untuk sebuah nama route, atau null bila route bukan halaman modul
     * (mis. dashboard, settings) sehingga tidak dibatasi.
     */
    public static function permissionForRoute(?string $routeName): ?string
    {
        if (! $routeName || $routeName === 'dashboard') {
            return null;
        }

        foreach (config('permissions.route_suffixes', []) as $suffix) {
            if (str_ends_with($routeName, $suffix)) {
                $routeName = substr($routeName, 0, -strlen($suffix));
                break;
            }
        }

        $routeName = config('permissions.route_aliases')[$routeName] ?? $routeName;

        return in_array($routeName, self::modulePermissions(), true) ? $routeName : null;
    }

    /**
     * Semua kunci izin akses halaman (tanpa grup Otorisasi).
     *
     * @return list<string>
     */
    private static function modulePermissions(): array
    {
        return collect(config('permissions.groups'))
            ->reject(fn (array $permissions, string $group) => str_ends_with($group, ' - Otorisasi'))
            ->flatMap(fn (array $permissions) => array_keys($permissions))
            ->values()
            ->all();
    }
}
