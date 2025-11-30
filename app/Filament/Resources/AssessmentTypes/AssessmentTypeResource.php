<?php

namespace App\Filament\Resources\AssessmentTypes;

use App\Filament\Resources\AssessmentTypes\Pages\CreateAssessmentType;
use App\Filament\Resources\AssessmentTypes\Pages\EditAssessmentType;
use App\Filament\Resources\AssessmentTypes\Pages\ListAssessmentTypes;
use App\Filament\Resources\AssessmentTypes\Schemas\AssessmentTypeForm;
use App\Filament\Resources\AssessmentTypes\Tables\AssessmentTypesTable;
use App\Models\AssessmentType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssessmentTypeResource extends Resource
{
    protected static ?string $model = AssessmentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AssessmentTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssessmentTypesTable::configure($table);
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
            'index' => ListAssessmentTypes::route('/'),
            'create' => CreateAssessmentType::route('/create'),
            'edit' => EditAssessmentType::route('/{record}/edit'),
        ];
    }
}
