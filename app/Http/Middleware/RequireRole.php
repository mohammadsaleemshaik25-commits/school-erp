<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->relationLoaded('role')) {
            $user?->load('role');
        }

        $currentRole = strtoupper((string) optional($user?->role)->role_name);
        $allowedRoles = array_map(static fn (string $role) => strtoupper($role), $roles);

        if (! in_array($currentRole, $allowedRoles, true)) {
            abort(403, 'You do not have permission to access this section.');
        }

        return $next($request);
    }
}
