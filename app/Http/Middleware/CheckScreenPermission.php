<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckScreenPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = $request->route();
        $name = $route?->getName();

        if (! $user || ! $name) {
            return $next($request);
        }

        // Screen permissions are for page access (GET/HEAD). Write actions are
        // authorized by button/db permissions in controllers or route middleware.
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $excluded = [
            'logout',
            'verification.notice',
            'verification.verify',
            'verification.send',
            'password.confirm',
            'password.update',
        ];

        if (in_array($name, $excluded, true)) {
            return $next($request);
        }

        $allowed = $user->can('screen.' . $name . '.view');

        if (! $allowed) {
            abort(403, 'You are not allowed to access this screen.');
        }

        return $next($request);
    }
}
