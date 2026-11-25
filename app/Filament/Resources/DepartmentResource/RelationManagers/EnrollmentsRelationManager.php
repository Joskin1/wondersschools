<?php

declare(strict_types=1);

namespace App\Filament\Resources\DepartmentResource\RelationManagers;

use App\Filament\Resources\DepartmentResource;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Session;
use App\Models\StudentEnrollment;
use App\Services\FrontendLibrary;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Students in This Department';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->whereHas('student')
                ->with(['student.user', 'classroom', 'classArm', 'session']))
            ->columns([
                TextColumn::make('student.user.surname')
                    ->label('Student Name')
                    ->formatStateUsing(fn (StudentEnrollment $record): string => $record->student?->full_name ?? '—')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('student.user', function (Builder $q) use ($search): void {
                            $q->where('surname', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('other_name', 'like', "%{$search}%");
                        });
                    })
                    ->weight('bold'),

                TextColumn::make('student.admission_number')
                    ->label('Adm No')
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),

                TextColumn::make('classroom.name')
                    ->label('Classroom')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('classArm.name')
                    ->label('Arm')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->visible(fn (): bool => FrontendLibrary::getBooleanSetting('enable_class_arms', false)),

                TextColumn::make('session.name')
                    ->label('Session')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('session_id')
                    ->label('Session')
                    ->relationship('session', 'name')
                    ->default(fn () => Session::where('is_active', true)->value('id')),

                SelectFilter::make('classroom_id')
                    ->label('Classroom')
                    ->options(fn () => Classroom::where('has_departments', true)->orderBy('class_order')->pluck('name', 'id')),
            ])
            ->headerActions([
                DepartmentResource::makeAssignStudentsAction(
                    name: 'assign_students',
                    label: 'Add Students to Department',
                    resolvePresetDepartment: function (): Department {
                        /** @var Department $department */
                        $department = $this->getOwnerRecord();

                        return $department;
                    },
                ),
            ])
            ->actions([
                Action::make('remove_from_department')
                    ->label('Remove')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (): bool => Auth::user()?->canPerformAdminAction('departments', 'update') ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Remove Student from Department')
                    ->modalDescription(fn (StudentEnrollment $record): string => "Remove {$record->student?->full_name} from this department? Their classroom enrollment will be kept.")
                    ->action(function (StudentEnrollment $record): void {
                        abort_unless(Auth::user()?->canPerformAdminAction('departments', 'update') ?? false, 403);

                        $record->update(['department_id' => null]);

                        Notification::make()
                            ->title('Student Removed from Department')
                            ->body("{$record->student?->full_name} has been unassigned from this department.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('remove_selected_from_department')
                    ->label('Remove Selected from Department')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (): bool => Auth::user()?->canPerformAdminAction('departments', 'update') ?? false)
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        abort_unless(Auth::user()?->canPerformAdminAction('departments', 'update') ?? false, 403);

                        $count = StudentEnrollment::query()
                            ->whereIn('id', $records->pluck('id'))
                            ->update(['department_id' => null]);

                        Notification::make()
                            ->title('Students Removed from Department')
                            ->body("Removed {$count} student(s) from this department.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
