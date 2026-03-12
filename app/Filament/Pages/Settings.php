<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use App\Models\Setting;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class Settings extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('School Information')
                    ->schema([
                        TextInput::make('school_name')->required(),
                        TextInput::make('school_motto'),
                        TextInput::make('school_email')->email()->required(),
                        TextInput::make('school_phone')->tel()->required(),
                        TextInput::make('school_website')->url(),
                        Textarea::make('school_address')->required(),
                    ])
                    ->columns(2),

                Section::make('Branding')
                    ->description('Customize your school\'s visual identity across all pages.')
                    ->schema([
                        FileUpload::make('school_logo')
                            ->image()
                            ->directory('logos')
                            ->disk('public')
                            ->maxSize(512)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Max 512 KB. Used on report cards and navigation.'),
                        ColorPicker::make('secondary_color')
                            ->label('Secondary Color')
                            ->helperText('Used for accents, links, and highlights.')
                            ->nullable(),
                        ColorPicker::make('accent_color')
                            ->label('Accent Color')
                            ->helperText('Used for hover states and badges.')
                            ->nullable(),
                        Select::make('layout_style')
                            ->label('Layout Style')
                            ->options([
                                'standard' => 'Standard',
                                'centered' => 'Centered',
                                'compact'  => 'Compact',
                            ])
                            ->default('standard')
                            ->helperText('Controls the overall page layout style.'),
                        TextInput::make('fee_schedule_link')->url(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }
}
