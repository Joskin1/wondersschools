<?php

namespace App\Filament\Teacher\Resources;

use App\Filament\Teacher\Resources\AssignmentResource\Pages;
use App\Models\Assignment;
use App\Models\Session;
use App\Services\LessonNoteCache;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Assignments';

    protected static ?string $modelLabel = 'Assignment';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('teacher_id', auth()->id())
            ->with(['subject', 'classroom', 'questions', 'submissions']);
    }

    public static function form(Schema $schema): Schema
    {
        $cache = app(LessonNoteCache::class);
        $teacherId = auth()->id();
        $assignments = $cache->getTeacherAssignments($teacherId);

        return $schema
            ->components([
                Section::make('Assignment Details')
                    ->schema([
                        Grid::make(2)->schema([
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
                                ->options(array_combine(range(1, 12), array_map(fn($w) => "Week {$w}", range(1, 12))))
                                ->required(),

                            TextInput::make('title')
                                ->label('Assignment Title')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g. Week 3 Mathematics Assignment'),
                        ]),

                        Textarea::make('description')
                            ->label('Instructions (Optional)')
                            ->rows(3)
                            ->placeholder('Any special instructions for students...'),
                    ]),

                Section::make('Questions')
                    ->description('Add as many objective questions as you like. Each question must have at least 2 options and one correct answer.')
                    ->schema([
                        Repeater::make('questions')
                            ->relationship()
                            ->schema([
                                Textarea::make('question_text')
                                    ->label('Question')
                                    ->required()
                                    ->rows(2),

                                TextInput::make('option_a')
                                    ->label('Option A')
                                    ->required(),

                                TextInput::make('option_b')
                                    ->label('Option B')
                                    ->required(),

                                TextInput::make('option_c')
                                    ->label('Option C'),

                                TextInput::make('option_d')
                                    ->label('Option D'),

                                Select::make('correct_option')
                                    ->label('Correct Answer')
                                    ->options(function (callable $get) {
                                        $options = [];
                                        if ($get('option_a')) {
                                            $options['A'] = 'A: ' . $get('option_a');
                                        }
                                        if ($get('option_b')) {
                                            $options['B'] = 'B: ' . $get('option_b');
                                        }
                                        if ($get('option_c')) {
                                            $options['C'] = 'C: ' . $get('option_c');
                                        }
                                        if ($get('option_d')) {
                                            $options['D'] = 'D: ' . $get('option_d');
                                        }

                                        return $options;
                                    })
                                    ->required()
                                    ->reactive(),

                                TextInput::make('points')
                                    ->label('Points')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(10),
                            ])
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $options = [];
                                if (!empty($data['option_a'])) {
                                    $options['A'] = $data['option_a'];
                                }
                                if (!empty($data['option_b'])) {
                                    $options['B'] = $data['option_b'];
                                }
                                if (!empty($data['option_c'])) {
                                    $options['C'] = $data['option_c'];
                                }
                                if (!empty($data['option_d'])) {
                                    $options['D'] = $data['option_d'];
                                }

                                $data['options'] = $options;

                                unset($data['option_a'], $data['option_b'], $data['option_c'], $data['option_d']);

                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                $options = [];
                                if (!empty($data['option_a'])) {
                                    $options['A'] = $data['option_a'];
                                }
                                if (!empty($data['option_b'])) {
                                    $options['B'] = $data['option_b'];
                                }
                                if (!empty($data['option_c'])) {
                                    $options['C'] = $data['option_c'];
                                }
                                if (!empty($data['option_d'])) {
                                    $options['D'] = $data['option_d'];
                                }

                                $data['options'] = $options;

                                unset($data['option_a'], $data['option_b'], $data['option_c'], $data['option_d']);

                                return $data;
                            })
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Add Question')
                            ->reorderable(false)
                            ->itemLabel(fn(array $state): ?string => $state['question_text'] ?? 'New Question'),
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
                    ->badge()
                    ->formatStateUsing(fn($state) => "Week {$state}"),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Questions')
                    ->counts('questions')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('submissions_count')
                    ->label('Submissions')
                    ->counts('submissions')
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name'),

                Tables\Filters\SelectFilter::make('week_number')
                    ->label('Week')
                    ->options(array_combine(range(1, 12), array_map(fn($w) => "Week {$w}", range(1, 12)))),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make()
                    ->visible(fn(Assignment $record) => $record->submissions()->count() === 0),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'view' => Pages\ViewAssignment::route('/{record}'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }

    public static function canDelete($record): bool
    {
        // Only allow deletion if no students have submitted
        return $record->submissions()->count() === 0;
    }
}
