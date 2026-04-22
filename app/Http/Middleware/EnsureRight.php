<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureRight
{
    public function handle(Request $request, Closure $next, string $right): Response
    {
        $user = $request->user();
        if (! $user) {
            throw new AccessDeniedHttpException('Unauthenticated.');
        }

        if (! method_exists($user, 'hasRight') || ! $user->hasRight($right)) {
            throw new AccessDeniedHttpException('Right required: '.$right);
        }

        return $next($request);
    }
}
