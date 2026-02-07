<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'student_id',
        'subject_id',
        'classroom_id',
        'score_header_id',
        'session',
        'term',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'term' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function scoreHeader()
    {
        return $this->belongsTo(ScoreHeader::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get total score for a student in a subject across all headers
     */
    public static function getTotalScore(int $studentId, int $subjectId, string $session, int $term): float
    {
        return static::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('session', $session)
            ->where('term', $term)
            ->sum('value');
    }
}
