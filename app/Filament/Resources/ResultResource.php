<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResultResource\Pages;
use App\Models\Result;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Schema; // Required for the new method signature
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ResultResource extends Resource
{
    protected static ?string $model = Result::class;

    // Fixed: Type definition matches parent class requirements
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-academic-cap';
    
    // protected static ?string $navigationGroup = 'Academic';

    // Fixed: Updated signature to use Schema instead of Form
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([ // Changed from ->schema() to ->components()
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'first_name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('academic_session_id')
                    ->relationship('academicSession', 'name')
                    ->required(),
                Forms\Components\Select::make('term_id')
                    ->relationship('term', 'name')
                    ->required(),
                Forms\Components\Select::make('classroom_id')
                    ->relationship('classroom', 'name')
                    ->required(),
                Forms\Components\TextInput::make('total_score')
                    ->numeric()
                    ->maxValue(999999.99),
                Forms\Components\TextInput::make('average_score')
                    ->numeric()
                    ->maxValue(999999.99),
                Forms\Components\TextInput::make('position')
                    ->numeric(),
                Forms\Components\TextInput::make('grade')
                    ->maxLength(255),
                Forms\Components\Textarea::make('teacher_remark')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('principal_remark')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academicSession.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('term.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('classroom.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
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
            'index' => Pages\ManageResults::route('/'),
        ];
    }
}