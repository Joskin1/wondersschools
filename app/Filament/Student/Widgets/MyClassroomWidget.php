<?php

namespace App\Filament\Student\Widgets;

use Filament\Widgets\Widget;

class MyClassroomWidget extends Widget
{
    protected string $view = 'filament.student.widgets.my-classroom-widget';
    
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $student = auth('student')->user();
        
        if (!$student) {
            return [
                'classroom' => null,
                'studentCount' => 0,
            ];
        }

        $classroom = $student->classrooms()->first();
        
        $studentCount = $classroom ? $classroom->students()->count() : 0;

        return [
            'classroom' => $classroom,
            'studentCount' => $studentCount,
        ];
    }
}
