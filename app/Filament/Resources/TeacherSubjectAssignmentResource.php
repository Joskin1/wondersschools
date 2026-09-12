<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherSubjectAssignmentResource\Pages;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Session;
use App\Models\Term;
use App\Services\LessonNoteCache;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class TeacherSubjectAssignmentResource extends Resource
{
    protected static ?string $model = TeacherSubjectAssignment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-plus';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Subject Teachers';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        return $schema
            ->components([
                Select::make('teacher_id')
                    ->label('Teacher')
                    ->options(User::activeTeachers()->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Only active teachers who have completed registration are shown'),

                Select::make('subject_id')
                    ->label('Subject')
                    ->options(Subject::orderBy('name')->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive(),

                Select::make('classroom_ids')
                    ->label('Classes')
                    ->options(function () {
                        $classrooms = Classroom::active()->ordered()->with('classGroup')->get();
                        $hasGroups = $classrooms->contains(fn ($c) => $c->class_group_id !== null);
                        if ($hasGroups) {
                            $grouped = [];
                            foreach ($classrooms as $classroom) {
                                $groupName = $classroom->classGroup?->name ?? 'Ungrouped Classes';
                                $grouped[$groupName][$classroom->id] = $classroom->name;
                            }
                            return $grouped;
                        }
                        return $classrooms->pluck('name', 'id');
                    })
                    ->multiple()
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Select one or more classes for this subject assignment')
                    ->visible(fn (string $operation): bool => $operation === 'create'),

                Select::make('classroom_id')
                    ->label('Class')
                    ->options(function () {
                        $classrooms = Classroom::active()->ordered()->with('classGroup')->get();
                        $hasGroups = $classrooms->contains(fn ($c) => $c->class_group_id !== null);
                        if ($hasGroups) {
                            $grouped = [];
                            foreach ($classrooms as $classroom) {
                                $groupName = $classroom->classGroup?->name ?? 'Ungrouped Classes';
                                $grouped[$groupName][$classroom->id] = $classroom->name;
                            }
                            return $grouped;
                        }
                        return $classrooms->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                Select::make('session_id')
                    ->label('Academic Session')
                    ->options(Session::orderBy('start_year', 'desc')->get()->pluck('name', 'id'))
                    ->default($activeSession?->id)
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('term_id', null)),

                Select::make('term_id')
                    ->label('Term')
                    ->options(function (callable $get) {
                        $sessionId = $get('session_id');
                        if (!$sessionId) {
                            return [];
                        }
                        return Term::where('session_id', $sessionId)
                            ->orderBy('order')
                            ->get()
                            ->pluck('name', 'id');
                    })
                    ->default($activeTerm?->id)
                    ->required()
                    ->searchable()
                    ->helperText('Select session first to see available terms'),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'approved' => 'Approved',
                        'pending' => 'Pending Approval',
                        'rejected' => 'Rejected',
                    ])
                    ->default('approved')
                    ->required()
                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                Textarea::make('rejection_reason')
                    ->label('Rejection Reason')
                    ->placeholder('Reason why this request was rejected...')
                    ->rows(3)
                    ->visible(fn (callable $get, string $operation): bool => $operation === 'edit' && $get('status') === 'rejected'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('teacher_avatar')
                    ->label('')
                    ->circular()
                    ->getStateUsing(function (TeacherSubjectAssignment $record): string {
                        $avatarPath = $record->teacher?->teacher?->profile_picture;
                        if ($avatarPath) {
                            return \Illuminate\Support\Facades\Storage::disk(config('filesystems.upload_disk', 'public'))->url($avatarPath);
                        }
                        return 'https://ui-avatars.com/api/?name=' . urlencode($record->teacher?->name ?? '?') . '&background=6366f1&color=fff&size=40';
                    })
                    ->size(40)
                    ->grow(false),

                TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('classroom.name')
                    ->label('Class')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('classroom.classGroup.name')
                    ->label('Class Group')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Approved',
                        'pending' => 'Pending Approval',
                        'rejected' => 'Rejected',
                        default => ucfirst($state),
                    })
                    ->sortable(),

                TextColumn::make('session.name')
                    ->label('Session')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'First Term' => 'primary',
                        'Second Term' => 'warning',
                        'Third Term' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Requested / Assigned')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('class_group_id')
                    ->label('Class Group')
                    ->options(fn () => \App\Models\ClassGroup::active()->ordered()->pluck('name', 'id'))
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereHas('classroom', fn ($q) => $q->where('class_group_id', (int) $data['value']));
                        }
                    })
                    ->placeholder('All Class Groups'),

                SelectFilter::make('classroom_id')
                    ->label('Class')
                    ->options(function () {
                        $classrooms = Classroom::active()->ordered()->with('classGroup')->get();
                        $hasGroups = $classrooms->contains(fn ($c) => $c->class_group_id !== null);
                        if ($hasGroups) {
                            $grouped = [];
                            foreach ($classrooms as $classroom) {
                                $groupName = $classroom->classGroup?->name ?? 'Ungrouped Classes';
                                $grouped[$groupName][$classroom->id] = $classroom->name;
                            }
                            return $grouped;
                        }
                        return $classrooms->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->placeholder('All Classes'),

                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(Subject::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('All Subjects'),

                SelectFilter::make('teacher_id')
                    ->label('Teacher')
                    ->options(User::activeTeachers()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('All Teachers'),

                SelectFilter::make('session_id')
                    ->label('Session')
                    ->options(Session::orderBy('start_year', 'desc')->get()->pluck('name', 'id'))
                    ->default(Session::active()->first()?->id)
                    ->placeholder('All Sessions'),

                SelectFilter::make('term_id')
                    ->label('Term')
                    ->options(Term::orderBy('order')->get()->pluck('name', 'id'))
                    ->placeholder('All Terms'),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->persistFiltersInSession()
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Subject Assignment')
                    ->modalDescription(fn (TeacherSubjectAssignment $record) => "Are you sure you want to approve {$record->teacher?->name} to teach {$record->subject?->name} in {$record->classroom?->name}?")
                    ->visible(fn (TeacherSubjectAssignment $record) => $record->status !== 'approved')
                    ->action(function (TeacherSubjectAssignment $record) {
                        $record->approve(auth()->id());
                        app(LessonNoteCache::class)->invalidateTeacherAssignments((int) $record->teacher_id);

                        if ($record->teacher) {
                            Notification::make()
                                ->title('Subject Assignment Approved')
                                ->body("Your request to teach {$record->subject?->name} in {$record->classroom?->name} has been approved.")
                                ->success()
                                ->sendToDatabase($record->teacher);
                        }

                        Notification::make()
                            ->title('Assignment Approved')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (TeacherSubjectAssignment $record) => $record->status !== 'rejected')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->placeholder('Optional reason for rejecting this assignment request...')
                            ->rows(3),
                    ])
                    ->action(function (TeacherSubjectAssignment $record, array $data) {
                        $record->reject(auth()->id(), $data['rejection_reason'] ?? null);
                        app(LessonNoteCache::class)->invalidateTeacherAssignments((int) $record->teacher_id);

                        if ($record->teacher) {
                            $body = "Your request to teach {$record->subject?->name} in {$record->classroom?->name} was rejected.";
                            if (!empty($data['rejection_reason'])) {
                                $body .= " Reason: {$data['rejection_reason']}";
                            }

                            Notification::make()
                                ->title('Subject Assignment Rejected')
                                ->body($body)
                                ->danger()
                                ->sendToDatabase($record->teacher);
                        }

                        Notification::make()
                            ->title('Assignment Rejected')
                            ->warning()
                            ->send();
                    }),

                \STS\FilamentImpersonate\Actions\Impersonate::make()
                    ->impersonateRecord(fn ($record) => $record->teacher)
                    ->redirectTo('/teacher'),

                EditAction::make(),
                DeleteAction::make()
                    ->after(function (TeacherSubjectAssignment $record) {
                        app(LessonNoteCache::class)->invalidateTeacherAssignments((int) $record->teacher_id);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->approve(auth()->id());
                                app(LessonNoteCache::class)->invalidateTeacherAssignments((int) $record->teacher_id);

                                if ($record->teacher) {
                                    Notification::make()
                                        ->title('Subject Assignment Approved')
                                        ->body("Your request to teach {$record->subject?->name} in {$record->classroom?->name} has been approved.")
                                        ->success()
                                        ->sendToDatabase($record->teacher);
                                }
                                $count++;
                            }

                            Notification::make()
                                ->title("Approved {$count} assignment(s)")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->reject(auth()->id());
                                app(LessonNoteCache::class)->invalidateTeacherAssignments((int) $record->teacher_id);

                                if ($record->teacher) {
                                    Notification::make()
                                        ->title('Subject Assignment Rejected')
                                        ->body("Your request to teach {$record->subject?->name} in {$record->classroom?->name} was rejected.")
                                        ->danger()
                                        ->sendToDatabase($record->teacher);
                                }
                                $count++;
                            }

                            Notification::make()
                                ->title("Rejected {$count} assignment(s)")
                                ->warning()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherSubjectAssignments::route('/'),
            'create' => Pages\CreateTeacherSubjectAssignment::route('/create'),
            'edit' => Pages\EditTeacherSubjectAssignment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (! tenant()) {
            return null;
        }
        
        $pendingCount = static::getModel()::pending()->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
