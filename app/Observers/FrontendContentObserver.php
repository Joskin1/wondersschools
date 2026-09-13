<?php

namespace App\Observers;

use App\Models\FrontendContent;
use App\Services\FrontendLibrary;

class FrontendContentObserver
{
    public function saved(FrontendContent $content): void
    {
        $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : null;
        FrontendLibrary::flush($tenantId);
    }

    public function deleted(FrontendContent $content): void
    {
        $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : null;
        FrontendLibrary::flush($tenantId);
    }
}
