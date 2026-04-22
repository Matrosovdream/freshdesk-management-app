<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectManagerScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('manager')) {
            $request->attributes->set('assigned_group_ids', $this->groupIdsForManager($user));
        }

        return $next($request);
    }

    private function groupIdsForManager($user): array
    {
        if (method_exists($user, 'managerGroupScopes')) {
            return $user->managerGroupScopes()->pluck('group_id')->all();
        }

        return [];
    }
}
