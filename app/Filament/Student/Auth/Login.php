<?php

namespace App\Filament\Student\Auth;

use App\Models\Student;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('admission_number')
            ->label('Admission Number')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $student = Student::query()
            ->where('admission_number', strtoupper(trim((string) $data['admission_number'])))
            ->whereNotNull('user_id')
            ->with('user')
            ->first();

        if (! $student?->user) {
            return [
                'email' => '__missing_student__',
                'password' => $data['password'],
            ];
        }

        return [
            'email' => $student->user->email,
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.admission_number' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    public function getHeading(): string
    {
        return Filament::getCurrentPanel()?->getBrandName() ?? 'Student Portal';
    }
}
