<?php

namespace App\Filament\Resources\ClassroomResource\RelationManagers;

use App\Filament\Resources\ClassArmResource;
use App\Models\ClassArm;
use App\Services\FrontendLibrary;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ArmsRelationManager extends RelationManager
{
    protected static string $relationship = 'arms';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return FrontendLibrary::getBooleanSetting('enable_class_arms');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Arm Name')
                    ->required()
                    ->maxLength(100)
                    ->placeholder('e.g., Gold, Silver, Diamond, A, B, Science')
                    ->helperText('Name of the arm / stream for this classroom.'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Arm Name')
                    ->weight('bold')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('enrollments_count')
                    ->counts('enrollments')
                    ->label('Enrolled Students')
                    ->badge()
                    ->color('info'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Arm')
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->actions([
                Action::make('assign_students')
                    ->label('Assign Students')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (): bool => auth()->user()?->canPerformAdminAction('class_arms', 'update') ?? false)
                    ->modalHeading(fn (ClassArm $record): string => "Assign Students to {$record->full_name}")
                    ->modalDescription('Select one or more students from this classroom to add them to this arm at once.')
                    ->modalWidth('4xl')
                    ->modalSubmitActionLabel('Assign Selected Students')
                    ->form(fn (ClassArm $record): array => ClassArmResource::assignStudentsFormSchema($record))
                    ->action(fn (ClassArm $record, array $data) => ClassArmResource::handleAssignStudents($data, $record)),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
