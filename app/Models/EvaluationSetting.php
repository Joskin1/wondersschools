<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationSetting extends Model
{
    use HasFactory;

    protected $fillable = ['academic_session_id', 'name', 'max_score'];

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function scores()
    {
        return $this->hasMany(Score::class);
    }

    /**
     * Get the total max score for the session this setting belongs to
     */
    public function getSessionTotalAttribute(): int
    {
        return self::where('academic_session_id', $this->academic_session_id)
            ->sum('max_score');
    }
}
