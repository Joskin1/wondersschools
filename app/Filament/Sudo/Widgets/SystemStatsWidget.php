<?php

namespace App\Filament\Sudo\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('All users in system')
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),

            Stat::make('Active Teachers', User::where('role', 'teacher')->where('is_active', true)->count())
                ->description('Currently active')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('primary'),

            Stat::make('Administrators', User::whereIn('role', ['admin', 'sudo'])->count())
                ->description('Admin & Sudo users')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('warning'),
        ];
    }
}
