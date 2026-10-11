<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassroomResource\Pages;
use App\Filament\Resources\ClassroomResource\RelationManagers;
use App\Models\Classroom;
use App\Services\FrontendLibrary;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class ClassroomResource extends Resource
{
    protected static ?string $model = Classroom::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Classrooms';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return auth()->user()?->canAccessAdminPage('classrooms') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Class Name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('e.g., JSS 1, Primary 2, SS 1')
                    ->helperText('Name of the class (e.g. JSS 1, Primary 2). Arms/streams can be added separately.'),

                Select::make('class_group_id')
                    ->label('Class Group')
                    ->relationship('classGroup', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Select a class group / category (optional)')
                    ->helperText('Category or stage this classroom belongs to (e.g., Primary, JSS, SSS).'),

                TextInput::make('class_order')
                    ->label('Promotion Order')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->unique(ignoreRecord: true)
                    ->helperText('Lower number = lower academic level. Defines the promotion sequence (1 for JSS 1, 2 for JSS 2, etc.).'),

                Toggle::make('has_departments')
                    ->label('Senior School (Has Departments)')
                    ->default(false)
                    ->helperText('Enable this for Senior Secondary classes (SS 1–3 / Year 10–12) to allow students in this class to select a department (Science, Commercial, Arts, etc.).'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive classrooms are hidden from new assignment dropdowns but preserved in historical records.'),

                Select::make('subjects')
                    ->relationship('subjects', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Select which subjects are offered in this classroom.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $enableClassArms = FrontendLibrary::getBooleanSetting('enable_class_arms');

        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('classGroup.name')
                    ->label('Group')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable()
                    ->placeholder('None'),

                TextColumn::make('arms.name')
                    ->label('Arms')
                    ->badge()
                    ->color('primary')
                    ->separator(', ')
                    ->placeholder('No arms')
                    ->visible(fn () => $enableClassArms),

                TextColumn::make('class_order')
                    ->label('Order')
                    ->sortable()
                    ->badge(),

                IconColumn::make('has_departments')
                    ->label('Senior / Dept')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('lesson_notes_count')
                    ->counts('lessonNotes')
                    ->label('Lesson Notes')
                    ->badge()
                    ->color('info'),

                TextColumn::make('subjects_count')
                    ->counts('subjects')
                    ->label('Subjects')
                    ->badge()
                    ->color('success'),

                TextColumn::make('assignments_count')
                    ->counts('assignments')
                    ->label('Teacher Assignments')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('class_group_id')
                    ->relationship('classGroup', 'name')
                    ->label('Class Group'),

                TernaryFilter::make('has_departments')
                    ->label('Has Departments (Senior)'),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                // No bulk actions — preserve data integrity
            ])
            ->defaultSort('class_order', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ArmsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassrooms::route('/'),
            'create' => Pages\CreateClassroom::route('/create'),
            'edit' => Pages\EditClassroom::route('/{record}/edit'),
        ];
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
