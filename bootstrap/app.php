<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Public, read-only JSON for the website: no session, no cookies.
            Route::middleware(['api', 'throttle:60,1'])
                ->prefix('api/public')
                ->name('api.public.')
                ->group(base_path('routes/public.php'));

            // Admin API: same session cookie as the admin panel, so it runs inside
            // the "web" group (CSRF protection included) rather than "api".
            Route::middleware(['web', 'auth', 'admin', 'throttle:60,1'])
                ->prefix('api')
                ->name('api.')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // Signed-out visitors go to the Vue login page instead of a missing "login" route.
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();