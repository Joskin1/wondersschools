<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $table = 'attendance_records';

    protected $fillable = [
        'attendance_id',
        'student_id',
        'status',
        'remark',
        'arrival_time',
        'departure_time',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function isPresent(): bool
    {
        return $this->status === 'present';
    }

    public function isAbsent(): bool
    {
        return $this->status === 'absent';
    }

    public function isLate(): bool
    {
        return $this->status === 'late';
    }

    public function isExcused(): bool
    {
        return $this->status === 'excused';
    }
}
