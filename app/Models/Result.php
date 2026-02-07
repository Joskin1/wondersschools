<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'student_id',
        'academic_session_id',
        'term_id',
        'classroom_id',
        'cache_key',
        'session',
        'term',
        'settings_name',
        'result_data',
        'total_score',
        'average_score',
        'overall_average',
        'position',
        'position_in_class',
        'grade',
        'teacher_remark',
        'principal_remark',
        'generated_at',
    ];

    protected $casts = [
        'result_data' => 'array',
        'total_score' => 'decimal:2',
        'average_score' => 'decimal:2',
        'overall_average' => 'decimal:2',
        'position' => 'integer',
        'position_in_class' => 'integer',
        'term' => 'integer',
        'generated_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function comments()
    {
        return $this->hasMany(ResultComment::class);
    }

    /**
     * Generate a unique cache key for this result
     */
    public static function generateCacheKey(int $studentId, string $session, int $term): string
    {
        return "result_{$studentId}_{$session}_{$term}";
    }
}
