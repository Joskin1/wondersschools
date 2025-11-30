<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentType extends Model
{
    protected $fillable = ['name', 'max_score', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scores()
    {
        return $this->hasMany(Score::class);
    }
}
