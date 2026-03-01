<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDatabaseIsSelected
{
    public function handle(Request $request, Closure $next): Response
    {
        // If static API token is configured, host is resolved automatically — skip session check
        if (config('accurate.api_token')) {
            return $next($request);
        }

        // Kondisi: Jika pengguna sudah login TAPI belum memilih database
        if (auth()->check() && ! session()->has('accurate_database')) {

            // Pengecualian: Biarkan pengguna mengakses halaman pemilihan database, form-nya, dan proses logout
            if (! $request->routeIs('database.selection') && ! $request->routeIs('database.select') && ! $request->routeIs('logout') && ! $request->routeIs('settings.accurate') && ! $request->routeIs('accurate.auth')) {

                // Jika tidak, paksa alihkan ke halaman pemilihan database
                return redirect()->route('database.selection');
            }
        }

        return $next($request);
    }
}
