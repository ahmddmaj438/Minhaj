<?php

use App\Http\Middleware\CheckRuleAccess;
use App\Http\Middleware\CheckScreenPermission;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'rule' => CheckRuleAccess::class,
            'screen' => CheckScreenPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash([
            'api_key',
            'current_password',
            'password',
            'password_confirmation',
            'token',
        ]);

        $exceptions->report(function (QueryException $exception): bool {
            Log::warning('Database operation failed.', [
                'connection' => $exception->getConnectionName(),
                'sql_state' => $exception->errorInfo[0] ?? null,
                'driver_code' => $exception->errorInfo[1] ?? null,
                'route' => request()->route()?->getName(),
                'user_id' => request()->user()?->id,
            ]);

            return false;
        });

        $exceptions->render(function (QueryException $exception, Request $request) {
            if (in_array($request->method(), ['GET', 'HEAD'], true)) {
                return null;
            }

            $sqlState = $exception->errorInfo[0] ?? null;
            $message = $sqlState === '23000'
                ? 'This change conflicts with existing data. Please review the selected records and try again.'
                : 'The record could not be saved right now. Please review your input and try again.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], $sqlState === '23000' ? 409 : 422);
            }

            return back()
                ->withInput($request->except(['api_key', 'current_password', 'password', 'password_confirmation', 'token']))
                ->withErrors(['database' => $message]);
        });
    })->create();
