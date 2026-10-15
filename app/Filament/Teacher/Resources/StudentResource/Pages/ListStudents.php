<?php

namespace App\Filament\Teacher\Resources\StudentResource\Pages;

use App\Filament\Teacher\Resources\StudentResource;
use App\Models\Classroom;
use App\Models\Student;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('All Students')
                ->badge(fn () => Student::count()),
        ];

        $classrooms = Classroom::query()->active()->ordered()->get();
        if ($classrooms->isEmpty()) {
            $classrooms = Classroom::query()->ordered()->get();
        }

        foreach ($classrooms as $classroom) {
            $tabs['class_' . $classroom->id] = Tab::make($classroom->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('enrollments', fn ($q) => $q->where('classroom_id', $classroom->id)))
                ->badge(fn () => Student::whereHas('enrollments', fn ($q) => $q->where('classroom_id', $classroom->id))->count());
        }

        return $tabs;
    }
}
