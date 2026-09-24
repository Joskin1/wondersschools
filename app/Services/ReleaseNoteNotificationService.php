<?php

namespace App\Services;

use App\Filament\Pages\ReleaseNotes;
use App\Models\ReleaseNote;
use App\Models\ReleaseNoteRead;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ReleaseNoteNotificationService
{
    /**
     * Check unread release notes for an admin user and send database notification if not already sent.
     */
    public function notifyUnreadReleases(?User $user): void
    {
        if (!$user || (!$user->isAdmin() && !$user->isSudo())) {
            return;
        }

        try {
            $unreadNotes = ReleaseNote::unreadFor($user)->get();

            if ($unreadNotes->isEmpty()) {
                return;
            }

            $latest = $unreadNotes->first();

            // Prevent spamming duplicate notifications for the same release version
            $alreadyNotified = $user->unreadNotifications()
                ->where('data->viewData->release_note_version', $latest->version)
                ->exists();

            if ($alreadyNotified) {
                return;
            }

            $count = $unreadNotes->count();
            $title = $count === 1
                ? "🚀 New System Update: {$latest->title} ({$latest->version})"
                : "🚀 {$count} New System Updates Available ({$latest->version})";

            $body = $latest->summary;
            if ($latest->procedure_guide) {
                $body .= "\n\n⚠️ Action Required / Procedure Update Included.";
            }

            Notification::make()
                ->title($title)
                ->body($body)
                ->icon('heroicon-o-sparkles')
                ->iconColor('primary')
                ->actions([
                    Action::make('view_release_notes')
                        ->label('View Release Notes')
                        ->button()
                        ->url(fn () => ReleaseNotes::getUrl(panel: 'admin')),
                ])
                ->viewData([
                    'release_note_version' => $latest->version,
                ])
                ->sendToDatabase($user);
        } catch (\Throwable $e) {
            Log::warning('Failed to send release note notification: ' . $e->getMessage());
        }
    }

    /**
     * Mark all published release notes as read for the user.
     */
    public function markAllAsRead(User $user): void
    {
        $publishedNotes = ReleaseNote::published()->get();

        foreach ($publishedNotes as $note) {
            $note->markAsReadFor($user);
        }

        // Mark corresponding database notifications as read
        try {
            $user->unreadNotifications()
                ->whereNotNull('data->viewData->release_note_version')
                ->update(['read_at' => now()]);
        } catch (\Throwable $e) {
            Log::warning('Failed to update release note notification read_at: ' . $e->getMessage());
        }
    }

    /**
     * Mark a specific release note as read for the user.
     */
    public function markAsRead(ReleaseNote $releaseNote, User $user): void
    {
        $releaseNote->markAsReadFor($user);

        // If there are no more unread releases, mark notifications read
        if (ReleaseNote::unreadFor($user)->count() === 0) {
            try {
                $user->unreadNotifications()
                    ->whereNotNull('data->viewData->release_note_version')
                    ->update(['read_at' => now()]);
            } catch (\Throwable $e) {
                Log::warning('Failed to mark release notifications read: ' . $e->getMessage());
            }
        }
    }
}
