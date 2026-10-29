<?php

declare(strict_types=1);

namespace App\Filament\Resources\DesktopDeviceResource\Pages;

use App\Filament\Resources\DesktopDeviceResource;
use Filament\Resources\Pages\ListRecords;

class ListDesktopDevices extends ListRecords
{
    protected static string $resource = DesktopDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
