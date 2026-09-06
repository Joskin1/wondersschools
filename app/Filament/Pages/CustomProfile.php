<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use SensitiveParameter;

class CustomProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile Information')
                    ->description('Your profile details. Please contact an administrator to change these.')
                    ->schema([
                        Placeholder::make('avatar')
                            ->label('Profile Picture')
                            ->content(function () {
                                $avatarUrl = Filament::getUserAvatarUrl(auth()->user());

                                return new HtmlString('<img src="'.$avatarUrl.'" alt="Profile Picture" class="w-20 h-20 rounded-full object-cover border border-gray-200 shadow-sm">');
                            }),
                        TextInput::make('name')
                            ->required()
                            ->readOnly()
                            ->maxLength(255),
                        Placeholder::make('admission_number')
                            ->label('Admission Number')
                            ->visible(fn () => auth()->user()->isStudent())
                            ->content(fn () => auth()->user()->student?->admission_number ?? 'Not assigned'),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->readOnly()
                            ->maxLength(255)
                            ->visible(fn () => ! auth()->user()->isStudent()),
                        TextInput::make('role')
                            ->readOnly()
                            ->formatStateUsing(fn ($state) => ucfirst($state ?? '')),
                        Placeholder::make('current_class')
                            ->label('Current Class')
                            ->visible(fn () => auth()->user()->isStudent())
                            ->content(function () {
                                return auth()->user()->student?->currentEnrollment()?->classroom?->name ?? 'Not Enrolled';
                            }),
                    ]),
                Section::make('Student Information')
                    ->visible(fn () => auth()->user()->isStudent())
                    ->schema([
                        FileUpload::make('profile_picture')
                            ->label('Profile Picture')
                            ->image()
                            ->directory('profile-pictures')
                            ->disk(config('filesystems.upload_disk', 'public'))
                            ->maxSize(2048),
                        DatePicker::make('date_of_birth')
                            ->label('Date of Birth')
                            ->maxDate(now()->subDay()),
                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                            ]),
                        Textarea::make('address')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('previous_school')
                            ->maxLength(255),
                        TextInput::make('parent_name')
                            ->maxLength(255),
                        TextInput::make('parent_phone')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('parent_email')
                            ->email()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                Section::make('Change Password')
                    ->description('Ensure your account is using a long, random password to stay secure.')
                    ->schema([
                        $this->getCurrentPasswordFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $student = $this->getUser()->student;

        if (! $student) {
            return $data;
        }

        return array_merge($data, [
            'profile_picture' => $student->profile_picture,
            'date_of_birth' => $student->date_of_birth?->format('Y-m-d'),
            'gender' => $student->gender,
            'address' => $student->address,
            'previous_school' => $student->previous_school,
            'parent_name' => $student->parent_name,
            'parent_phone' => $student->parent_phone,
            'parent_email' => $student->parent_email,
        ]);
    }

    protected function mutateFormDataBeforeSave(#[SensitiveParameter] array $data): array
    {
        $student = $this->getUser()->student;

        if ($student) {
            $student->update([
                'profile_picture' => $data['profile_picture'] ?? $student->profile_picture,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'] ?? null,
                'address' => $data['address'] ?? null,
                'previous_school' => $data['previous_school'] ?? null,
                'parent_name' => $data['parent_name'] ?? null,
                'parent_phone' => $data['parent_phone'] ?? null,
                'parent_email' => $data['parent_email'] ?? null,
            ]);

            $student->update([
                'registration_completed_at' => $student->registration_completed_at ?? now(),
            ]);
        }

        return collect($data)
            ->except([
                'profile_picture',
                'date_of_birth',
                'gender',
                'address',
                'previous_school',
                'parent_name',
                'parent_phone',
                'parent_email',
            ])
            ->all();
    }
}
