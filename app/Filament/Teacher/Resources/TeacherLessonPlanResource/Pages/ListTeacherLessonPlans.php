<?php

namespace App\Filament\Teacher\Resources\TeacherLessonPlanResource\Pages;

use App\Filament\Teacher\Resources\TeacherLessonPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeacherLessonPlans extends ListRecords
{
    protected static string $resource = TeacherLessonPlanResource::class;

    protected ?string $heading = 'My Lesson Plans';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Create Lesson Plan'),
        ];
    }
}
