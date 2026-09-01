<?php

namespace App\Services;

class BrandingService
{
    public function __construct(
        protected TenantBrandingService $tenantBrandingService
    ) {}

    public function getAppName(): string
    {
        $resolved = $this->tenantBrandingService->resolve(request()?->getHost() ?? '');
        return $resolved['name'] ?? config('app.name');
    }

    public function getPrimaryColor(): string
    {
        $resolved = $this->tenantBrandingService->resolve(request()?->getHost() ?? '');
        $color = $resolved['color'] ?? null;
        if (is_array($color)) {
            return $color['500'] ?? '#4f46e5';
        }
        return is_string($color) ? $color : '#4f46e5';
    }
}
