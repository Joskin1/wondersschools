<?php

namespace App\Filament\Teacher\Pages;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class MySignature extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string | \UnitEnum | null $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Digital Signature';

    protected static ?string $title = 'My Digital Signature';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.teacher.pages.my-signature';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->isTeacher() || $user->isAssignedTeacher() || $user->isAdmin() || $user->isSudo();
    }

    public function mount(): void
    {
        $user = Auth::user();

        $this->form->fill([
            'signature' => $user?->signature,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $user = Auth::user();

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Digital Signature Upload')
                    ->description('Upload your signature scan or digital image. Whenever you are assigned as a Class Teacher, your signature will automatically appear on official student report cards and midterm assessment slips.')
                    ->schema([
                        Placeholder::make('current_preview')
                            ->label('Current Active Signature')
                            ->content(function () {
                                $user = Auth::user();
                                if (! $user || empty($user->signature)) {
                                    return new HtmlString('<div class="p-4 border-2 border-dashed border-gray-300 rounded-lg text-sm text-gray-500 text-center">No digital signature uploaded yet.</div>');
                                }

                                $url = Storage::disk(config('filesystems.upload_disk', 'public'))->url($user->signature);

                                return new HtmlString(
                                    '<div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex flex-col items-center gap-2">' .
                                    '<img src="' . e($url) . '" alt="Signature Preview" class="max-h-24 max-w-xs object-contain border border-gray-200 bg-white p-2 rounded shadow-sm">' .
                                    '<span class="text-xs text-gray-500">Active signature on record</span>' .
                                    '</div>'
                                );
                            }),

                        FileUpload::make('signature')
                            ->label('New Signature Image')
                            ->image()
                            ->directory('signatures')
                            ->disk(config('filesystems.upload_disk', 'public'))
                            ->maxSize(2048)
                            ->imageResizeMode('contain')
                            ->imageCropAspectRatio(null)
                            ->helperText('Upload a clear scan of your handwritten signature. Transparent PNG recommended, max 2 MB.'),
                    ]),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $user = Auth::user();

        if ($user) {
            $user->signature = $state['signature'] ?? null;
            $user->save();

            Notification::make()
                ->title('Signature Saved Successfully')
                ->body('Your digital signature is now updated and will be affixed to official report cards where you are the class teacher.')
                ->success()
                ->send();
        }
    }
}
