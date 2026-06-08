<?php

namespace App\Filament\Pages;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;

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
                                return new HtmlString('<img src="' . $avatarUrl . '" alt="Profile Picture" class="w-20 h-20 rounded-full object-cover border border-gray-200 shadow-sm">');
                            }),
                        TextInput::make('name')
                            ->required()
                            ->readOnly()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->readOnly()
                            ->maxLength(255),
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
                Section::make('Change Password')
                    ->description('Ensure your account is using a long, random password to stay secure.')
                    ->schema([
                        $this->getCurrentPasswordFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ]),
            ]);
    }
}
