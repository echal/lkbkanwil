<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\CheckRole;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CheckRole::class,
        ]);

        // Prevent redirect to 'login' route for unauthenticated API requests
        $middleware->redirectGuestsTo(function ($request) {
            // For API routes, don't redirect - let exception handler deal with it
            if ($request->is('api/*')) {
                return null;
            }
            // For web routes (if any), redirect to login
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force API routes to ALWAYS return JSON (no redirect)
        $exceptions->shouldRenderJsonWhen(function ($request, $exception) {
            // Selalu return JSON untuk route yang dimulai dengan /api
            if ($request->is('api/*')) {
                return true;
            }

            // Atau jika request memiliki Accept: application/json header
            return $request->expectsJson();
        });

        // Handle unauthenticated exception untuk API
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Token tidak valid atau sudah kadaluarsa.'
                ], 401);
            }
        });

        // Handle all exceptions for API routes to prevent redirect
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($request->is('api/*')) {
                // Prevent "Route [login] not defined" error
                if ($e instanceof \Symfony\Component\Routing\Exception\RouteNotFoundException) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthenticated. Please login first.'
                    ], 401);
                }
            }
        });
    })
    ->create();
