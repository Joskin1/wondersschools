<?php

namespace App\Filament\Teacher\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MyClassroomsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        
        if (!$user->isTeacher() || !$user->staff) {
            return [];
        }

        $session = now()->year . '/' . (now()->year + 1);
        
        // Get unique classrooms count
        $classroomsCount = DB::table('classroom_subject_teacher')
            ->where('staff_id', $user->staff->id)
            ->where('session', $session)
            ->distinct('classroom_id')
            ->count('classroom_id');
        
        // Get unique subjects count
        $subjectsCount = DB::table('classroom_subject_teacher')
            ->where('staff_id', $user->staff->id)
            ->where('session', $session)
            ->distinct('subject_id')
            ->count('subject_id');
        
        // Get total students in assigned classrooms
        $classroomIds = DB::table('classroom_subject_teacher')
            ->where('staff_id', $user->staff->id)
            ->where('session', $session)
            ->distinct()
            ->pluck('classroom_id');
        
        $studentsCount = DB::table('classroom_student')
            ->whereIn('classroom_id', $classroomIds)
            ->distinct('student_id')
            ->count('student_id');

        return [
            Stat::make('My Classrooms', $classroomsCount)
                ->description('Classrooms I teach')
                ->descriptionIcon('heroicon-o-building-office-2')
                ->color('success'),
            Stat::make('My Subjects', $subjectsCount)
                ->description('Subjects I teach')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('info'),
            Stat::make('My Students', $studentsCount)
                ->description('Total students in my classes')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('warning'),
        ];
    }
}
