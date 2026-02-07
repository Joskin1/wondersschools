<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTeacher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('filament.teacher.auth.login');
        }

        $user = auth()->user();

        // Check if user is a teacher or admin
        if (!$user->isTeacher() && !$user->isAdmin()) {
            abort(403, 'Access denied. Only teachers can access this panel.');
        }

        return $next($request);
    }
}
