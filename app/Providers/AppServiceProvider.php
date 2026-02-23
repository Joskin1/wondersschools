<?php

namespace App\Providers;

use App\Services\FrontendContentService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // FrontendContentService is a per-request singleton.
        // It loads ALL tenant settings in one query on first access,
        // then caches them for the lifetime of the request.
        $this->app->singleton(FrontendContentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Inject $site into the frontend layout so nav, footer, and
        // meta tags can read settings without extra queries.
        View::composer('components.layouts.app', function ($view) {
            $view->with('site', $this->app->make(FrontendContentService::class));
        });
    }
}
