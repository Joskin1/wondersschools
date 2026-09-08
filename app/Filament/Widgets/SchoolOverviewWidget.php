<?php

namespace App\Filament\Widgets;

use App\Models\Subject;
use App\Models\Student;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Teachers', User::where('role', 'teacher')->count())
                ->description('Teaching staff')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('success'),
            Stat::make('Subjects', Subject::count())
                ->description('Subjects in the school')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('primary'),
            Stat::make('Students', Student::where('status', 'active')->count())
                ->description('Active students')
                ->descriptionIcon('heroicon-o-users')
                ->color('warning'),
        ];
    }
}
