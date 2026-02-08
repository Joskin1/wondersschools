<?php

namespace App\Filament\Student\Pages;

use App\Models\Score;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class MyScoresPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';
    
    protected string $view = 'filament.student.pages.my-scores-page';
    
    protected static ?string $navigationLabel = 'My Scores';
    
    protected static ?string $title = 'My Scores';
    
    protected static ?int $navigationSort = 3;

    public ?string $session = null;
    public ?int $term = null;

    public function mount(): void
    {
        $this->session = now()->year . '/' . (now()->year + 1);
        $this->term = 1;
    }

    public function table(Table $table): Table
    {
        $student = auth('student')->user();
        
        if (!$student) {
            return $table->query(Score::query()->whereRaw('1 = 0'));
        }

        $query = Score::query()
            ->where('student_id', $student->id);

        if ($this->session) {
            $query->where('session', $this->session);
        }

        if ($this->term) {
            $query->where('term', $this->term);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scoreHeader.name')
                    ->label('Assessment Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('scoreHeader.max_score')
                    ->label('Max Score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Percentage')
                    ->state(function (Score $record): string {
                        if ($record->scoreHeader && $record->scoreHeader->max_score > 0) {
                            $percentage = ($record->value / $record->scoreHeader->max_score) * 100;
                            return number_format($percentage, 1) . '%';
                        }
                        return 'N/A';
                    })
                    ->badge()
                    ->color(fn (Score $record): string => match (true) {
                        $record->scoreHeader && ($record->value / $record->scoreHeader->max_score) >= 0.7 => 'success',
                        $record->scoreHeader && ($record->value / $record->scoreHeader->max_score) >= 0.5 => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Recorded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->heading('My Scores')
            ->description('View all your scores for the selected session and term.')
            ->defaultSort('subject.name')
            ->paginated([10, 25, 50]);
    }
}
