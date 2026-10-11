<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassArmResource\Pages;
use App\Models\ClassArm;
use App\Services\FrontendLibrary;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class ClassArmResource extends Resource
{
    protected static ?string $model = ClassArm::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Class Arms';

    protected static ?int $navigationSort = 5;

    public static function shouldRegisterNavigation(): bool
    {
        return FrontendLibrary::getBooleanSetting('enable_class_arms');
    }

    public static function canAccess(): bool
    {
        return FrontendLibrary::getBooleanSetting('enable_class_arms') && (auth()->user()?->canAccessAdminPage('class_arms') ?? false);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('classroom_id')
                    ->label('Classroom')
                    ->relationship('classroom', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->placeholder('Select classroom to add arm against')
                    ->helperText('Select the classroom (e.g. JSS 1) that this arm belongs to.'),

                TextInput::make('name')
                    ->label('Arm Name')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('e.g., Gold, Silver, Diamond, A, B, Science')
                    ->helperText('Name/stream identifier for this arm (e.g., Gold, Silver, A, B).'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive arms will not be selectable for new enrollments.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('classroom.name')
                    ->label('Classroom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Arm')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('classroom.classGroup.name')
                    ->label('Class Group')
                    ->badge()
                    ->color('gray')
                    ->placeholder('None'),

                TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Students')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('classroom_id')
                    ->label('Filter by Classroom')
                    ->relationship('classroom', 'name'),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('classroom_id', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassArms::route('/'),
            'create' => Pages\CreateClassArm::route('/create'),
            'edit' => Pages\EditClassArm::route('/{record}/edit'),
        ];
    }
}
