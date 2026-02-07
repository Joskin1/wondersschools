<?php

namespace App\Services;

use App\Models\ResultOption;
use Illuminate\Support\Collection;

class ResultSettingsService
{
    /**
     * Get settings by name
     */
    public function getSettings(?string $settingsName = null, ?int $schoolId = null): array
    {
        if ($settingsName) {
            return $this->getNamedSettings($settingsName, $schoolId);
        }
        
        return $this->getDefaultSettings($schoolId);
    }

    /**
     * Get named settings
     */
    private function getNamedSettings(string $settingsName, ?int $schoolId = null): array
    {
        // For now, return default settings
        // In future, you could store different setting profiles
        return $this->getDefaultSettings($schoolId);
    }

    /**
     * Get default settings
     */
    public function getDefaultSettings(?int $schoolId = null): array
    {
        $options = ResultOption::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->get();

        $settings = [];
        foreach ($options as $option) {
            $settings[$option->key] = $option->getTypedValue();
        }

        return $settings;
    }

    /**
     * Save settings
     */
    public function saveSettings(string $settingsName, array $options, ?int $schoolId = null): bool
    {
        foreach ($options as $key => $value) {
            $type = $this->detectType($value);
            
            ResultOption::updateOrCreate(
                [
                    'key' => $key,
                    'school_id' => $schoolId,
                ],
                [
                    'name' => ucwords(str_replace('_', ' ', $key)),
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                    'type' => $type,
                ]
            );
        }

        return true;
    }

    /**
     * Get settings by scope
     */
    public function getSettingsByScope(string $scope, ?int $schoolId = null): array
    {
        return ResultOption::getByScope($scope, $schoolId);
    }

    /**
     * Get setting value
     */
    public function getSetting(string $key, mixed $default = null, ?int $schoolId = null): mixed
    {
        $option = ResultOption::where('key', $key)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->first();

        return $option ? $option->getTypedValue() : $default;
    }

    /**
     * Set setting value
     */
    public function setSetting(string $key, mixed $value, ?string $scope = null, ?int $schoolId = null): bool
    {
        $type = $this->detectType($value);
        
        ResultOption::updateOrCreate(
            [
                'key' => $key,
                'school_id' => $schoolId,
            ],
            [
                'name' => ucwords(str_replace('_', ' ', $key)),
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type' => $type,
                'scope' => $scope,
            ]
        );

        return true;
    }

    /**
     * Detect value type
     */
    private function detectType(mixed $value): string
    {
        if (is_array($value)) {
            return 'json';
        }
        
        if (is_bool($value)) {
            return 'boolean';
        }
        
        if (is_numeric($value)) {
            return 'number';
        }
        
        return 'string';
    }

    /**
     * Get all scopes
     */
    public function getScopes(): Collection
    {
        return ResultOption::query()
            ->select('scope')
            ->distinct()
            ->whereNotNull('scope')
            ->pluck('scope');
    }

    /**
     * Delete setting
     */
    public function deleteSetting(string $key, ?int $schoolId = null): bool
    {
        return ResultOption::where('key', $key)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->delete() > 0;
    }
}
