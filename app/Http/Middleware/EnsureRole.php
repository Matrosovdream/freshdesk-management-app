<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        if (! $user) {
            throw new AccessDeniedHttpException('Unauthenticated.');
        }

        $allowed = explode('|', $roles);
        foreach ($allowed as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return $next($request);
            }
        }

        throw new AccessDeniedHttpException('Role required: '.$roles);
    }
}
