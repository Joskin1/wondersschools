<?php

namespace App\Filament\Pages;

use App\Models\Grading;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Notifications\Notification;

class GradingSchemePage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.grading-scheme-page';
    
    protected static ?string $navigationLabel = 'Grading Scheme';
    
    protected static ?string $title = 'Grading Scheme Management';
    
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    public function table(Table $table): Table
    {
        return $table
            ->query(Grading::query()->orderBy('lower_bound', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('letter')
                    ->label('Grade')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'D', 'E', 'F' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('lower_bound')
                    ->label('Min Score (%)')
                    ->sortable()
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('upper_bound')
                    ->label('Max Score (%)')
                    ->sortable()
                    ->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('remark')
                    ->label('Remark')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Subject')
                    ->searchable()
                    ->sortable()
                    ->default('Global')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->relationship('subject', 'name')
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->heading('Grading Scheme')
            ->description('Define grade boundaries and remarks for student assessment. Grades are automatically assigned based on percentage scores.')
            ->paginated([10, 25, 50]);
    }
}
