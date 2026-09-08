<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to filter only active class groups.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order class groups.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order', 'asc')->orderBy('name', 'asc');
    }

    /**
     * Get all classrooms in this group.
     */
    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'class_group_id');
    }

    /**
     * Get all teacher subject assignments for classrooms in this group.
     */
    public function assignments(): HasManyThrough
    {
        return $this->hasManyThrough(
            TeacherSubjectAssignment::class,
            Classroom::class,
            'class_group_id',
            'classroom_id'
        );
    }
}
