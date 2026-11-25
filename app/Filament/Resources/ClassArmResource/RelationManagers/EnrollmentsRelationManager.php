<?php

declare(strict_types=1);

namespace App\Filament\Resources\ClassArmResource\RelationManagers;

use App\Filament\Resources\ClassArmResource;
use App\Models\ClassArm;
use App\Models\Session;
use App\Models\StudentEnrollment;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Students in This Arm';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->whereHas('student')
                ->with(['student.user', 'department', 'session']))
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

                TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                TextColumn::make('session.name')
                    ->label('Session')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('session_id')
                    ->label('Session')
                    ->relationship('session', 'name')
                    ->default(fn () => Session::where('is_active', true)->value('id')),
            ])
            ->headerActions([
                Action::make('assign_students')
                    ->label('Add Students to Arm')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()?->canPerformAdminAction('class_arms', 'update') ?? false)
                    ->modalHeading(function (): string {
                        /** @var ClassArm $arm */
                        $arm = $this->getOwnerRecord();

                        return "Assign Students to {$arm->full_name}";
                    })
                    ->modalDescription('Select one or more students from this classroom to add them to this arm at once.')
                    ->modalWidth('4xl')
                    ->modalSubmitActionLabel('Assign Selected Students')
                    ->form(function (): array {
                        /** @var ClassArm $arm */
                        $arm = $this->getOwnerRecord();

                        return ClassArmResource::assignStudentsFormSchema($arm);
                    })
                    ->action(function (array $data): void {
                        /** @var ClassArm $arm */
                        $arm = $this->getOwnerRecord();
                        ClassArmResource::handleAssignStudents($data, $arm);
                    }),
            ])
            ->actions([
                Action::make('remove_from_arm')
                    ->label('Remove')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->canPerformAdminAction('class_arms', 'update') ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Remove Student from Arm')
                    ->modalDescription(fn (StudentEnrollment $record): string => "Remove {$record->student?->full_name} from this arm? Their classroom enrollment will be kept.")
                    ->action(function (StudentEnrollment $record): void {
                        abort_unless(auth()->user()?->canPerformAdminAction('class_arms', 'update') ?? false, 403);

                        $record->update(['class_arm_id' => null]);

                        Notification::make()
                            ->title('Student Removed from Arm')
                            ->body("{$record->student?->full_name} has been unassigned from this arm.")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('remove_selected_from_arm')
                    ->label('Remove Selected from Arm')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (): bool => auth()->user()?->canPerformAdminAction('class_arms', 'update') ?? false)
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        abort_unless(auth()->user()?->canPerformAdminAction('class_arms', 'update') ?? false, 403);

                        $count = StudentEnrollment::query()
                            ->whereIn('id', $records->pluck('id'))
                            ->update(['class_arm_id' => null]);

                        Notification::make()
                            ->title('Students Removed from Arm')
                            ->body("Removed {$count} student(s) from this arm.")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
