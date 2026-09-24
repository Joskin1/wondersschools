<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ReleaseNoteNotificationService;
use Illuminate\Auth\Events\Login;

class CheckUnreadReleaseNotesOnLogin
{
    public function __construct(
        protected ReleaseNoteNotificationService $service
    ) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->service->notifyUnreadReleases($event->user);
        }
    }
}
