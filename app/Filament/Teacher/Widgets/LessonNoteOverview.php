<?php

namespace App\Filament\Teacher\Widgets;

use App\Models\LessonNote;
use App\Models\Session;
use App\Models\SubmissionWindow;
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

        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;
        $openWindows = 0;

        if ($activeSession && $activeTerm) {
            $openWindows = SubmissionWindow::where('session_id', $activeSession->id)
                ->where('term_id', $activeTerm->id)
                ->currentlyOpen()
                ->count();
        }

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
            Stat::make('Open Submission Windows', $openWindows)
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),
        ];
    }
}
