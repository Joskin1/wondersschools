<?php

namespace App\Filament\Sudo\Resources;

use App\Filament\Sudo\Resources\SchoolResource\Pages\ManageSchools;
use App\Models\Tenant;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class SchoolResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static ?string $navigationLabel = 'Schools';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $modelLabel = 'School';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('School Identity')
                ->columns(2)
                ->schema([
                    TextInput::make('id')
                        ->label('Slug / ID')
                        ->helperText('Lowercase slug, no spaces (e.g. royal-academy). Cannot be changed after creation.')
                        ->required()
                        ->maxLength(100)
                        ->alphaDash()
                        ->unique(table: 'tenants', column: 'id', ignoreRecord: true)
                        ->rules([Rule::notIn(['sudo', 'admin', 'teacher', 'api', 'livewire'])])
                        ->validationMessages(['not_in' => 'This slug is reserved and cannot be used.'])
                        ->disabledOn('edit'),

                    TextInput::make('name')
                        ->label('School Name')
                        ->required()
                        ->maxLength(255),
                ]),

            Section::make('Branding')
                ->schema([
                    ColorPicker::make('primary_color')
                        ->label('Primary Brand Color')
                        ->helperText('Applied to admin and teacher Filament panels.')
                        ->nullable(),
                ]),

            Section::make('Domains')
                ->schema([
                    Repeater::make('domains')
                        ->relationship('domains')
                        ->schema([
                            TextInput::make('domain')
                                ->label('Domain')
                                ->helperText('e.g. royal-academy.wonders.test or royalacademy.edu.ng')
                                ->required()
                                ->maxLength(255)
                                ->unique(table: 'domains', column: 'domain', ignoreRecord: true),
                        ])
                        ->addActionLabel('Add Domain')
                        ->minItems(1)
                        ->defaultItems(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('School Name')
                    ->sortable(query: fn (Builder $query, string $direction): Builder =>
                        $query->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name')) {$direction}")
                    )
                    ->searchable(query: fn (Builder $query, string $search): Builder =>
                        $query->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`data`, '$.name')) LIKE ?", ["%{$search}%"])
                    ),

                Tables\Columns\ColorColumn::make('primary_color')
                    ->label('Color'),

                Tables\Columns\TextColumn::make('domains.domain')
                    ->label('Domains')
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSchools::route('/'),
        ];
    }
}
