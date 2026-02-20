<?php

namespace App\Filament\Sudo\Widgets;

use App\Models\Central\School;
use App\Models\Central\Domain;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Schools', School::count())
                ->description('Registered schools')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('primary'),

            Stat::make('Active Schools', School::active()->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Suspended Schools', School::suspended()->count())
                ->description('Access blocked')
                ->descriptionIcon('heroicon-o-pause-circle')
                ->color('danger'),

            Stat::make('Registered Domains', Domain::count())
                ->description('Total domain mappings')
                ->descriptionIcon('heroicon-o-globe-alt')
                ->color('warning'),
        ];
    }
}
