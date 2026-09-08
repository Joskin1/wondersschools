<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\LessonNote;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LessonNoteOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $teacherId = auth()->id();
        $query = LessonNote::forTeacher($teacherId)->active();

        $pending = (clone $query)->pending()->count();
        $approved = (clone $query)->approved()->count();
        $rejected = (clone $query)->rejected()->count();

        return [
            Stat::make('Pending Review', $pending)
                ->icon('heroicon-o-clock')
                ->color('warning'),
            Stat::make('Approved', $approved)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Needs Revision', $rejected)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
