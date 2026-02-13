<?php

namespace App\Filament\Widgets;

use App\Models\LessonNote;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingLessonNotesWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pendingCount = LessonNote::where('status', 'pending')->count();

        return [
            Stat::make('Pending Lesson Notes', $pendingCount)
                ->description('Awaiting your review')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.lesson-notes.index')),
        ];
    }
}
