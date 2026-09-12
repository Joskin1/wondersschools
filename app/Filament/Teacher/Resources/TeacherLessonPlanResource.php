<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\TeacherLessonPlanResource\Pages;
use App\Models\InstructionalMaterial;
use App\Models\LessonNote;
use App\Models\LessonPlan;
use App\Models\ReferenceMaterial;
use App\Models\TeachingMethod;
use App\Services\LessonSubmissionService;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TeacherLessonPlanResource extends Resource
{
    protected static ?string $model = LessonPlan::class;

    protected static ?string $slug = 'lesson-plans';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Lessons';

    protected static ?string $navigationLabel = 'Lesson Plans';

    protected static ?string $modelLabel = 'Lesson Plan';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('teacher_id', auth()->id())
            ->with(['subject', 'classroom', 'referenceMaterials', 'instructionalMaterials', 'teachingMethods']);
    }

    public static function form(Schema $schema): Schema
    {
        $teacherId = auth()->id();
        // Load teacher assignments for dropdowns
        $assignments = app(\App\Services\LessonNoteCache::class)->getTeacherAssignments($teacherId);

        return $schema
            ->components([
                // ─────────────────────────────────────────────────────────────────
                // 1. LESSON INFORMATION
                // ─────────────────────────────────────────────────────────────────
                Section::make('1. Lesson Information')
                    ->description('Select the class, subject, and week for this lesson plan.')
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
                                    if (!$subjectId) return [];

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

                        Grid::make(2)->schema([
                            TextInput::make('time')
                                ->label('Time / Duration')
                                ->placeholder('e.g. 40 minutes')
                                ->helperText('Duration of this lesson period'),

                            TextInput::make('section')
                                ->label('Section / Period')
                                ->placeholder('e.g. Morning, Period 3')
                                ->helperText('Which session or period this lesson covers'),
                        ]),

                        // Show completion status banner when editing
                        ViewField::make('submission_status')
                            ->view('filament.teacher.components.lesson-submission-status')
                            ->columnSpanFull()
                            ->visible(fn ($record) => $record !== null),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 2. LEARNING OBJECTIVES
                // ─────────────────────────────────────────────────────────────────
                Section::make('2. Learning Objectives')
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
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->deletable()
                            ->minItems(1)
                            ->default([['objective' => '']])
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 3. KEY VOCABULARY WORDS
                // ─────────────────────────────────────────────────────────────────
                Section::make('3. Key Vocabulary Words')
                    ->description('Enter the important vocabulary and terminology associated with this lesson.')
                    ->schema([
                        TagsInput::make('key_vocabulary')
                            ->label('Vocabulary Words')
                            ->placeholder('Type a word and press Enter')
                            ->helperText('Enter each vocabulary word and press Enter or comma to add it.')
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 4. REFERENCE MATERIALS
                // ─────────────────────────────────────────────────────────────────
                Section::make('4. Reference Materials')
                    ->description('Select or create the reference materials used for this lesson.')
                    ->schema([
                        Select::make('reference_materials')
                            ->label('Reference Materials')
                            ->relationship('referenceMaterials', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Material Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Mathematics Textbook for JSS 2'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return ReferenceMaterial::firstOrCreate(['name' => \Illuminate\Support\Str::limit($data['name'], 252)])->id;
                            })
                            ->helperText('Select existing materials or type to create a new one.')
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 5. INSTRUCTIONAL MATERIALS
                // ─────────────────────────────────────────────────────────────────
                Section::make('5. Instructional Materials')
                    ->description('Select or create instructional materials and resources used during the lesson.')
                    ->schema([
                        Select::make('instructional_materials')
                            ->label('Instructional Materials')
                            ->relationship('instructionalMaterials', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Material Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Whiteboard, Projector, Charts'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return InstructionalMaterial::firstOrCreate(['name' => \Illuminate\Support\Str::limit($data['name'], 252)])->id;
                            })
                            ->helperText('Select existing materials or type to create a new one.')
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 6. BUILDING BACKGROUND / PRIOR KNOWLEDGE
                // ─────────────────────────────────────────────────────────────────
                Section::make('6. Building Background / Connection to Prior Knowledge')
                    ->description('Explain how this lesson connects to what students have previously learned.')
                    ->schema([
                        RichEditor::make('prior_knowledge')
                            ->label('')
                            ->placeholder('Describe how this lesson connects to prior learning…')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 7. CONTENT
                // ─────────────────────────────────────────────────────────────────
                Section::make('7. Content')
                    ->description('Enter the full lesson content.')
                    ->schema([
                        RichEditor::make('content')
                            ->label('')
                            ->placeholder('Enter the lesson content here…')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike',
                                'h2', 'h3', 'bulletList', 'orderedList',
                                'blockquote', 'codeBlock', 'link', 'undo', 'redo',
                            ])
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 8. TEACHING METHOD
                // ─────────────────────────────────────────────────────────────────
                Section::make('8. Teaching Method')
                    ->description('Select or create the teaching methods used in this lesson.')
                    ->schema([
                        Select::make('teaching_methods')
                            ->label('Teaching Methods')
                            ->relationship('teachingMethods', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Method Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Discussion Method, Group Work'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return TeachingMethod::firstOrCreate(['name' => \Illuminate\Support\Str::limit($data['name'], 252)])->id;
                            })
                            ->helperText('Select existing methods or create a new one.')
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 9. PRESENTATION STEPS
                // ─────────────────────────────────────────────────────────────────
                Section::make('9. Presentation Steps')
                    ->description('List each step in your presentation sequence.')
                    ->schema([
                        Repeater::make('presentation_steps')
                            ->label('')
                            ->schema([
                                Textarea::make('step')
                                    ->label('Step')
                                    ->placeholder('Describe this presentation step…')
                                    ->rows(2)
                                    ->required(),
                            ])
                            ->addActionLabel('Add Step')
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->deletable()
                            ->minItems(1)
                            ->default([['step' => '']])
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 10. STRATEGIES AND ACTIVITIES
                // ─────────────────────────────────────────────────────────────────
                Section::make('10. Strategies and Activities')
                    ->description('Describe the teaching strategies and student activities for this lesson.')
                    ->schema([
                        RichEditor::make('strategies_activities')
                            ->label('')
                            ->placeholder('Describe strategies and activities…')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 11. ASSESSMENT / EVALUATION
                // ─────────────────────────────────────────────────────────────────
                Section::make('11. Assessment / Evaluation')
                    ->description('The teacher evaluates the students on the lesson taught by asking the following questions:')
                    ->schema([
                        Repeater::make('evaluation_questions')
                            ->label('')
                            ->schema([
                                TextInput::make('question')
                                    ->label('Question')
                                    ->placeholder('Enter an evaluation question…')
                                    ->required(),
                            ])
                            ->addActionLabel('Add Question')
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->deletable()
                            ->minItems(1)
                            ->default([['question' => '']])
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 12. CONCLUSION
                // ─────────────────────────────────────────────────────────────────
                Section::make('12. Conclusion')
                    ->description('Explain how the lesson is brought to a close.')
                    ->schema([
                        RichEditor::make('conclusion')
                            ->label('')
                            ->placeholder('Describe how the lesson concludes…')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'undo', 'redo'])
                            ->columnSpanFull(),
                    ]),

                // ─────────────────────────────────────────────────────────────────
                // 13. ASSIGNMENT / HOMEWORK
                // ─────────────────────────────────────────────────────────────────
                Section::make('13. Assignment / Homework')
                    ->description('Enter the assignment or homework given to students after this lesson.')
                    ->schema([
                        RichEditor::make('assignment')
                            ->label('')
                            ->placeholder('Enter the assignment or homework…')
                            ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList', 'undo', 'redo'])
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

                Tables\Columns\TextColumn::make('title')
                    ->label('Topic / Title')
                    ->limit(40)
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'    => 'gray',
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft'    => 'Draft',
                        'pending'  => 'Pending Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    }),

                Tables\Columns\TextColumn::make('admin_comment')
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
                        'draft'    => 'Draft',
                        'pending'  => 'Pending Review',
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
                    ->visible(fn (LessonPlan $record) => in_array($record->status, ['draft', 'rejected'])),
                Action::make('submit_for_review')
                    ->label('Submit for Review')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Submit Lesson Plan for Admin Review?')
                    ->modalDescription('Once submitted, you will not be able to edit this lesson plan unless it is rejected.')
                    ->visible(fn (LessonPlan $record) => in_array($record->status, ['draft', 'rejected']))
                    ->action(function (LessonPlan $record) {
                        $record->update(['status' => 'pending']);

                        app(LessonSubmissionService::class)->checkAndNotifyIfComplete($record);

                        Notification::make()
                            ->title('Lesson Plan Submitted')
                            ->body('Your lesson plan has been submitted for admin review.')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTeacherLessonPlans::route('/'),
            'create' => Pages\CreateTeacherLessonPlan::route('/create'),
            'edit'   => Pages\EditTeacherLessonPlan::route('/{record}/edit'),
            'view'   => Pages\ViewTeacherLessonPlan::route('/{record}'),
        ];
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
