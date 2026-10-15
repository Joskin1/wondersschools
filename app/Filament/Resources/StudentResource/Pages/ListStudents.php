<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Classroom;
use App\Models\Student;
use Filament\Actions\Action;
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
            Action::make('copy_student_registration_link')
                ->label('Copy Registration Link')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->modalHeading('Public Student Registration Link')
                ->modalDescription('Share this link with parents or prospective students. Anyone using this link can complete full student registration online.')
                ->modalContent(function () {
                    $link = route('public.student.register');
                    return view('filament.components.copy-student-link-modal', ['link' => $link]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),

            Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => ImportStudents::downloadTemplate()),

            Action::make('bulk_import')
                ->label('Bulk Import')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(StudentResource::getUrl('import')),

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
