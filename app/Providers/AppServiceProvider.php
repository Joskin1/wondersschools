<?php

namespace App\Providers;

use App\Models\FrontendContent;
use App\Models\Setting;
use App\Observers\FrontendContentObserver;
use App\Observers\SettingObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FrontendContent::observe(FrontendContentObserver::class);
        Setting::observe(SettingObserver::class);
    }
}
