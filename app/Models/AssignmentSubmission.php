<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'assignment_id',
        'answers',
        'score',
        'total_points',
        'completed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'integer',
        'total_points' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the student who made this submission.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the assignment this submission belongs to.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Calculate the percentage score.
     */
    public function percentageScore(): float
    {
        if ($this->total_points === 0) {
            return 0;
        }

        return round(($this->score / $this->total_points) * 100, 1);
    }

    /**
     * Grade the submission against the assignment questions.
     *
     * @param \Illuminate\Database\Eloquent\Collection $questions
     * @param array $studentAnswers  e.g. [question_id => selected_option]
     * @return array{score: int, total_points: int}
     */
    public static function grade($questions, array $studentAnswers): array
    {
        $score = 0;
        $totalPoints = 0;

        foreach ($questions as $question) {
            $totalPoints += $question->points;
            $studentAnswer = $studentAnswers[$question->id] ?? null;

            if ($studentAnswer !== null && $question->isCorrect($studentAnswer)) {
                $score += $question->points;
            }
        }

        return [
            'score' => $score,
            'total_points' => $totalPoints,
        ];
    }
}
