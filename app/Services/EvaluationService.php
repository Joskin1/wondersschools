<?php

namespace App\Services;

use App\Models\EvaluationSetting;
use App\Models\Score;
use Illuminate\Validation\ValidationException;

class EvaluationService
{
    /**
     * Validate CA and Exam scores against evaluation settings.
     *
     * @param int $academicSessionId
     * @param float $caScore
     * @param float $examScore
     * @throws ValidationException
     */
    public function validateScores(int $academicSessionId, float $caScore, float $examScore): void
    {
        $caSettings = EvaluationSetting::where('academic_session_id', $academicSessionId)
            ->where('name', 'CA')
            ->first();

        $examSettings = EvaluationSetting::where('academic_session_id', $academicSessionId)
            ->where('name', 'Exam')
            ->first();

        if (!$caSettings || !$examSettings) {
            throw ValidationException::withMessages([
                'evaluation' => 'Evaluation settings not configured for this session. Please contact administrator.',
            ]);
        }

        $errors = [];

        if ($caScore > $caSettings->max_score) {
            $errors['ca_score'] = "CA score ({$caScore}) exceeds maximum allowed ({$caSettings->max_score}).";
        }

        if ($examScore > $examSettings->max_score) {
            $errors['exam_score'] = "Exam score ({$examScore}) exceeds maximum allowed ({$examSettings->max_score}).";
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Validate that evaluation settings for a session total 100.
     *
     * @param int $academicSessionId
     * @throws ValidationException
     */
    public function validateSessionTotal(int $academicSessionId): void
    {
        $total = EvaluationSetting::where('academic_session_id', $academicSessionId)
            ->sum('max_score');

        if ($total != 100) {
            throw ValidationException::withMessages([
                'max_score' => "Total evaluation scores must equal 100. Current total: {$total}",
            ]);
        }
    }

    /**
     * Get evaluation settings for a session.
     */
    public function getSessionSettings(int $academicSessionId): array
    {
        $settings = EvaluationSetting::where('academic_session_id', $academicSessionId)
            ->get()
            ->keyBy('name');

        return [
            'ca_max' => $settings->get('CA')?->max_score ?? 40,
            'exam_max' => $settings->get('Exam')?->max_score ?? 60,
        ];
    }

    /**
     * Get total max score for a session.
     */
    public function getSessionTotal(int $academicSessionId): int
    {
        return EvaluationSetting::where('academic_session_id', $academicSessionId)->sum('max_score');
    }
}
