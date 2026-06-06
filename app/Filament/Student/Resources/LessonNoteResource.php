<?php

namespace App\Filament\Student\Resources;

use App\Filament\Student\Resources\LessonNoteResource\Pages;
use App\Models\LessonNote;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Resources\Resource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonNoteResource extends Resource
{
    protected static ?string $model = LessonNote::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Lesson Notes';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $student = auth()->user()?->student;
        if (! $student) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $enrollment = $student->currentEnrollment();
        if (! $enrollment) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $classroomId = $enrollment->classroom_id;
        $subjectIds = $enrollment->classroom->subjects()->pluck('subjects.id')->toArray();

        return parent::getEloquentQuery()
            ->where('classroom_id', $classroomId)
            ->whereIn('subject_id', $subjectIds)
            ->approved()
            ->active();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Lesson Note Details')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('teacher_id')
                                ->relationship('teacher', 'name')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('subject_id')
                                ->relationship('subject', 'name')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('week_number')
                                ->options(array_combine(range(1, 12), range(1, 12)))
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('term_id')
                                ->relationship('term', 'name')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('session_id')
                                ->relationship('session', 'name')
                                ->disabled()
                                ->dehydrated(false),
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
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('week_number')
                    ->label('Week')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Teacher')
                    ->sortable(),

                Tables\Columns\TextColumn::make('latestVersion.file_name')
                    ->label('File')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->latestVersion?->file_name),

                Tables\Columns\TextColumn::make('latestVersion.reviewed_at')
                    ->label('Published')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name'),

                Tables\Filters\SelectFilter::make('week_number')
                    ->label('Week')
                    ->options(array_combine(range(1, 12), range(1, 12))),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (LessonNote $record) => $record->latestVersion?->getDownloadUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (LessonNote $record) => $record->latestVersion !== null),
            ])
            ->bulkActions([
                // No bulk actions for students
            ])
            ->defaultSort('week_number', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessonNotes::route('/'),
        ];
    }
}
