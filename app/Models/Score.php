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
        'teacher_id',
        'academic_session_id',
        'term_id',
        'evaluation_setting_id',
        'ca_score',
        'exam_score',
    ];

    protected $appends = ['total_score'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(Staff::class, 'teacher_id');
    }

    public function academicSession()
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function evaluationSetting()
    {
        return $this->belongsTo(EvaluationSetting::class);
    }

    /**
     * Get the total score (CA + Exam)
     */
    public function getTotalScoreAttribute(): float
    {
        return $this->ca_score + $this->exam_score;
    }
}
