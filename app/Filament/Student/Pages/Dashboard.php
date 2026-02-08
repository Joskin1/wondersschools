<?php

namespace App\Filament\Student\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/';
    
    protected static ?string $title = 'Student Dashboard';
    
    public function getWidgets(): array
    {
        return [
            \App\Filament\Student\Widgets\MyClassroomWidget::class,
            \App\Filament\Student\Widgets\RecentScoresWidget::class,
        ];
    }
}
