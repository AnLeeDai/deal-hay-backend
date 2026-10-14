<?php

use App\Http\Middleware\AddRequestContext;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdministrator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')->group(__DIR__.'/../routes/health.php');
        },
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php', [
        'middleware' => ['auth:sanctum', 'active', 'throttle:broadcast-auth'],
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn () => route('api.auth.login'));

        $middleware->append(AddRequestContext::class);
        $middleware->append(AddSecurityHeaders::class);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'admin' => EnsureUserIsAdministrator::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportDuplicates();
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (Throwable $throwable, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            if ($throwable instanceof ValidationException
                || $throwable instanceof AuthenticationException
                || $throwable instanceof AuthorizationException
                || $throwable instanceof ModelNotFoundException
                || $throwable instanceof HttpExceptionInterface) {
                return null;
            }

            report($throwable);

            return response()->json([
                'message' => 'Hệ thống chưa thể xử lý yêu cầu. Vui lòng thử lại sau.',
            ], 500);
        });
    })->create();
