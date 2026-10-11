<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\AdminPermissionRegistry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

class ManageAdminPermissions extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | \UnitEnum | null $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Admin Permissions';

    protected static ?string $title = 'Admin Page & Resource Access';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.manage-admin-permissions';

    public ?int $selectedUserId = null;

    public ?int $principalUserId = null;

    public string $adminDesignation = '';

    public array $selectedPermissions = [];

    /**
     * Only Sudo users and the Primary Admin (Principal) can access this page.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSudo() || $user->isPrimaryAdmin());
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->principalUserId = $this->getCurrentPrincipal()?->id;

        $requestedUserId = request()->query('user');
        $admins = $this->getSecondaryAdmins();

        if ($requestedUserId && $admins->contains('id', (int) $requestedUserId)) {
            $this->selectedUserId = (int) $requestedUserId;
        } elseif ($admins->isNotEmpty()) {
            $this->selectedUserId = $admins->first()->id;
        }

        $this->loadUserData();
    }

    /**
     * Get all admin users for Sudo to select from.
     */
    public function getAllAdmins(): Collection
    {
        return User::query()
            ->where('role', 'admin')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get currently designated Principal, if any.
     */
    public function getCurrentPrincipal(): ?User
    {
        return User::query()
            ->where('role', 'admin')
            ->where('is_primary_admin', true)
            ->first();
    }

    /**
     * Assign Principal privilege to a selected user (Sudo only).
     */
    public function assignPrincipal(): void
    {
        abort_unless(auth()->user()?->isSudo(), 403);

        if (! $this->principalUserId) {
            Notification::make()
                ->title('Please select an admin user to designate as Principal')
                ->warning()
                ->send();
            return;
        }

        $user = User::where('role', 'admin')->find($this->principalUserId);
        if (! $user) {
            Notification::make()
                ->title('Invalid user selected')
                ->danger()
                ->send();
            return;
        }

        // Reset previous primary admin
        User::where('role', 'admin')->where('is_primary_admin', true)->update([
            'is_primary_admin' => false,
        ]);

        // Designate selected user
        $user->is_primary_admin = true;
        if (empty($user->admin_designation)) {
            $user->admin_designation = 'Principal';
        }
        $user->save();

        // Refresh secondary admin selection
        $admins = $this->getSecondaryAdmins();
        $this->selectedUserId = $admins->first()?->id;
        $this->loadUserData();

        Notification::make()
            ->title('Principal Privilege Granted')
            ->body("{$user->name} ({$user->email}) is now designated as Principal. They can now access Admin Permissions in their panel.")
            ->success()
            ->send();
    }

    /**
     * Revoke Principal privilege from a user (Sudo only).
     */
    public function revokePrincipal(int $userId): void
    {
        abort_unless(auth()->user()?->isSudo(), 403);

        $user = User::where('role', 'admin')->find($userId);
        if ($user) {
            $user->is_primary_admin = false;
            $user->save();

            $this->principalUserId = null;
            $admins = $this->getSecondaryAdmins();
            $this->selectedUserId = $admins->first()?->id;
            $this->loadUserData();

            Notification::make()
                ->title('Principal Privilege Revoked')
                ->body("{$user->name} ({$user->email}) no longer has access to Admin Permissions.")
                ->info()
                ->send();
        }
    }

    /**
     * Get list of secondary administrators that can have permissions managed.
     */
    public function getSecondaryAdmins(): Collection
    {
        return User::query()
            ->where('role', 'admin')
            ->where('is_primary_admin', false)
            ->orderBy('name')
            ->get();
    }

    public function updatedSelectedUserId(): void
    {
        $this->loadUserData();
    }

    public function loadUserData(): void
    {
        if (! $this->selectedUserId) {
            $this->adminDesignation = '';
            $this->selectedPermissions = [];
            return;
        }

        $user = User::find($this->selectedUserId);
        if ($user) {
            $this->adminDesignation = (string) ($user->admin_designation ?? '');
            $this->selectedPermissions = (array) ($user->allowed_admin_pages ?? []);
        } else {
            $this->adminDesignation = '';
            $this->selectedPermissions = [];
        }
    }

    public function applyPreset(string $presetKey): void
    {
        $presets = AdminPermissionRegistry::getPresets();
        if (! isset($presets[$presetKey])) {
            return;
        }

        $preset = $presets[$presetKey];
        $this->selectedPermissions = $preset['pages'];

        if (empty($this->adminDesignation) || in_array($this->adminDesignation, array_column($presets, 'designation'))) {
            $this->adminDesignation = $preset['designation'];
        }

        Notification::make()
            ->title("Preset applied: {$preset['name']}")
            ->success()
            ->send();
    }

    public function selectAll(): void
    {
        $this->selectedPermissions = AdminPermissionRegistry::getAllKeys();

        Notification::make()
            ->title('All pages selected')
            ->info()
            ->send();
    }

    public function deselectAll(): void
    {
        $this->selectedPermissions = [];

        Notification::make()
            ->title('All pages cleared')
            ->warning()
            ->send();
    }

    public function toggleGroup(string $groupName): void
    {
        $grouped = AdminPermissionRegistry::getPermissionsGrouped();
        if (! isset($grouped[$groupName])) {
            return;
        }

        $groupKeys = array_keys($grouped[$groupName]['items']);
        $allPresent = count(array_intersect($groupKeys, $this->selectedPermissions)) === count($groupKeys);

        if ($allPresent) {
            // Remove group keys
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $groupKeys));
        } else {
            // Add all group keys
            $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $groupKeys)));
        }
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        if (! $this->selectedUserId) {
            Notification::make()
                ->title('No administrator selected')
                ->danger()
                ->send();
            return;
        }

        $user = User::query()
            ->where('id', $this->selectedUserId)
            ->where('role', 'admin')
            ->where('is_primary_admin', false)
            ->first();

        if (! $user) {
            Notification::make()
                ->title('Invalid user selected')
                ->danger()
                ->send();
            return;
        }

        $user->admin_designation = trim($this->adminDesignation) ?: null;
        $user->allowed_admin_pages = array_values(array_unique($this->selectedPermissions));
        $user->save();

        Notification::make()
            ->title('Permissions updated successfully')
            ->body("Access permissions saved for {$user->name} (" . ($user->admin_designation ?: 'Admin') . ').')
            ->success()
            ->send();
    }
}
