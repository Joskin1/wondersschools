<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'attendances';

    protected $fillable = [
        'class_weekly_attendance_id',
        'session_id',
        'term_id',
        'classroom_id',
        'class_arm_id',
        'department_id',
        'date',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function weeklyAttendance(): BelongsTo
    {
        return $this->belongsTo(ClassWeeklyAttendance::class, 'class_weekly_attendance_id');
    }

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

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class, 'attendance_id');
    }
}
