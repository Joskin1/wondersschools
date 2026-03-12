<?php

namespace App\Filament\Resources\TeacherSubjectAssignmentResource\Pages;

use App\Filament\Resources\TeacherSubjectAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTeacherSubjectAssignments extends ListRecords
{
    protected static string $resource = TeacherSubjectAssignmentResource::class;

    public ?int $classroomFilter = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Assign Teacher to Subject')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getTableFiltersFormWidth(): string
    {
        return '3xl';
    }

    public function updateClassroomFilter(?int $classroomId): void
    {
        $this->classroomFilter = $classroomId;
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        if ($this->classroomFilter) {
            $query->where('classroom_id', $this->classroomFilter);
        }

        return $query;
    }
}
