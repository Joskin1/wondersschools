<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionWindowResource\Pages;
use App\Models\SubmissionWindow;
use App\Models\Session;
use App\Models\Term;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubmissionWindowResource extends Resource
{
    protected static ?string $model = SubmissionWindow::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string | \UnitEnum | null $navigationGroup = 'Lessons';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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
                    ->options(array_combine(
                        range(1, config('academic.weeks_per_term')),
                        range(1, config('academic.weeks_per_term'))
                    ))
                    ->required()
                    ->rules([
                        fn ($get, ?\Illuminate\Database\Eloquent\Model $record) => \Illuminate\Validation\Rule::unique('submission_windows', 'week_number')
                            ->where(function ($query) use ($get) {
                                return $query->where('session_id', $get('session_id'))
                                    ->where('term_id', $get('term_id'));
                            })
                            ->ignore($record?->id),
                    ])
                    ->validationMessages([
                        'unique' => 'A submission window for this session, term, and week already exists.',
                    ])
                    ->helperText('Select week 1-14'),

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

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('session_id')
                    ->label('Session')
                    ->options(Session::all()->pluck('name', 'id')),

            ])
            ->actions([])
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
        ];
    }

    public static function canDelete($record): bool
    {
        return false; // Never allow deletions
    }
}
