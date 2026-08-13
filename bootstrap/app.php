<?php

use App\Http\Middleware\EnsureEmailIsVerifiedWhenRequired;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // cloudflared runs locally and forwards the original HTTPS request scheme.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        $middleware->validateCsrfTokens(except: [
            'payments/midtrans/webhook',
        ]);

        $middleware->redirectGuestsTo(fn (Request $request): string => $request->routeIs(
            'admin.*',
            'assets.inspections.*',
        ) ? route('admin.login') : route('login'));

        $middleware->redirectUsersTo(fn (Request $request): string => $request->user()?->isInternal()
            ? route('admin.dashboard')
            : route('home'));

        $middleware->alias([
            'verified.when_required' => EnsureEmailIsVerifiedWhenRequired::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
