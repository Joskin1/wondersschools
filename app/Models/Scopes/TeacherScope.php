<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TeacherScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user && $user->staff && $user->staff->role === 'teacher') {
            // Get IDs of classrooms assigned to this teacher
            $classroomIds = $user->staff->classrooms()->pluck('classrooms.id');
            
            // Apply scope based on model type
            if ($model instanceof \App\Models\Student) {
                $builder->whereIn('classroom_id', $classroomIds);
            } elseif ($model instanceof \App\Models\Classroom) {
                $builder->whereIn('id', $classroomIds);
            }
        }
    }
}
