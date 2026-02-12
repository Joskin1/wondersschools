<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionWindowResource\Pages;
use App\Models\SubmissionWindow;
use App\Models\Session;
use App\Models\Term;
use App\Services\LessonNoteCache;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class SubmissionWindowResource extends Resource
{
    protected static ?string $model = SubmissionWindow::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Lesson Notes';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('session_id')
                    ->label('Academic Session')
                    ->options(Session::all()->pluck('name', 'id'))
                    ->default(fn () => Session::active()->first()?->id)
                    ->required()
                    ->reactive(),

                Select::make('term_id')
                    ->label('Term')
                    ->options(function (callable $get) {
                        $sessionId = $get('session_id');
                        if (!$sessionId) {
                            return [];
                        }
                        return Term::where('session_id', $sessionId)
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->reactive(),

                Select::make('week_number')
                    ->label('Week Number')
                    ->options(array_combine(range(1, 12), range(1, 12)))
                    ->required()
                    ->helperText('Select week 1-12'),

                DateTimePicker::make('opens_at')
                    ->label('Opens At')
                    ->required()
                    ->default(now()->startOfWeek())
                    ->helperText('When teachers can start uploading'),

                DateTimePicker::make('closes_at')
                    ->label('Closes At')
                    ->required()
                    ->default(now()->endOfWeek())
                    ->helperText('When the submission window closes')
                    ->after('opens_at'),

                Toggle::make('is_open')
                    ->label('Window Open')
                    ->default(true)
                    ->helperText('Toggle to manually open/close this window'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('session.name')
                    ->label('Session')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable(),

                Tables\Columns\TextColumn::make('week_number')
                    ->label('Week')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('opens_at')
                    ->label('Opens')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('closes_at')
                    ->label('Closes')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_open')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('updated_by.name')
                    ->label('Updated By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('session_id')
                    ->label('Session')
                    ->options(Session::all()->pluck('name', 'id')),

                Tables\Filters\TernaryFilter::make('is_open')
                    ->label('Status')
                    ->placeholder('All windows')
                    ->trueLabel('Open only')
                    ->falseLabel('Closed only'),
            ])
            ->actions([
                Action::make('toggle')
                    ->label(fn (SubmissionWindow $record) => $record->is_open ? 'Close' : 'Open')
                    ->icon(fn (SubmissionWindow $record) => $record->is_open ? 'heroicon-o-lock-closed' : 'heroicon-o-lock-open')
                    ->color(fn (SubmissionWindow $record) => $record->is_open ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (SubmissionWindow $record) {
                        $newStatus = !$record->is_open;
                        $record->update([
                            'is_open' => $newStatus,
                            'updated_by' => auth()->id(),
                        ]);

                        // Invalidate cache
                        app(LessonNoteCache::class)->invalidateWindow(
                            $record->session_id,
                            $record->term_id,
                            $record->week_number
                        );

                        Notification::make()
                            ->title('Window ' . ($newStatus ? 'Opened' : 'Closed'))
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->bulkActions([
                // No bulk delete - preserve historical data
            ])
            ->defaultSort('session_id', 'desc')
            ->defaultSort('week_number', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissionWindows::route('/'),
            'create' => Pages\CreateSubmissionWindow::route('/create'),
            'edit' => Pages\EditSubmissionWindow::route('/{record}/edit'),
        ];
    }

    public static function canDelete($record): bool
    {
        return false; // Never allow deletions
    }
}
