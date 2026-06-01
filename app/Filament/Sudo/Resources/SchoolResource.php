<?php

declare(strict_types=1);

namespace App\Filament\Sudo\Resources;

use App\Enums\TenantStatus;
use App\Filament\Sudo\Resources\SchoolResource\Pages\ManageSchools;
use App\Models\Tenant;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;

class SchoolResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static ?string $navigationLabel = 'Schools';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $modelLabel = 'School';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('School Identity')
                ->columns(2)
                ->schema([
                    TextInput::make('id')
                        ->label('Slug / ID')
                        ->helperText('Lowercase slug, no spaces (e.g. royal-academy). Cannot be changed after creation.')
                        ->required()
                        ->maxLength(100)
                        ->alphaDash()
                        ->regex('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/')
                        ->unique(table: 'tenants', column: 'id', ignoreRecord: true)
                        ->rules([Rule::notIn(['sudo', 'admin', 'teacher', 'student', 'api', 'livewire', 'localhost'])])
                        ->validationMessages([
                            'not_in' => 'This slug is reserved and cannot be used.',
                            'regex'  => 'Slug must contain only lowercase letters, numbers, and hyphens. Must start and end with a letter or number.',
                        ])
                        ->disabledOn('edit'),

                    TextInput::make('name')
                        ->label('School Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true),
                ]),

            Section::make('Branding')
                ->schema([
                    ColorPicker::make('primary_color')
                        ->label('Primary Brand Color')
                        ->helperText('Applied to admin, teacher, and student Filament panels. Hex value stored (e.g. #f59e0b).')
                        ->nullable(),
                ]),

            Section::make('Domains')
                ->schema([
                    Repeater::make('domains')
                        ->relationship('domains')
                        ->schema([
                            TextInput::make('domain')
                                ->label('Domain')
                                ->helperText('e.g. royal-academy.wonders.test or royalacademy.edu.ng')
                                ->required()
                                ->maxLength(255)
                                ->regex('/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/')
                                ->unique(table: 'domains', column: 'domain', ignoreRecord: true)
                                ->rules([Rule::notIn([
                                    'localhost',
                                    '127.0.0.1',
                                    '0.0.0.0',
                                    ...array_filter(explode(',', config('tenancy.central_domains', env('CENTRAL_DOMAINS', '')))),
                                ])])
                                ->validationMessages([
                                    'not_in' => 'This domain is reserved and cannot be used.',
                                    'regex'  => 'Must be a valid hostname (e.g. school.example.com).',
                                ]),
                        ])
                        ->addActionLabel('Add Domain')
                        ->minItems(1)
                        ->defaultItems(1),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('School Name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (TenantStatus $state): string => $state->label())
                    ->color(fn (TenantStatus $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\ColorColumn::make('primary_color')
                    ->label('Color'),

                Tables\Columns\TextColumn::make('domains.domain')
                    ->label('Domains')
                    ->badge()
                    ->separator(','),

                Tables\Columns\TextColumn::make('last_provisioned_at')
                    ->label('Provisioned At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSchools::route('/'),
        ];
    }
}
