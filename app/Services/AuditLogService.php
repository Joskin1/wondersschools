<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Score;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AuditLogService
{
    /**
     * Log a score change
     */
    public function logScoreChange(User $user, Score $score, string $action, $oldValue = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'model' => Score::class,
            'model_id' => $score->id,
            'old_value' => $oldValue ? $this->prepareValueForLog($oldValue) : null,
            'new_value' => $this->prepareValueForLog($score),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Get audit trail for a specific score
     */
    public function getAuditTrail(Score $score): Collection
    {
        return AuditLog::where('model', Score::class)
            ->where('model_id', $score->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get user activity within a date range
     */
    public function getUserActivity(User $user, Carbon $startDate, Carbon $endDate): Collection
    {
        return AuditLog::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent score changes
     */
    public function getRecentScoreChanges(int $limit = 50): Collection
    {
        return AuditLog::byModel(Score::class)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get score changes by action type
     */
    public function getScoreChangesByAction(string $action, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $query = AuditLog::byModel(Score::class)
            ->byAction($action)
            ->with('user');

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Prepare value for logging (extract relevant fields)
     */
    protected function prepareValueForLog($value): array
    {
        if ($value instanceof Score) {
            return [
                'student_id' => $value->student_id,
                'subject_id' => $value->subject_id,
                'classroom_id' => $value->classroom_id,
                'score_header_id' => $value->score_header_id,
                'session' => $value->session,
                'term' => $value->term,
                'value' => $value->value,
            ];
        }

        return is_array($value) ? $value : ['value' => $value];
    }

    /**
     * Get statistics for a user's activity
     */
    public function getUserActivityStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $activities = $this->getUserActivity($user, $startDate, $endDate);

        return [
            'total_actions' => $activities->count(),
            'scores_created' => $activities->where('action', 'score_created')->count(),
            'scores_updated' => $activities->where('action', 'score_updated')->count(),
            'scores_deleted' => $activities->where('action', 'score_deleted')->count(),
            'unique_days' => $activities->pluck('created_at')->map(fn($date) => $date->format('Y-m-d'))->unique()->count(),
        ];
    }
}
