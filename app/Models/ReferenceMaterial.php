<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReferenceMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function lessonPlans(): BelongsToMany
    {
        return $this->belongsToMany(LessonPlan::class, 'lesson_plan_reference_materials');
    }
}
