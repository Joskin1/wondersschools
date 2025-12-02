<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResultResource\Pages;
use App\Models\Result;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class ResultResource extends Resource
{
    protected static ?string $model = Result::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->required()
                    ->searchable(['first_name', 'last_name'])
                    ->preload()
                    ->disabledOn('edit'),
                Forms\Components\Select::make('academic_session_id')
                    ->relationship('academicSession', 'name')
                    ->required()
                    ->disabledOn('edit'),
                Forms\Components\Select::make('term_id')
                    ->relationship('term', 'name')
                    ->required()
                    ->disabledOn('edit'),
                Forms\Components\Select::make('classroom_id')
                    ->relationship('classroom', 'name')
                    ->required()
                    ->disabledOn('edit'),
                Forms\Components\TextInput::make('total_score')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('average_score')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('position')
                    ->numeric()
                    ->readOnly(),
                Forms\Components\TextInput::make('grade')
                    ->readOnly(),
                Forms\Components\Textarea::make('teacher_remark')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('principal_remark')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($record) => $record->student->full_name ?? '')
                    ->sortable()
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('academicSession.name')
                    ->label('Session')
                    ->sortable(),
                Tables\Columns\TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->label('Class')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->sortable(),
                Tables\Columns\TextColumn::make('grade')
                    ->sortable(),
                Tables\Columns\TextColumn::make('teacher_remark')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('principal_remark')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResults::route('/'),
            'create' => Pages\CreateResult::route('/create'),
            'edit' => Pages\EditResult::route('/{record}/edit'),
        ];
    }
}
