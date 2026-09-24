<?php

namespace App\Filament\Pages;

use App\Models\ReleaseNote;
use App\Services\ReleaseNoteNotificationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class ReleaseNotes extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static string | \UnitEnum | null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Release Notes';

    protected static ?string $title = 'System Updates & Release Notes';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.release-notes';

    public string $selectedCategory = 'all';

    public string $search = '';

    /**
     * Display unread release notes count as a navigation badge.
     */
    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isSudo())) {
            return null;
        }

        try {
            $count = ReleaseNote::unreadFor($user)->count();
            return $count > 0 ? (string) $count : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Unread system updates';
    }

    /**
     * Access restricted to Administrators and Sudo users.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        return $user->isAdmin() || $user->isSudo();
    }

    public function mount(): void
    {
        if (!static::canAccess()) {
            abort(403, 'Unauthorized access to Release Notes.');
        }

        // Auto mark as read on visit so notification badges clear
        $user = auth()->user();
        if ($user) {
            app(ReleaseNoteNotificationService::class)->markAllAsRead($user);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllAsRead')
                ->label('Mark All as Read')
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->action(function () {
                    $user = auth()->user();
                    if ($user) {
                        app(ReleaseNoteNotificationService::class)->markAllAsRead($user);
                        Notification::make()
                            ->title('All release notes marked as read')
                            ->success()
                            ->send();
                    }
                }),
        ];
    }

    /**
     * Get filtered release notes collection.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, ReleaseNote>
     */
    public function getReleaseNotesProperty(): Collection
    {
        $query = ReleaseNote::published();

        if ($this->selectedCategory !== 'all') {
            $query->where('category', $this->selectedCategory);
        }

        if (!empty(trim($this->search))) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('version', 'like', $term)
                    ->orWhere('summary', 'like', $term)
                    ->orWhere('procedure_guide', 'like', $term);
            });
        }

        return $query->get();
    }

    public function setCategory(string $category): void
    {
        $this->selectedCategory = $category;
    }
}
