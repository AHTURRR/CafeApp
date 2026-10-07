<?php

namespace App\Http\Middleware;

use App\Exceptions\AccountInactiveException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menolak akun nonaktif pada SETIAP request, sehingga penonaktifan berlaku segera
 * walau token masih belum kedaluwarsa.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            throw new AccountInactiveException();
        }

        return $next($request);
    }
}
