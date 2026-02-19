Student Account Creation + Self-Completion via Public Link

No grading. No results. No attendance.

ATOMIC PRD — STUDENT REGISTRATION MODULE
1️⃣ OBJECTIVE

Allow Admin to:

Create a basic student record (minimal data).

Assign student to a class (within active academic session).

Generate a unique registration link.

Allow student/parent to complete full profile using that link.

Automatically activate student after completion.

2️⃣ SYSTEM FLOW
Step 1 — Admin Creates Student

Admin inputs:

Full Name

Classroom

Academic Session

System creates:

Student record

Enrollment record (session-bound)

Registration token

Registration slug

Student status = pending

Step 2 — Admin Clicks “Generate Registration Link”

System generates:

https://schoolportal.com/register/student/{slug}


Example:

/register/student/john-doe-AB12X


Slug format:

slug(full_name) + short random string


Example:

john-doe-k92lx


Token expiration:

3 days (recommended)

Step 3 — Student Completes Registration

Student visits link and fills:

Date of Birth

Gender

Address

Previous School

Parent Name

Parent Phone

Parent Email (optional)

Emergency Contact

After submission:

Student profile table updated

Student marked as active

Registration link deleted (slug, token, and expiration set to NULL)

3️⃣ DATABASE DESIGN
1️⃣ students (Core Identity Table)
Schema::create('students', function (Blueprint $table) {
    $table->id();

    $table->string('full_name');
    $table->string('registration_slug')->unique()->nullable();
    $table->string('registration_token')->nullable();
    $table->timestamp('registration_expires_at')->nullable();

    $table->enum('status', ['pending', 'active'])
          ->default('pending');

    $table->timestamps();
});

// Note: registration_slug, registration_token, and registration_expires_at
// are set during student creation and cleared (set to NULL) after successful
// registration completion. This prevents link reuse and maintains data cleanliness.

2️⃣ student_profiles (Extended Details)
Schema::create('student_profiles', function (Blueprint $table) {
    $table->id();

    $table->foreignId('student_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->date('date_of_birth')->nullable();
    $table->string('gender')->nullable();
    $table->string('address')->nullable();
    $table->string('previous_school')->nullable();

    $table->string('parent_name')->nullable();
    $table->string('parent_phone')->nullable();
    $table->string('parent_email')->nullable();

    $table->timestamps();
});

3️⃣ student_enrollments (Session Binding)

This connects student to class per academic session.

Schema::create('student_enrollments', function (Blueprint $table) {
    $table->id();

    $table->foreignId('student_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->foreignId('classroom_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->foreignId('session_id')
          ->constrained()
          ->cascadeOnDelete();

    $table->timestamps();

    $table->unique(['student_id', 'session_id']);
});


This ensures:

One class per student per session.

4️⃣ MODEL STRUCTURE
Student Model
class Student extends Model
{
    protected $fillable = [
        'full_name',
        'registration_slug',
        'registration_token',
        'registration_expires_at',
        'status',
    ];

    public function profile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}

5️⃣ BUSINESS RULES

Student cannot be assigned twice in same session.

Student status = pending until profile completed.

Registration link expires after defined time.

Expired or deleted links block submission.

Registration link is permanently deleted after successful completion.

Deleted links cannot be regenerated - admin must create new registration if needed.

Slug must be unique (when not NULL).

Deleting student deletes profile + enrollment.

6️⃣ FILAMENT IMPLEMENTATION
StudentResource (Admin)
Create Form:

full_name (required)

classroom (required)

session (required)

On submit:

Generate slug

Generate token

Set expiration

Create enrollment record

Student List Page

Add Action Button:

“Generate Registration Link”

Show modal with full URL

Copy-to-clipboard option

7️⃣ PUBLIC REGISTRATION PAGE (Livewire)

Route:

/register/student/{slug}


Flow:

Validate slug exists and is not NULL

Check token not expired

Show form

Save profile

Update status to active

Delete registration link (set slug, token, and expiration to NULL)

8️⃣ ACCEPTANCE CRITERIA

✔ Admin can create student
✔ Student assigned to class per session
✔ Unique slug generated
✔ Registration link works
✔ Expired links blocked
✔ Student becomes active after completion
✔ Registration link deleted after completion
✔ Deleted links cannot be reused
✔ Registration fields are NULL after successful completion
✔ History preserved via enrollment table

9️⃣ SECURITY CONSIDERATION

Slug alone should not expose ID

Use additional token for verification

Delete registration link after use (set all fields to NULL)

Prevent reuse by clearing all registration-related fields

Validate session consistency

🔟 ARCHITECTURE STATUS AFTER THIS

Layer 1 → Classroom
Layer 2 → Subjects
Layer 3 → Teacher Assignment
Layer 4 → Students (Session Bound)

Your core academic engine is now complete.