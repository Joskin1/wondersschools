<?php

namespace App\Filament\Widgets;

use App\Models\Classroom;
use App\Models\Session;
use App\Models\StudentEnrollment;
use Filament\Widgets\ChartWidget;

class StudentsByClassWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Students by Class';

    protected ?string $description = 'Active student enrolments for the current academic session.';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $session = Session::active()->first();
        $classrooms = Classroom::active()->ordered()->get(['id', 'name']);

        if (! $session || $classrooms->isEmpty()) {
            return [
                'datasets' => [['label' => 'Students', 'data' => []]],
                'labels' => [],
            ];
        }

        $counts = StudentEnrollment::query()
            ->where('session_id', $session->id)
            ->whereHas('student', fn ($query) => $query->where('status', 'active'))
            ->selectRaw('classroom_id, COUNT(DISTINCT student_id) as student_count')
            ->groupBy('classroom_id')
            ->pluck('student_count', 'classroom_id');

        return [
            'datasets' => [[
                'label' => 'Students',
                'data' => $classrooms->map(fn (Classroom $classroom): int => (int) ($counts[$classroom->id] ?? 0))->all(),
                'backgroundColor' => '#f59e0b',
                'borderColor' => '#d97706',
                'borderWidth' => 1,
                'borderRadius' => 4,
            ]],
            'labels' => $classrooms->pluck('name')->all(),
        ];
    }
}
