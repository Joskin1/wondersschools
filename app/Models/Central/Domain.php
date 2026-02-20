<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Database\Models\Domain as BaseDomain;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends BaseDomain
{
    use HasFactory;

    /**
     * Always use the central database connection.
     */
    protected $connection = 'central';

    protected $fillable = [
        'domain',
        'tenant_id',
        'is_primary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Get the school that owns this domain.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'tenant_id');
    }
}
