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
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class LessonNoteResource extends Resource
{
    protected static ?string $model = LessonNote::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Lesson Notes';

    protected static ?string $navigationLabel = 'Admin Review';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lesson Note Details')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('teacher_id')
                                ->relationship('teacher', 'name')
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
                                ->options(array_combine(range(1, 12), range(1, 12)))
                                ->required()
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                ])
                                ->required()
                                ->disabled()
                                ->dehydrated(false),

                            Textarea::make('latestVersion.admin_comment')
                                ->label('Admin Comment')
                                ->rows(3)
                                ->disabled()
                                ->visible(fn ($record) => $record->latestVersion?->admin_comment !== null)
                                ->helperText('Feedback provided to the teacher'),
                        ]),
                    ]),

                Section::make('Uploaded File')
                    ->schema([
                        ViewField::make('file_preview')
                            ->view('filament.components.lesson-note-preview')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->searchable()
                    ->sortable(),

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
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('latestVersion.file_name')
                    ->label('File')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->latestVersion?->file_name),

                Tables\Columns\TextColumn::make('latestVersion.file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 2) . ' KB' : 'N/A'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('classroom_id')
                    ->label('Classroom')
                    ->options(Classroom::all()->pluck('name', 'id'))
                    ->multiple(),

                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(Subject::all()->pluck('name', 'id'))
                    ->multiple(),

                Tables\Filters\SelectFilter::make('week_number')
                    ->label('Week')
                    ->options(array_combine(range(1, 12), range(1, 12)))
                    ->multiple(),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),

                Tables\Filters\SelectFilter::make('session_id')
                    ->label('Session')
                    ->options(Session::all()->pluck('name', 'id')),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (LessonNote $record) => $record->latestVersion?->getDownloadUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (LessonNote $record) => $record->latestVersion !== null),

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
                            'Approved by ' . auth()->user()->name
                        );

                        Notification::make()
                            ->title('Lesson Note Approved')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (LessonNote $record) => $record->status === 'pending'),

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
                            'Rejected by ' . auth()->user()->name
                        );

                        Notification::make()
                            ->title('Lesson Note Rejected')
                            ->warning()
                            ->send();
                    })
                    ->visible(fn (LessonNote $record) => $record->status === 'pending'),

                ViewAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('bulk_approve')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            if ($record->status === 'pending') {
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
                                    'Bulk approved by ' . auth()->user()->name
                                );
                            }
                        }

                        Notification::make()
                            ->title('Lesson Notes Approved')
                            ->body(count($records) . ' lesson notes approved')
                            ->success()
                            ->send();
                    }),
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

    public static function canCreate(): bool
    {
        return false; // Teachers create via their own resource
    }

    public static function canDelete($record): bool
    {
        return false; // Never allow deletions
    }

    public static function canEdit($record): bool
    {
        return false; // Admins approve/reject, don't edit directly
    }
}
