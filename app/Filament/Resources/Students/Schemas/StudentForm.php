<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('admission_number')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Admission Number')
                    ->placeholder('e.g., STD/2024/001'),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->label('Password')
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->password()
                    ->label('Confirm Password')
                    ->dehydrated(false),
                Select::make('classroom_id')
                    ->relationship('classroom', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->required(),
                    ]),
            ]);
    }
}
