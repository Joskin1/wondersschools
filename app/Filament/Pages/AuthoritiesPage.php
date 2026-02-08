<?php

namespace App\Filament\Pages;

use App\Models\SchoolAuthority;
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

class AuthoritiesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.authorities-page';
    
    protected static ?string $navigationLabel = 'Authorities';
    
    protected static ?string $title = 'School Authorities';
    
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-identification';

    public function table(Table $table): Table
    {
        return $table
            ->query(SchoolAuthority::query()->orderBy('display_order'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('display_order')
                    ->label('Display Order')
                    ->sortable()
                    ->badge(),
                Tables\Columns\ImageColumn::make('signature_path')
                    ->label('Signature')
                    ->disk('public')
                    ->height(40),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            ->heading('School Authorities & Result Signatories')
            ->description('Manage school authorities who sign student results and appear on report cards.')
            ->paginated([10, 25, 50]);
    }
}
