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
        'total_score',
        'average_score',
        'position',
        'grade',
        'teacher_remark',
        'principal_remark',
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

    /**
     * Get all scores for this result (student's scores for this term/session)
     */
    public function scores()
    {
        return $this->hasMany(Score::class, 'student_id', 'student_id')
            ->where('academic_session_id', $this->academic_session_id)
            ->where('term_id', $this->term_id);
    }

    /**
     * Calculate and update result totals from scores
     */
    public function calculateTotals(): void
    {
        $scores = $this->scores()->get();
        
        // Calculate total score (sum of all subject totals)
        $this->total_score = $scores->sum('total_score');
        
        // Calculate average score
        $subjectCount = $scores->count();
        $this->average_score = $subjectCount > 0 ? $this->total_score / $subjectCount : 0;
        
        // Calculate grade based on average
        $this->grade = $this->calculateGrade($this->average_score);
        
        $this->save();
    }

    /**
     * Calculate grade from average score
     */
    protected function calculateGrade(float $average): string
    {
        if ($average >= 80) return 'A';
        if ($average >= 70) return 'B';
        if ($average >= 60) return 'C';
        if ($average >= 50) return 'D';
        if ($average >= 40) return 'E';
        return 'F';
    }

    /**
     * Calculate position in class
     */
    public function calculatePosition(): void
    {
        $position = self::where('classroom_id', $this->classroom_id)
            ->where('academic_session_id', $this->academic_session_id)
            ->where('term_id', $this->term_id)
            ->where('total_score', '>', $this->total_score)
            ->count() + 1;
        
        $this->position = $position;
        $this->save();
    }
}
