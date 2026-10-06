<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ReleaseNoteNotificationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUnreadReleaseNotesMiddleware
{
    public function __construct(
        protected ReleaseNoteNotificationService $service
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user instanceof User && ($user->isAdmin() || $user->isSudo())) {
            $session = $request->hasSession() ? $request->session() : null;
            $sessionKey = 'release_notes_checked_at_' . $user->id;

            // Only check release notes once every 30 minutes per user session
            if (! $session || ! $session->has($sessionKey) || (now()->timestamp - (int) $session->get($sessionKey)) > 1800) {
                $this->service->notifyUnreadReleases($user);
                $session?->put($sessionKey, now()->timestamp);
            }
        }

        return $next($request);
    }
}
