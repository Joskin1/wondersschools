<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Scores\Pages\CreateScore;
use App\Filament\Resources\Scores\Pages\EditScore;
use App\Filament\Resources\Scores\Pages\ListScores;
use App\Filament\Resources\Scores\Schemas\ScoreForm;
use App\Filament\Resources\Scores\Tables\ScoresTable;
use App\Models\Score;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScoreResource extends Resource {
    protected static ?string $model = Score::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form( Schema $schema ): Schema {
        return ScoreForm::configure( $schema );
    }

    public static function table( Table $table ): Table {
        return ScoresTable::configure( $table );
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListScores::route( '/' ),
            'create' => CreateScore::route( '/create' ),
            'edit' => EditScore::route( '/{record}/edit' ),
        ];
    }
}
