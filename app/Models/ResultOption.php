<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultOption extends Model
{
    protected $fillable = [
        'name',
        'key',
        'value',
        'type',
        'scope',
        'school_id',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    /**
     * Get the school this option belongs to
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the typed value based on the type field
     */
    public function getTypedValue(): mixed
    {
        return match($this->type) {
            'number' => is_numeric($this->value) ? (float) $this->value : 0,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Get options by scope
     */
    public static function getByScope(?string $scope = null, ?int $schoolId = null): array
    {
        $query = static::query();
        
        if ($scope) {
            $query->where('scope', $scope);
        }
        
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        
        return $query->get()->mapWithKeys(function ($option) {
            return [$option->key => $option->getTypedValue()];
        })->toArray();
    }
}
