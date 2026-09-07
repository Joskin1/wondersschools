<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages;
use App\Jobs\ProcessLessonNoteUpload;
use App\Models\LessonNote;
use App\Models\Session;
use App\Models\SubmissionWindow;
use App\Services\LessonNoteCache;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                Section::make('Class & Subject Selection')
                    ->description('Select the target classroom, subject, and week for this lesson note.')
                    ->schema([
                        Grid::make(3)->schema([
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
                        ]),
                    ]),

                Section::make('Lesson Note Content')
                    ->description('Choose whether to upload a document or write your lesson note online.')
                    ->schema([
                        Radio::make('submission_type')
                            ->label('Submission Format')
                            ->options([
                                'file' => '📄 Upload Document (PDF, DOC, DOCX)',
                                'written' => '📝 Write Lesson Note Online',
                            ])
                            ->default('file')
                            ->live()
                            ->required(),

                        // File Upload Option
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
                            ->required(fn ($get) => $get('submission_type') === 'file' || empty($get('submission_type')))
                            ->visible(fn ($get) => $get('submission_type') === 'file' || empty($get('submission_type')))
                            ->helperText('PDF, DOC, or DOCX. Maximum 10MB.')
                            ->columnSpanFull(),

                        // Written Option
                        TextInput::make('title')
                            ->label('Lesson Topic / Title')
                            ->placeholder('e.g. Introduction to Photosynthesis & Plant Nutrition')
                            ->required(fn ($get) => $get('submission_type') === 'written')
                            ->visible(fn ($get) => $get('submission_type') === 'written')
                            ->columnSpanFull(),

                        RichEditor::make('content')
                            ->label('Lesson Note Body')
                            ->placeholder('Type or paste your lesson note content here including objectives, key concepts, instructional materials, step-by-step presentation, and evaluation questions...')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'h2',
                                'h3',
                                'bulletList',
                                'orderedList',
                                'blockquote',
                                'codeBlock',
                                'link',
                                'redo',
                                'undo',
                            ])
                            ->required(fn ($get) => $get('submission_type') === 'written')
                            ->visible(fn ($get) => $get('submission_type') === 'written')
                            ->columnSpanFull(),

                        FileUpload::make('images')
                            ->label('Supporting Diagrams / Images (Optional)')
                            ->helperText('Attach illustrations, diagrams, textbook figures, or chart images. Maximum 5MB per image.')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->maxSize(5120) // 5MB per image as requested
                            ->disk(config('filesystems.upload_disk', 'public'))
                            ->directory('lesson-notes/images')
                            ->visible(fn ($get) => $get('submission_type') === 'written')
                            ->columnSpanFull(),
                    ]),
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

                Tables\Columns\TextColumn::make('latestVersion.file_name')
                    ->label('Lesson Note / Topic')
                    ->formatStateUsing(function ($state, LessonNote $record) {
                        if ($record->latestVersion?->isWritten()) {
                            return '📝 ' . ($record->latestVersion->title ?: 'Written Note');
                        }
                        return '📄 ' . ($state ?: 'Document File');
                    })
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->latestVersion?->isWritten() ? ($record->latestVersion->title ?: 'Written Lesson Note') : $record->latestVersion?->file_name),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('latestVersion.admin_comment')
                    ->label('Feedback')
                    ->limit(40)
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
                    ->label('Re-submit')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (LessonNote $record) => $record->status === 'rejected')
                    ->form([
                        Radio::make('submission_type')
                            ->label('Submission Format')
                            ->options([
                                'file' => '📄 Upload Document (PDF, DOC, DOCX)',
                                'written' => '📝 Write Lesson Note Online',
                            ])
                            ->default(fn (LessonNote $record) => $record->latestVersion?->isWritten() ? 'written' : 'file')
                            ->live()
                            ->required(),

                        FileUpload::make('file')
                            ->label('New Document File')
                            ->disk('public')
                            ->directory('lesson-note-uploads/temp')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(10240)
                            ->required(fn ($get) => $get('submission_type') === 'file')
                            ->visible(fn ($get) => $get('submission_type') === 'file'),

                        TextInput::make('title')
                            ->label('Lesson Topic / Title')
                            ->default(fn (LessonNote $record) => $record->latestVersion?->title)
                            ->required(fn ($get) => $get('submission_type') === 'written')
                            ->visible(fn ($get) => $get('submission_type') === 'written'),

                        RichEditor::make('content')
                            ->label('Lesson Note Body')
                            ->default(fn (LessonNote $record) => $record->latestVersion?->content)
                            ->required(fn ($get) => $get('submission_type') === 'written')
                            ->visible(fn ($get) => $get('submission_type') === 'written'),

                        FileUpload::make('images')
                            ->label('Supporting Diagrams / Images (Optional)')
                            ->helperText('Maximum 5MB per image.')
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->maxSize(5120)
                            ->disk(config('filesystems.upload_disk', 'public'))
                            ->directory('lesson-notes/images')
                            ->default(fn (LessonNote $record) => $record->latestVersion?->images)
                            ->visible(fn ($get) => $get('submission_type') === 'written'),
                    ])
                    ->action(function (LessonNote $record, array $data) {
                        $submissionType = $data['submission_type'] ?? 'file';

                        if ($submissionType === 'written') {
                            $version = \App\Models\LessonNoteVersion::create([
                                'lesson_note_id' => $record->id,
                                'submission_type' => 'written',
                                'title' => $data['title'] ?? null,
                                'content' => $data['content'] ?? null,
                                'images' => $data['images'] ?? null,
                                'file_name' => ($data['title'] ?? 'Written Lesson Note') . ' (Week ' . $record->week_number . ')',
                                'file_size' => strlen($data['content'] ?? ''),
                                'file_hash' => hash('sha256', ($data['content'] ?? '') . json_encode($data['images'] ?? [])),
                                'uploaded_by' => auth()->id(),
                                'mime_type' => 'text/html',
                                'status' => 'pending',
                            ]);

                            $record->update([
                                'latest_version_id' => $version->id,
                                'status' => 'pending',
                            ]);

                            Notification::make()
                                ->title('Lesson note re-submitted')
                                ->body('Your updated written lesson note has been submitted for review.')
                                ->success()
                                ->send();
                        } else {
                            $filePath = $data['file'];
                            $fileName = basename($filePath);

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
                        }
                    }),

                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (LessonNote $record) => $record->latestVersion?->getDownloadUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (LessonNote $record) => $record->latestVersion?->isFile() && $record->latestVersion?->file_path !== null),

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
