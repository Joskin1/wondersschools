<?php

namespace App\Providers;

use App\Listeners\CheckUnreadReleaseNotesOnLogin;
use App\Models\FrontendContent;
use App\Models\Setting;
use App\Observers\FrontendContentObserver;
use App\Observers\SettingObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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
        Event::listen(Login::class, CheckUnreadReleaseNotesOnLogin::class);
    }
}

