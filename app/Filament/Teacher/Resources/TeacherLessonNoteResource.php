<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages;
use App\Jobs\ProcessLessonNoteUpload;
use App\Models\LessonNote;
use App\Models\Session;
use App\Models\SubmissionWindow;
use App\Services\LessonNoteCache;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherLessonNoteResource extends Resource
{
    protected static ?string $model = LessonNote::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationLabel = 'My Lesson Notes';

    protected static ?string $modelLabel = 'Lesson Note';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('teacher_id', auth()->id())
            ->with(['subject', 'classroom', 'latestVersion']);
    }

    public static function form(Schema $schema): Schema
    {
        $cache = app(LessonNoteCache::class);
        $teacherId = auth()->id();
        $assignments = $cache->getTeacherAssignments($teacherId);

        $activeSession = Session::active()->first();
        $activeTerm = $activeSession?->activeTerm;

        return $schema
            ->components([
                Select::make('subject_id')
                    ->label('Subject')
                    ->options($assignments->pluck('subject.name', 'subject_id')->unique())
                    ->required()
                    ->reactive(),

                Select::make('classroom_id')
                    ->label('Class')
                    ->options(function (callable $get) use ($assignments) {
                        $subjectId = $get('subject_id');
                        if (!$subjectId) {
                            return [];
                        }

                        return $assignments
                            ->where('subject_id', $subjectId)
                            ->pluck('classroom.name', 'classroom_id')
                            ->unique();
                    })
                    ->required()
                    ->reactive(),

                Select::make('week_number')
                    ->label('Week')
                    ->options(function () use ($activeSession, $activeTerm) {
                        if (!$activeSession || !$activeTerm) {
                            return [];
                        }

                        return SubmissionWindow::where('session_id', $activeSession->id)
                            ->where('term_id', $activeTerm->id)
                            ->currentlyOpen()
                            ->pluck('week_number')
                            ->mapWithKeys(fn ($w) => [$w => "Week {$w}"])
                            ->toArray();
                    })
                    ->required()
                    ->helperText('Only weeks with open submission windows are shown'),

                FileUpload::make('file')
                    ->label('Lesson Note File')
                    ->disk('public')
                    ->directory('lesson-note-uploads/temp')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(10240)
                    ->required()
                    ->helperText('PDF, DOC, or DOCX. Maximum 10MB.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Class')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('latestVersion.admin_comment')
                    ->label('Feedback')
                    ->limit(50)
                    ->placeholder('No feedback yet'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('week_number')
                    ->label('Week')
                    ->options(array_combine(range(1, 12), range(1, 12))),
            ])
            ->actions([
                Action::make('reupload')
                    ->label('Re-upload')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (LessonNote $record) => $record->status === 'rejected')
                    ->form([
                        FileUpload::make('file')
                            ->label('New Version')
                            ->disk('public')
                            ->directory('lesson-note-uploads/temp')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(10240)
                            ->required(),
                    ])
                    ->action(function (LessonNote $record, array $data) {
                        $filePath = $data['file'];
                        $fileName = basename($filePath);

                        // Reset status to pending
                        $record->update(['status' => 'pending']);

                        ProcessLessonNoteUpload::dispatch(
                            $record->id,
                            $filePath,
                            $fileName,
                            auth()->id()
                        );

                        Notification::make()
                            ->title('New version uploaded')
                            ->body('Your corrected lesson note is being processed.')
                            ->success()
                            ->send();
                    }),

                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (LessonNote $record) => $record->latestVersion?->getDownloadUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (LessonNote $record) => $record->latestVersion !== null),

                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherLessonNotes::route('/'),
            'create' => Pages\CreateTeacherLessonNote::route('/create'),
            'view' => Pages\ViewTeacherLessonNote::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
