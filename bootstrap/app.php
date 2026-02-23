<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Prepend tenancy initialization to the web group so it runs for
        // EVERY web request — including Livewire's global /livewire/update
        // endpoint — before EncryptCookies / StartSession / Auth.
        // Without this, Livewire form submissions (login, any action) never
        // trigger InitializeTenancyByDomain, so auth always queries the
        // landlord DB instead of the tenant DB.
        $middleware->web(prepend: [
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
