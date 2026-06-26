<?php

use App\Http\Middleware\CheckRuleAccess;
use App\Http\Middleware\CheckScreenPermission;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
                ? __('This change conflicts with existing data. Please review the selected records and try again.')
                : __('The record could not be saved right now. Please review your input and try again.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], $sqlState === '23000' ? 409 : 422);
            }

            return back()
                ->withInput($request->except(['api_key', 'current_password', 'password', 'password_confirmation', 'token']))
                ->withErrors(['database' => $message]);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            $message = __('Please sign in to continue.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 401);
            }

            return redirect()->guest(route('login'))->withErrors(['auth' => $message]);
        });

        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            $message = __('Your session expired. Please try again.');

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 419);
            }

            return back()
                ->withInput($request->except(['api_key', 'current_password', 'password', 'password_confirmation', 'token']))
                ->withErrors(['session' => $message]);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            $status = $exception->getStatusCode();
            $messages = [
                403 => [
                    'title' => __('Access not allowed'),
                    'message' => in_array($request->method(), ['GET', 'HEAD'], true)
                        ? __('You do not have permission to access this page.')
                        : __('You do not have permission to perform this action.'),
                ],
                404 => ['title' => __('Information not found'), 'message' => __('The selected information was not found.')],
                405 => ['title' => __('Action not available'), 'message' => __('This action is not available from this page.')],
                422 => ['title' => __('Action could not be completed'), 'message' => $exception->getMessage() ?: __('Please review the information and try again.')],
                429 => ['title' => __('Too many attempts'), 'message' => __('Please wait a moment before trying again.')],
            ];

            if (! isset($messages[$status])) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => $messages[$status]['message']], $status);
            }

            return response()->view('errors.friendly', [
                'title' => $messages[$status]['title'],
                'message' => $messages[$status]['message'],
                'status' => $status,
            ], $status);
        });
    })->create();
