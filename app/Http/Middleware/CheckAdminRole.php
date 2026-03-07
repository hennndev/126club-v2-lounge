<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Route name patterns accessible per role.
     * Administrators bypass all checks.
     *
     * @var array<string, array<string>>
     */
    private const ROLE_ACCESS = [
        'Manager' => [
            'admin.dashboard',
            'admin.tables.*',
            'admin.areas.*',
            'admin.bookings.*',
            'admin.pos.*',
            'admin.printer.*',
            'admin.transaction-history.*',
            'admin.transaction-checker.*',
            'admin.kitchen.*',
            'admin.bar.*',
            'admin.inventory.*',
            'admin.bom.*',
            'admin.stock-opname.*',
            'admin.customers.*',
            'admin.customer-keep.*',
            'admin.rewards.*',
            'admin.song-requests.*',
            'admin.display-messages.*',
            'admin.events.*',
            'admin.waiter-performance.*',
            'admin.settings.*',
            'admin.accurate.*',
            'admin.sync.*',
        ],
        'Cashier' => [
            'admin.dashboard',
            'admin.pos.*',
            'admin.printer.*',
            'admin.bookings.*',
            'admin.transaction-history.*',
            'admin.transaction-checker.*',
            'admin.customers.*',
            'admin.customer-keep.*',
            'admin.rewards.*',
            'admin.inventory.*',
            'admin.bom.*',
            'admin.accurate.*',
            'admin.sync.*',
            'admin.settings.daily-auth-code.verify',        ],
        'DJ' => [
            'admin.dashboard',
            'admin.song-requests.*',
            'admin.display-messages.*',
            'admin.events.*',
        ],
        'Kitchen' => [
            'admin.dashboard',
            'admin.kitchen.*',
            'admin.inventory.*',
            'admin.bom.*',
            'admin.stock-opname.*',
            'admin.sync.*',
        ],
        'Bar' => [
            'admin.dashboard',
            'admin.bar.*',
            'admin.inventory.*',
            'admin.bom.*',
            'admin.sync.*',
        ],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        // Administrators have unrestricted access
        if ($user->hasRole('Administrator')) {
            return $next($request);
        }

        $currentRoute = $request->route()?->getName();

        if (! $currentRoute) {
            return $next($request);
        }

        foreach (self::ROLE_ACCESS as $role => $patterns) {
            if ($user->hasRole($role)) {
                foreach ($patterns as $pattern) {
                    if (fnmatch($pattern, $currentRoute)) {
                        return $next($request);
                    }
                }

                abort(403, 'Akses ditolak.');
            }
        }

        abort(403, 'Akses ditolak.');
    }
}
