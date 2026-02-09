<?php

namespace App\Filament\Resources\TermResource\Pages;

use App\Filament\Resources\TermResource;
use App\Models\Term;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTerms extends ListRecords
{
    protected static string $resource = TermResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Terms'),
            
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true))
                ->badge(Term::query()->where('is_active', true)->count()),
            
            'first' => Tab::make('First Term')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('name', 'First Term')),
            
            'second' => Tab::make('Second Term')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('name', 'Second Term')),
            
            'third' => Tab::make('Third Term')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('name', 'Third Term')),
        ];
    }
}
