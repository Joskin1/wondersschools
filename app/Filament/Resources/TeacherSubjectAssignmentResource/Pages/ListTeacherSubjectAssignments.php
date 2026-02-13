<?php

namespace App\Filament\Resources\TeacherSubjectAssignmentResource\Pages;

use App\Filament\Resources\TeacherSubjectAssignmentResource;
use App\Models\Classroom;
use App\Models\Session;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

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

    #[On('classroom-filter-updated')]
    public function updateClassroomFilter(?int $classroomId): void
    {
        $this->classroomFilter = $classroomId;
        $this->resetTable();
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        // Apply classroom filter if set
        if ($this->classroomFilter) {
            $query->where('classroom_id', $this->classroomFilter);
        }

        return $query;
    }

    public function getTableFiltersFormWidth(): string
    {
        return '2xl';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TeacherSubjectAssignmentResource\Widgets\ClassroomFilterWidget::class,
        ];
    }
}
