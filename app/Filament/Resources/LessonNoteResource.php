<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LessonNoteResource\Pages;
use App\Models\LessonNote;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Session;
use App\Jobs\LogLessonNoteAction;
use App\Notifications\LessonNoteReviewed;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class LessonNoteResource extends Resource
{
    protected static ?string $model = LessonNote::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Lessons';

    protected static ?string $navigationLabel = 'Admin Review';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessAdminPage('lesson_notes') ?? false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('lesson_notes.status', '!=', 'draft')
            ->basedOnTemplate()
            ->hasPairedLessonPlan()
            ->with(['teacher', 'subject', 'classroom', 'latestVersion']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── SUBMISSION CONTEXT ───────────────────────────────────────────
                Section::make('Submission Details')
                    ->schema([
                        Grid::make(4)->schema([
                            Select::make('teacher_id')
                                ->relationship('teacher', 'surname', fn (\Illuminate\Database\Eloquent\Builder $query) => $query->orderBy('surname')->orderBy('first_name'))
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                ->required()
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('subject_id')
                                ->relationship('subject', 'name')
                                ->required()
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('classroom_id')
                                ->relationship('classroom', 'name')
                                ->required()
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('week_number')
                                ->label('Week')
                                ->options(array_combine(
                                    range(1, config('academic.weeks_per_term')),
                                    range(1, config('academic.weeks_per_term'))
                                ))
                                ->required()
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('session_id')
                                ->relationship('session', 'name')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('term_id')
                                ->relationship('term', 'name')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('status')
                                ->options([
                                    'pending'  => 'Pending',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                ])
                                ->required()
                                ->disabled()
                                ->dehydrated(false),

                            Textarea::make('latestVersion.admin_comment')
                                ->label('Admin Feedback')
                                ->rows(2)
                                ->disabled()
                                ->visible(fn ($record) => $record?->latestVersion?->admin_comment !== null)
                                ->helperText('Feedback provided to the teacher')
                                ->columnSpanFull(),
                        ]),
                    ]),

                // ── PART 1: LESSON NOTE ──────────────────────────────────────────
                Section::make('Part 1 — Lesson Note')
                    ->description('Read the teacher\'s lesson note first, then compare it with the lesson plan below.')
                    ->schema([
                        ViewField::make('file_preview')
                            ->view('filament.components.lesson-note-preview')
                            ->columnSpanFull(),
                    ]),

                // ── PART 2: LESSON PLAN ──────────────────────────────────────────
                Section::make('Part 2 — Lesson Plan')
                    ->description('Review the lesson plan against the lesson note before making a decision.')
                    ->schema([
                        ViewField::make('lesson_plan_review')
                            ->view('filament.components.lesson-plan-review')
                            ->columnSpanFull(),
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.surname')
                    ->label('Teacher')
                    ->formatStateUsing(fn ($record) => $record->teacher?->full_name ?? '—')
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('teacher', function ($q) use ($search) {
                            $q->where('surname', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('other_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction) {
                        $query->join('users as teacher_users', 'lesson_notes.teacher_id', '=', 'teacher_users.id')
                            ->orderBy('teacher_users.surname', $direction)
                            ->orderBy('teacher_users.first_name', $direction)
                            ->select('lesson_notes.*');
                    }),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Class')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('week_number')
                    ->label('Week')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => ucfirst($state ?? ''),
                    }),

                Tables\Columns\TextColumn::make('latestVersion.file_name')
                    ->label('Note / Topic')
                    ->formatStateUsing(function ($state, $record) {
                        $version = $record instanceof \App\Models\LessonNoteVersion 
                            ? $record 
                            : ($record instanceof \App\Models\LessonNote ? $record->latestVersion : null);

                        if ($version?->isWritten()) {
                            return '📝 ' . ($version->title ?: 'Written Note');
                        }
                        return '📄 ' . ($state ?: 'Document File');
                    })
                    ->limit(35)
                    ->tooltip(function ($record) {
                        $version = $record instanceof \App\Models\LessonNoteVersion 
                            ? $record 
                            : ($record instanceof \App\Models\LessonNote ? $record->latestVersion : null);

                        return $version?->isWritten() ? ($version->title ?: 'Written Lesson Note') : $version?->file_name;
                    }),

                Tables\Columns\TextColumn::make('latestVersion.file_size')
                    ->label('Size / Length')
                    ->formatStateUsing(function ($state, $record) {
                        $version = $record instanceof \App\Models\LessonNoteVersion 
                            ? $record 
                            : ($record instanceof \App\Models\LessonNote ? $record->latestVersion : null);

                        if ($version?->isWritten()) {
                            $imgCount = count($version->images ?? []);
                            $chars = strlen($version->content ?? '');
                            return number_format($chars) . ' chars' . ($imgCount > 0 ? " ({$imgCount} img)" : '');
                        }
                        return $state ? number_format($state / 1024, 2) . ' KB' : 'N/A';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Classroom')
                    ->options(Classroom::ordered()->pluck('name', 'id'))
                    ->multiple(),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(Subject::all()->pluck('name', 'id'))
                    ->multiple(),

                Tables\Filters\SelectFilter::make('week_number')
                    ->label('Week')
                    ->options(array_combine(
                        range(1, config('academic.weeks_per_term')),
                        range(1, config('academic.weeks_per_term'))
                    ))
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        if (!$value) {
                            return $query;
                        }

                        return $query->where('lesson_notes.status', $value)
                            ->hasPairedLessonPlan($value);
                    }),

                Tables\Filters\SelectFilter::make('session_id')
                    ->label('Session')
                    ->options(Session::all()->pluck('name', 'id')),
            ])
            ->actions([
                ViewAction::make('review')
                    ->label('Review')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (LessonNote $record): string => static::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),

                Action::make('note_pdf')
                    ->label(fn (LessonNote $record) => $record->latestVersion?->isFile() ? 'Download File' : 'Note PDF')
                    ->icon(fn (LessonNote $record) => $record->latestVersion?->isFile() ? 'heroicon-o-arrow-down-tray' : 'heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->url(fn (LessonNote $record) => $record->latestVersion?->isFile()
                        ? route('tenant.lesson-note.file', ['version' => $record->latestVersion->id, 'download' => 1])
                        : route('admin.lesson-note.pdf', $record))
                    ->openUrlInNewTab(),

                Action::make('plan_pdf')
                    ->label('Plan PDF')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('gray')
                    ->url(fn (LessonNote $record) => $record->getPairedLessonPlan() ? route('admin.lesson-plan.pdf', $record->getPairedLessonPlan()) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn (LessonNote $record) => $record->getPairedLessonPlan() !== null),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('comment')
                            ->label('Comment (Optional)')
                            ->rows(3)
                            ->placeholder('Great work! Approved.'),
                    ])
                    ->action(function (LessonNote $record, array $data) {
                        $record->approve($data['comment'] ?? null, auth()->id());

                        $record->teacher->notify(new LessonNoteReviewed(
                            $record->load(['subject', 'classroom']),
                            'approved',
                            $data['comment'] ?? null
                        ));

                        LogLessonNoteAction::dispatch(
                            $record->id,
                            'approve',
                            auth()->id(),
                            'Approved by ' . auth()->user()->full_name
                        );

                        Notification::make()
                            ->title('Lesson Submission Approved')
                            ->body('The lesson submission has been approved.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (LessonNote $record) => $record->status === 'pending'
                        && $record->getPairedLessonPlan()?->status === 'pending'),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('comment')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3)
                            ->placeholder('Please revise and resubmit...'),
                    ])
                    ->action(function (LessonNote $record, array $data) {
                        $record->reject($data['comment'], auth()->id());

                        $record->teacher->notify(new LessonNoteReviewed(
                            $record->load(['subject', 'classroom']),
                            'rejected',
                            $data['comment']
                        ));

                        LogLessonNoteAction::dispatch(
                            $record->id,
                            'reject',
                            auth()->id(),
                            'Rejected by ' . auth()->user()->full_name
                        );

                        Notification::make()
                            ->title('Lesson Submission Rejected')
                            ->body('Both Lesson Note and Lesson Plan have been marked rejected.')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (LessonNote $record) => $record->status === 'pending'
                        && $record->getPairedLessonPlan()?->status === 'pending'),

                ActionGroup::make([
                    Action::make('delete_note')
                        ->label('Delete Lesson Note')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Lesson Note?')
                        ->modalDescription('Are you sure you want to delete this Lesson Note? The teacher will be able to start again and create a new lesson note for this week. This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete lesson note')
                        ->action(function (LessonNote $record) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                                $pairedPlan = $record->getPairedLessonPlan();
                                if ($pairedPlan && in_array($pairedPlan->status, ['pending', 'approved', 'rejected'])) {
                                    $pairedPlan->update(['status' => 'draft']);
                                }

                                foreach ($record->versions as $version) {
                                    if ($version->file_path) {
                                        try {
                                            \Illuminate\Support\Facades\Storage::disk('lesson_notes')->delete($version->file_path);
                                        } catch (\Throwable $e) {}
                                    }
                                }
                                $record->versions()->forceDelete();
                                $record->forceDelete();
                            });

                            Notification::make()
                                ->title('Lesson Note Deleted')
                                ->body('The lesson note has been permanently deleted. The teacher can now create a new lesson note for this week.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => in_array(auth()->user()?->role, ['admin', 'sudo'], true)),

                    Action::make('delete_plan')
                        ->label('Delete Lesson Plan')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Paired Lesson Plan?')
                        ->modalDescription('Are you sure you want to delete the paired Lesson Plan? The teacher will be able to start again and create a new lesson plan for this week. This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete lesson plan')
                        ->visible(fn (LessonNote $record) => $record->getPairedLessonPlan() !== null
                            && in_array(auth()->user()?->role, ['admin', 'sudo'], true))
                        ->action(function (LessonNote $record) {
                            $plan = $record->getPairedLessonPlan();
                            if ($plan) {
                                \Illuminate\Support\Facades\DB::transaction(function () use ($plan, $record) {
                                    $plan->referenceMaterials()->detach();
                                    $plan->instructionalMaterials()->detach();
                                    $plan->teachingMethods()->detach();
                                    $plan->forceDelete();

                                    $record->update(['status' => 'draft']);
                                    if ($record->latestVersion) {
                                        $record->latestVersion->update(['status' => 'draft']);
                                    }
                                });

                                Notification::make()
                                    ->title('Lesson Plan Deleted')
                                    ->body('The paired lesson plan has been deleted. The teacher can now create a new lesson plan for this week.')
                                    ->success()
                                    ->send();
                            }
                        }),

                    Action::make('delete_both')
                        ->label('Delete Both (Note & Plan)')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Both Lesson Note and Lesson Plan?')
                        ->modalDescription('Are you sure you want to delete both this Lesson Note and its paired Lesson Plan? The teacher will be able to start both again from scratch for this week. This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete both')
                        ->visible(fn (LessonNote $record) => $record->getPairedLessonPlan() !== null
                            && in_array(auth()->user()?->role, ['admin', 'sudo'], true))
                        ->action(function (LessonNote $record) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                                $plan = $record->getPairedLessonPlan();
                                if ($plan) {
                                    $plan->referenceMaterials()->detach();
                                    $plan->instructionalMaterials()->detach();
                                    $plan->teachingMethods()->detach();
                                    $plan->forceDelete();
                                }

                                foreach ($record->versions as $version) {
                                    if ($version->file_path) {
                                        try {
                                            \Illuminate\Support\Facades\Storage::disk('lesson_notes')->delete($version->file_path);
                                        } catch (\Throwable $e) {}
                                    }
                                }
                                $record->versions()->forceDelete();
                                $record->forceDelete();
                            });

                            Notification::make()
                                ->title('Lesson Note and Plan Deleted')
                                ->body('Both documents have been permanently deleted. The teacher can now start both from scratch.')
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Delete Options')
                    ->icon('heroicon-o-trash')
                    ->color('danger'),
            ])
            ->bulkActions([
                BulkAction::make('bulk_approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if ($record->status === 'pending' && $record->getPairedLessonPlan()?->status === 'pending') {
                                $record->approve('Bulk approved', auth()->id());

                                $record->teacher->notify(new LessonNoteReviewed(
                                    $record->load(['subject', 'classroom']),
                                    'approved',
                                    'Bulk approved'
                                ));

                                LogLessonNoteAction::dispatch(
                                    $record->id,
                                    'bulk_approve',
                                    auth()->id(),
                                    'Bulk approved by ' . auth()->user()->full_name
                                );
                            }
                        }

                        Notification::make()
                            ->title('Lesson Notes Approved')
                            ->body(count($records) . ' lesson notes approved')
                            ->success()
                            ->send();
                    }),

                BulkAction::make('bulk_delete')
                    ->label('Delete Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Selected Submissions?')
                    ->modalDescription('Are you sure you want to permanently delete the selected submissions so the teachers can start again?')
                    ->modalSubmitActionLabel('Yes, delete selected')
                    ->action(function ($records) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($records) {
                            foreach ($records as $record) {
                                $plan = $record->getPairedLessonPlan();
                                if ($plan) {
                                    $plan->referenceMaterials()->detach();
                                    $plan->instructionalMaterials()->detach();
                                    $plan->teachingMethods()->detach();
                                    $plan->forceDelete();
                                }
                                foreach ($record->versions as $version) {
                                    if ($version->file_path) {
                                        try {
                                            \Illuminate\Support\Facades\Storage::disk('lesson_notes')->delete($version->file_path);
                                        } catch (\Throwable $e) {}
                                    }
                                }
                                $record->versions()->forceDelete();
                                $record->forceDelete();
                            }
                        });

                        Notification::make()
                            ->title('Submissions Deleted')
                            ->body(count($records) . ' lesson submission(s) deleted. Teachers can now start again.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => in_array(auth()->user()?->role, ['admin', 'sudo'], true)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessonNotes::route('/'),
            'view' => Pages\ViewLessonNote::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (! tenant()) {
            return null;
        }

        try {
            $pendingCount = static::getModel()::query()
                ->where('lesson_notes.status', 'pending')
                ->basedOnTemplate()
                ->hasPairedLessonPlan('pending')
                ->count();
            return $pendingCount > 0 ? (string) $pendingCount : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        if (! tenant()) {
            return null;
        }

        try {
            $pendingCount = static::getModel()::query()
                ->where('lesson_notes.status', 'pending')
                ->basedOnTemplate()
                ->hasPairedLessonPlan('pending')
                ->count();
            return $pendingCount > 0 ? 'warning' : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function canCreate(): bool
    {
        return false; // Teachers create via their own resource
    }

    public static function canDelete($record): bool
    {
        return in_array(auth()->user()?->role, ['admin', 'sudo'], true);
    }

    public static function canEdit($record): bool
    {
        return false; // Admins approve/reject, don't edit directly
    }
}
