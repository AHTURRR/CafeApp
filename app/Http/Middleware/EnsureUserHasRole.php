<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC: pemakaian pada route  ->middleware('role:cashier,owner').
 * Role dibaca dari database (model User), bukan dari klaim di client atau token.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            throw new AuthenticationException();
        }

        // Role::from() melempar ValueError bila nama role salah ketik di definisi route (gagal cepat).
        $allowed = array_map(static fn (string $role) => Role::from($role), $roles);

        if (! $user->hasRole(...$allowed)) {
            throw new AuthorizationException();
        }

        return $next($request);
    }
}
