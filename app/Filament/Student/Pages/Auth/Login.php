<?php

namespace App\Filament\Student\Pages\Auth;

use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Facades\Filament;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;
use Filament\Actions\Action;

class Login extends SimplePage
{
    protected string $view = 'filament.student.pages.auth.login';

    public ?array $data = [];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('admission_number')
                    ->label('Admission Number')
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->autocomplete('current-password')
                    ->extraInputAttributes(['tabindex' => 2]),
                Checkbox::make('remember')
                    ->label('Remember me'),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        if (! Filament::auth()->attempt([
            'admission_number' => $data['admission_number'],
            'password' => $data['password'],
        ], $data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'data.admission_number' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }
    
    public function getHeading(): string
    {
        return 'Student Login';
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('authenticate')
                ->label('Sign in')
                ->submit('authenticate'),
        ];
    }
}
