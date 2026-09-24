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
            $this->service->notifyUnreadReleases($user);
        }

        return $next($request);
    }
}
