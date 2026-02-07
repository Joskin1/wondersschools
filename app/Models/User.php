<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Staff;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the staff profile for this user
     */
    public function staff()
    {
        return $this->hasOne(Staff::class);
    }

    /**
     * Get teacher assignments (subjects and classrooms)
     */
    public function teacherAssignments()
    {
        return $this->hasManyThrough(
            \Illuminate\Database\Eloquent\Relations\Pivot::class,
            Staff::class,
            'user_id',
            'staff_id',
            'id',
            'id'
        )->from('classroom_subject_teacher');
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a teacher
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Get assigned subjects for this teacher
     */
    public function assignedSubjects(?string $session = null)
    {
        if (!$this->isTeacher() || !$this->staff) {
            return collect();
        }

        $query = \Illuminate\Support\Facades\DB::table('classroom_subject_teacher')
            ->join('subjects', 'classroom_subject_teacher.subject_id', '=', 'subjects.id')
            ->where('classroom_subject_teacher.staff_id', $this->staff->id)
            ->select('subjects.*')
            ->distinct();

        if ($session) {
            $query->where('classroom_subject_teacher.session', $session);
        }

        return $query->get();
    }

    /**
     * Get assigned classrooms for this teacher
     */
    public function assignedClassrooms(?string $session = null, ?int $subjectId = null)
    {
        if (!$this->isTeacher() || !$this->staff) {
            return collect();
        }

        $query = \Illuminate\Support\Facades\DB::table('classroom_subject_teacher')
            ->join('classrooms', 'classroom_subject_teacher.classroom_id', '=', 'classrooms.id')
            ->where('classroom_subject_teacher.staff_id', $this->staff->id)
            ->select('classrooms.*')
            ->distinct();

        if ($session) {
            $query->where('classroom_subject_teacher.session', $session);
        }

        if ($subjectId) {
            $query->where('classroom_subject_teacher.subject_id', $subjectId);
        }

        return $query->get();
    }

    /**
     * Check if teacher can access a specific subject
     */
    public function canAccessSubject(int|Subject $subject, int|Classroom $classroom, string $session): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (!$this->isTeacher() || !$this->staff) {
            return false;
        }

        $subjectId = $subject instanceof Subject ? $subject->id : $subject;
        $classroomId = $classroom instanceof Classroom ? $classroom->id : $classroom;

        return \Illuminate\Support\Facades\DB::table('classroom_subject_teacher')
            ->where('staff_id', $this->staff->id)
            ->where('subject_id', $subjectId)
            ->where('classroom_id', $classroomId)
            ->where('session', $session)
            ->exists();
    }

    /**
     * Check if teacher can access a specific classroom
     */
    public function canAccessClassroom(int|Classroom $classroom, string $session): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (!$this->isTeacher() || !$this->staff) {
            return false;
        }

        $classroomId = $classroom instanceof Classroom ? $classroom->id : $classroom;

        return \Illuminate\Support\Facades\DB::table('classroom_subject_teacher')
            ->where('staff_id', $this->staff->id)
            ->where('classroom_id', $classroomId)
            ->where('session', $session)
            ->exists();
    }
}
