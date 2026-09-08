<?php

namespace App\Filament\Resources\TeacherSubjectAssignmentResource\Pages;

use App\Filament\Resources\TeacherSubjectAssignmentResource;
use App\Models\TeacherSubjectAssignment;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTeacherSubjectAssignments extends ListRecords
{
    protected static string $resource = TeacherSubjectAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Assign Teacher to Subject')
                ->icon('heroicon-o-plus-circle'),
        ];
    }

    public function getTabs(): array
    {
        $pendingCount = TeacherSubjectAssignment::pending()->count();

        return [
            'all' => Tab::make('All Assignments'),
            'pending' => Tab::make('Pending Requests')
                ->badge($pendingCount > 0 ? (string) $pendingCount : null)
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            'approved' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            'rejected' => Tab::make('Rejected')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
        ];
    }
}

