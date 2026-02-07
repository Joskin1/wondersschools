<?php

namespace App\Filament\Teacher\Resources\Scores\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ScoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('student_id')
                    ->required()
                    ->numeric(),
                TextInput::make('subject_id')
                    ->required()
                    ->numeric(),
                TextInput::make('classroom_id')
                    ->required()
                    ->numeric(),
                TextInput::make('score_header_id')
                    ->required()
                    ->numeric(),
                TextInput::make('session')
                    ->required(),
                TextInput::make('term')
                    ->required()
                    ->numeric(),
                TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('academic_session_id')
                    ->required()
                    ->numeric(),
                TextInput::make('term_id')
                    ->required()
                    ->numeric(),
                TextInput::make('ca_score')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('exam_score')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
