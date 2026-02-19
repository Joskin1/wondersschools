<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register middleware aliases for use in panel providers and routes
        $middleware->alias([
            'identify.tenant' => \App\Http\Middleware\IdentifyTenant::class,
            'central.only' => \App\Http\Middleware\CentralDomainOnly::class,
            'tenant.logging' => \App\Http\Middleware\TenantAwareLogging::class,
        ]);

        // Global middleware: tenant resolution + logging on all web requests
        // IdentifyTenant MUST be in the global stack so it runs on Filament panel routes too
        $middleware->web(append: [
            \App\Http\Middleware\IdentifyTenant::class,
            \App\Http\Middleware\TenantAwareLogging::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Custom error responses for tenant resolution failures
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            if ($e->getMessage() === 'School Not Found') {
                return response()->view('errors.tenant-not-found', [], 404);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() === 403 && $e->getMessage() === 'School Account Suspended') {
                return response()->view('errors.tenant-suspended', [], 403);
            }
            if ($e->getStatusCode() === 503 && $e->getMessage() === 'Service Temporarily Unavailable') {
                return response()->view('errors.tenant-unavailable', [], 503);
            }
        });
    })->create();

