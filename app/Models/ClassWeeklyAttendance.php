<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassWeeklyAttendance extends Model
{
    use HasFactory;

    protected $table = 'class_weekly_attendances';

    protected $fillable = [
        'session_id',
        'term_id',
        'classroom_id',
        'class_arm_id',
        'department_id',
        'week_number',
        'start_date',
        'end_date',
        'status',
        'submitted_at',
        'submitted_by',
        'unlocked_at',
        'unlocked_by',
    ];

    protected $casts = [
        'week_number' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'submitted_at' => 'datetime',
        'unlocked_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'term_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function classArm(): BelongsTo
    {
        return $this->belongsTo(ClassArm::class, 'class_arm_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function unlocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unlocked_by');
    }

    public function dailyAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_weekly_attendance_id');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['submitted', 'locked'], true);
    }

    public function lock(int $userId): void
    {
        $this->update([
            'status' => 'locked',
            'submitted_at' => now(),
            'submitted_by' => $userId,
        ]);
    }

    public function unlock(int $userId): void
    {
        $this->update([
            'status' => 'in_progress',
            'unlocked_at' => now(),
            'unlocked_by' => $userId,
        ]);
    }
}
