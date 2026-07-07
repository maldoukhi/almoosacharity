<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    // Event auto-discovery would register every Listeners/* class a second
    // time purely from its handle() type-hint, double-firing events that
    // are also wired explicitly via Event::listen() in AppServiceProvider
    // (see Notifications, Phase 5). Wiring stays explicit and centralized
    // there instead.
    ->withEvents(discover: false)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // routes/public.php (phase 6b): unauthenticated, signed-URL-only
        // routes (the beneficiary delivery-confirmation link) that must
        // never share the authenticated app's route file.
        then: function (): void {
            require __DIR__.'/../routes/public.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'active' => EnsureUserIsActive::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Phase 6b: the public.confirm route's 'signed' middleware rejects
        // a genuinely time-expired confirmation link with Laravel's bare
        // InvalidSignatureException (403) before the ConfirmReceipt
        // Livewire component ever mounts. Show the same friendly "link
        // expired" copy the component itself would for that route instead
        // of the framework's generic error page.
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            if ($request->routeIs('public.confirm')) {
                return response()->view('public.expired', [], 403);
            }
        });
    })->create();
