<?php

namespace App\Filament\Pages;

use Filament\Schemas\Schema;
use App\Models\Setting;
use Filament\Forms\Components\FileUpload;
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
                    ->schema([
                        FileUpload::make('school_logo')
                            ->image()
                            ->directory('logos')
                            ->disk('public')
                            ->maxSize(512)
                            ->helperText('Max 512 KB. Used on report cards.'),
                        TextInput::make('fee_schedule_link')->url(),
                    ]),
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
