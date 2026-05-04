<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRuleAccess
{
    public function handle(Request $request, Closure $next, string $resource, string $action): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($resource . '_' . $action)) {
            abort(403, 'You are not allowed to perform this action.');
        }

        return $next($request);
    }
}
