<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermResource\Pages;
use App\Models\Term;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TermResource extends Resource
{
    protected static ?string $model = Term::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('session_id')
                    ->relationship('session', 'name')
                    ->required()
                    ->disabled()
                    ->dehydrated(true),
                
                Select::make('name')
                    ->options([
                        'First Term' => 'First Term',
                        'Second Term' => 'Second Term',
                        'Third Term' => 'Third Term',
                    ])
                    ->required()
                    ->disabled()
                    ->dehydrated(true),
                
                TextInput::make('order')
                    ->numeric()
                    ->required()
                    ->disabled()
                    ->dehydrated(true),
                
                Toggle::make('is_active')
                    ->label('Active Term')
                    ->helperText('Use the "Migrate Term" action to change active terms')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session.name')
                    ->searchable()
                    ->sortable()
                    ->label('Academic Session'),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->badge()
                    ->color(fn (Term $record) => match ($record->name) {
                        'First Term' => 'success',
                        'Second Term' => 'warning',
                        'Third Term' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->label('Order'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('session_id')
                    ->relationship('session', 'name')
                    ->label('Academic Session'),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All terms')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                
                Tables\Actions\Action::make('migrate')
                    ->label('Migrate Term')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Migrate to Next Term')
                    ->modalDescription(function (Term $record) {
                        $nextTerm = $record->next_term;
                        if (!$nextTerm) {
                            return 'This will create a new academic session and migrate to First Term.';
                        }
                        return "This will migrate from {$record->name} to {$nextTerm->name}.";
                    })
                    ->modalSubmitActionLabel('Migrate')
                    ->visible(fn (Term $record) => $record->is_active)
                    ->authorize('migrate', Term::class)
                    ->action(function (Term $record) {
                        try {
                            $nextTerm = $record->migrate();
                            
                            Notification::make()
                                ->success()
                                ->title('Term Migration Successful')
                                ->body("Successfully migrated to {$nextTerm->name} ({$nextTerm->session->name})")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Migration Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                // No bulk actions - preserve data integrity
            ])
            ->defaultSort('session_id', 'desc')
            ->defaultSort('order', 'asc');
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
            'index' => Pages\ListTerms::route('/'),
            'view' => Pages\ViewTerm::route('/{record}'),
        ];
    }
}
