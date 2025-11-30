<?php

namespace App\Filament\Resources\Classrooms\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClassroomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('staff_id')
                    ->relationship('teacher', 'name')
                    ->label('Class Teacher')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
