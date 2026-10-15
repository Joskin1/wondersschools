<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ResultPinBatchResource\Pages\ManageResultPinBatches;
use App\Models\ResultPin;
use App\Models\ResultPinBatch;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use STS\FilamentImpersonate\Facades\Impersonation;

class ResultPinBatchResource extends Resource
{
    protected static ?string $model = ResultPinBatch::class;

    protected static ?string $navigationLabel = 'Result PINs';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|\UnitEnum|null $navigationGroup = 'Results';

    protected static ?string $modelLabel = 'Result PIN Batch';

    protected static ?string $pluralModelLabel = 'Result PIN Batches';

    protected static ?int $navigationSort = 15;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSudo() && ! Impersonation::isImpersonating();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSudo() && ! Impersonation::isImpersonating();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('batch_code')
                    ->label('Batch Code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('session.name')
                    ->label('Session')
                    ->sortable(),

                Tables\Columns\TextColumn::make('term.name')
                    ->label('Term')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Total PINs')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('usage')
                    ->label('Usage Status')
                    ->badge()
                    ->state(function (ResultPinBatch $record): string {
                        $used = $record->pins()->where('is_used', true)->count();
                        $unused = $record->pins()->where('is_used', false)->count();

                        return "{$used} used / {$unused} unused";
                    })
                    ->color(fn (string $state): string => str_starts_with($state, '0 used') ? 'gray' : 'success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Generated On')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Actions\Action::make('download_pdf')
                    ->label('Print (PDF)')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn (ResultPinBatch $record): string => route('admin.result-pins.pdf', ['batch' => $record->id]))
                    ->openUrlInNewTab(),

                Actions\Action::make('view_pins')
                    ->label('View PINs')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading(fn (ResultPinBatch $record) => "PINs for {$record->batch_code}")
                    ->modalDescription('List of generated PINs in this batch and their current redemption status.')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(function (ResultPinBatch $record) {
                        $pins = $record->pins()->with('student')->orderBy('id')->get();

                        return view('filament.components.batch-pins-modal', [
                            'batch' => $record,
                            'pins'  => $pins,
                        ]);
                    }),

                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageResultPinBatches::route('/'),
        ];
    }
}
