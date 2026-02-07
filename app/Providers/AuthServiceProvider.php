<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\TeacherAssignmentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Auto-discovery is enabled, but we can explicitly map if needed
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register gates for score management
        Gate::define('manage-score', function (User $user) {
            // Admins and teachers can manage scores
            return $user->isAdmin() || $user->isTeacher();
        });

        // Register gate for teacher assignment management
        Gate::define('manage-teacher-assignment', function (User $user) {
            // Only admins can manage teacher assignments
            return $user->isAdmin();
        });

        // Register gate for viewing audit logs
        Gate::define('view-audit-logs', function (User $user) {
            // Only admins can view audit logs
            return $user->isAdmin();
        });

        // Register gate for bulk score operations
        Gate::define('bulk-score-operations', function (User $user) {
            // Admins and teachers can perform bulk operations
            return $user->isAdmin() || $user->isTeacher();
        });

        // Register gate for score import/export
        Gate::define('import-export-scores', function (User $user) {
            // Admins and teachers can import/export scores
            return $user->isAdmin() || $user->isTeacher();
        });
    }
}
