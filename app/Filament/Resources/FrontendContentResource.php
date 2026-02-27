<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrontendContentResource\Pages\CreateFrontendContent;
use App\Filament\Resources\FrontendContentResource\Pages\EditFrontendContent;
use App\Filament\Resources\FrontendContentResource\Pages\ListFrontendContents;
use App\Models\FrontendContent;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FrontendContentResource extends Resource
{
    protected static ?string $model = FrontendContent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationGroup(): ?string
    {
        return 'Website';
    }

    public static function getNavigationLabel(): string
    {
        return 'Frontend Content';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->unique(FrontendContent::class, 'key', ignoreRecord: true)
                    ->helperText('Unique identifier used in views, e.g. hero_tagline')
                    ->columnSpanFull(),

                TextInput::make('group')
                    ->maxLength(255)
                    ->helperText('Optional grouping, e.g. home.hero, about, academics')
                    ->placeholder('e.g. home.hero'),

                Textarea::make('value')
                    ->rows(6)
                    ->columnSpanFull()
                    ->helperText('Supports HTML for rich text fields rendered with {!! !!}'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('value')
                    ->limit(60)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('group')
            ->filters([
                SelectFilter::make('group')
                    ->options(fn () => FrontendContent::query()
                        ->whereNotNull('group')
                        ->distinct()
                        ->pluck('group', 'group')
                        ->toArray()
                    )
                    ->label('Group'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFrontendContents::route('/'),
            'create' => CreateFrontendContent::route('/create'),
            'edit'   => EditFrontendContent::route('/{record}/edit'),
        ];
    }
}
