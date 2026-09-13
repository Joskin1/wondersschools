<?php

namespace App\Observers;

use App\Models\Setting;
use App\Services\FrontendLibrary;

class SettingObserver
{
    public function saved(Setting $setting): void
    {
        $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : null;
        FrontendLibrary::flush($tenantId);
    }

    public function deleted(Setting $setting): void
    {
        $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : null;
        FrontendLibrary::flush($tenantId);
    }
}
