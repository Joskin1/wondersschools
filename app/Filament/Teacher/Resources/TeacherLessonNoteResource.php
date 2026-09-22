<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\TeacherLessonNoteResource\Pages;
use App\Jobs\ProcessLessonNoteUpload;
use App\Models\LessonNote;
use App\Services\LessonNoteCache;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;


class TeacherLessonNoteResource extends Resource
{
    protected static ?string $model = LessonNote::class;

    protected static ?string $slug = 'lesson-notes';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static string|\UnitEnum|null $navigationGroup = 'Lessons';

    protected static ?string $navigationLabel = 'My Lesson Notes';

    protected static ?string $modelLabel = 'Lesson Note';

    protected static ?int $navigationSort = 2;


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

        return $schema
            ->components([
                ViewField::make('draft_manager')
                    ->view('filament.components.form-draft-manager')
                    ->viewData([
                        'resourceName' => 'Lesson Note',
                        'draftType' => 'lesson_note',
                    ])
                    ->dehydrated(false)
                    ->columnSpanFull(),

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
                                ->options(array_combine(
                                    range(1, config('academic.weeks_per_term')),
                                    array_map(fn ($week) => "Week {$week}", range(1, config('academic.weeks_per_term')))
                                ))
                                ->required()
                                ->helperText('Select any week in the 14-week term.'),
                        ]),
                    ]),

                Section::make('Learning Objectives')
                    ->description('At the end of the lesson, students should be able to:')
                    ->schema([
                        Repeater::make('learning_objectives')
                            ->label('')
                            ->schema([
                                TextInput::make('objective')
                                    ->label('Objective')
                                    ->placeholder('Enter a learning objective…')
                                    ->required(),
                            ])
                            ->addActionLabel('Add Objective')
                            ->defaultItems(0)
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->deletable()
                            ->columnSpanFull()
                            ->helperText('These objectives can be reused in your corresponding Lesson Plan.'),
                    ]),

                Section::make('Lesson Note Content')
                    ->description('Choose whether to write your lesson note online or upload a filled template (.docx).')
                    ->schema([
                        Radio::make('submission_type')
                            ->label('Creation Method')
                            ->options([
                                'written' => '📝 Write Lesson Note Online',
                                'template' => '📥 Upload from Template (.docx)',
                            ])
                            ->default('written')
                            ->live()
                            ->required(),

                        // Template Upload Option
                        FileUpload::make('template_file')
                            ->label('Filled Lesson Note Template (.docx)')
                            ->disk('public')
                            ->directory('lesson-note-uploads/temp')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/msword',
                            ])
                            ->maxSize(10240)
                            ->required(fn ($get) => $get('submission_type') === 'template')
                            ->visible(fn ($get) => $get('submission_type') === 'template')
                            ->helperText('Upload your completed Lesson_Note_Template.docx. The system will extract the title, learning objectives, and content into the database.')
                            ->columnSpanFull(),

                        // Written Option
                        TextInput::make('title')
                            ->label('Lesson Topic / Title')
                            ->placeholder('e.g. INTRODUCTION TO PHOTOSYNTHESIS & PLANT NUTRITION')
                            ->required(fn ($get) => $get('submission_type') === 'written')
                            ->visible(fn ($get) => $get('submission_type') === 'written')
                            ->dehydrateStateUsing(fn ($state) => $state ? mb_strtoupper(trim($state)) : null)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->helperText('Main topic for this lesson note (saved in ALL CAPS).')
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
                            ->maxSize(5120)
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

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Draft',
                        'pending' => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('latestVersion.admin_comment')
                    ->label('Feedback')
                    ->limit(40)
                    ->placeholder('No feedback yet'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),

                Tables\Filters\SelectFilter::make('week_number')
                    ->label('Week')
                    ->options(array_combine(
                        range(1, config('academic.weeks_per_term')),
                        range(1, config('academic.weeks_per_term'))
                    )),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (LessonNote $record): bool => $record->canBeEditedByTeacher()),
                Action::make('submit_for_review')
                    ->label('Submit for Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Submit for Admin Review?')
                    ->modalDescription('Both this Lesson Note and the corresponding Lesson Plan for this week will be submitted for admin review.')
                    ->visible(fn (LessonNote $record) => in_array($record->status, ['draft', 'rejected']))
                    ->action(function (LessonNote $record) {
                        $result = app(\App\Services\LessonSubmissionService::class)->submitPairForReview($record);

                        if (!$result['success']) {
                            Notification::make()
                                ->title('Cannot Submit for Review')
                                ->body($result['message'])
                                ->danger()
                                ->persistent()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Submitted for Review')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherLessonNotes::route('/'),
            'create' => Pages\CreateTeacherLessonNote::route('/create'),
            'edit' => Pages\EditTeacherLessonNote::route('/{record}/edit'),
            'view' => Pages\ViewTeacherLessonNote::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return $record->teacher_id === auth()->id()
            && $record->canBeEditedByTeacher();
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
