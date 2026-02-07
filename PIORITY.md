# Results System - Priority Rules & Security Specification
## Teacher Assignment, Score Entry & Result Traceability

> [!IMPORTANT]
> This document defines **critical security and access control rules** for the Results System. These rules MUST be implemented at the database, service, policy, and UI levels to ensure data integrity, auditability, and scalability.

---

## Document Context

This specification is part of the **Results System PRD** (Product Requirements Document) and focuses specifically on:
- Teacher assignment and authorization
- Score entry access control
- Data traceability and historical integrity
- Multi-level security enforcement

**Related Documents:**
- [Results System PRD](/home/oluwadamilare/.gemini/antigravity/brain/e7e3e349-6722-42b4-a07b-bca89a3bdac0/results_system_prd.md) - Complete system specification
- [RESULT.md](file:///home/oluwadamilare/Code/Wonders/RESULT.md) - Implementation roadmap

---

## 1. Teacher Creation & Assignment (Admin-Only)

### 1.1. Authorization Rules

| Action | Who Can Perform | Enforcement Level |
|--------|----------------|-------------------|
| Create Teacher accounts | **Admin only** | Filament Policy + Service Layer |
| Assign Teachers to Subjects | **Admin only** | Filament Resource + Policy |
| Assign Teachers to Classes | **Admin only** | Filament Resource + Policy |
| Modify Teacher assignments | **Admin only** | Filament Resource + Policy |

### 1.2. Assignment Data Model

Teachers MUST be assigned to specific **Subject-Class combinations** using a dedicated pivot table:

```php
// Migration: teacher_subject_class
Schema::create('teacher_subject_class', function (Blueprint $table) {
    $table->id();
    $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
    $table->foreignId('classroom_id')->constrained('school_classes')->cascadeOnDelete();
    $table->string('session'); // e.g., "2024/2025"
    $table->timestamps();
    
    // Prevent duplicate assignments
    $table->unique(['teacher_id', 'subject_id', 'classroom_id', 'session'], 
        'unique_teacher_assignment');
    
    // Optimize queries
    $table->index(['teacher_id', 'session']);
    $table->index(['classroom_id', 'subject_id']);
});
```

### 1.3. Example Assignment

**Teacher:** Mrs. Charles (User ID: 42)

| Subject | Classes Assigned | Session |
|---------|-----------------|---------|
| Biology | Year 1, Year 2, Year 3 | 2024/2025 |
| Chemistry | Year 4, Year 5, Year 6 | 2024/2025 |

**Database Records:**
```php
teacher_subject_class:
- { teacher_id: 42, subject_id: 5, classroom_id: 1, session: "2024/2025" } // Biology → Year 1
- { teacher_id: 42, subject_id: 5, classroom_id: 2, session: "2024/2025" } // Biology → Year 2
- { teacher_id: 42, subject_id: 5, classroom_id: 3, session: "2024/2025" } // Biology → Year 3
- { teacher_id: 42, subject_id: 8, classroom_id: 4, session: "2024/2025" } // Chemistry → Year 4
- { teacher_id: 42, subject_id: 8, classroom_id: 5, session: "2024/2025" } // Chemistry → Year 5
- { teacher_id: 42, subject_id: 8, classroom_id: 6, session: "2024/2025" } // Chemistry → Year 6
```

---

## 2. Teacher Panel Access Control

### 2.1. Access Permissions Matrix

When a Teacher logs into the **Teacher Panel**, the system MUST enforce:

| Resource | Teacher CAN Access | Teacher CANNOT Access |
|----------|-------------------|----------------------|
| **Subjects** | ✅ Only assigned subjects | ❌ Other teachers' subjects |
| **Classes** | ✅ Only assigned classes per subject | ❌ Classes not assigned to them |
| **Students** | ✅ Students in assigned class-subject combinations | ❌ Students in other classes |
| **Scores** | ✅ Scores for their assigned subject-class pairs | ❌ Scores for other subjects/classes |
| **Results** | ✅ Preview results for their classes | ❌ Results for other classes |

### 2.2. Three-Level Security Enforcement

> [!CAUTION]
> Access control MUST be enforced at **ALL THREE LEVELS**. Relying on UI filtering alone is a critical security vulnerability.

#### Level 1: Filament Panel Navigation Filtering

```php
// app/Filament/Teacher/Resources/ScoreResource.php
public static function getEloquentQuery(): Builder
{
    $teacher = auth()->user();
    
    return parent::getEloquentQuery()
        ->whereHas('subject', function ($query) use ($teacher) {
            $query->whereIn('id', $teacher->assignedSubjects()->pluck('id'));
        })
        ->whereHas('classroom', function ($query) use ($teacher) {
            $query->whereIn('id', $teacher->assignedClassrooms()->pluck('id'));
        });
}
```

#### Level 2: Laravel Policies / Gates

```php
// app/Policies/ScorePolicy.php
public function update(User $user, Score $score): bool
{
    // Admin can update any score
    if ($user->isAdmin()) {
        return true;
    }
    
    // Teacher can only update scores for their assigned subject-class pairs
    if ($user->isTeacher()) {
        return DB::table('teacher_subject_class')
            ->where('teacher_id', $user->id)
            ->where('subject_id', $score->subject_id)
            ->where('classroom_id', $score->classroom_id)
            ->where('session', $score->session)
            ->exists();
    }
    
    return false;
}
```

#### Level 3: Service Layer Query Scoping

```php
// app/Services/ScoreService.php
public function getScoresForTeacher(User $teacher, string $session, int $term): Collection
{
    // Get teacher's assigned subject-class pairs
    $assignments = DB::table('teacher_subject_class')
        ->where('teacher_id', $teacher->id)
        ->where('session', $session)
        ->get();
    
    // Build query with explicit scoping
    return Score::query()
        ->where('session', $session)
        ->where('term', $term)
        ->where(function ($query) use ($assignments) {
            foreach ($assignments as $assignment) {
                $query->orWhere(function ($q) use ($assignment) {
                    $q->where('subject_id', $assignment->subject_id)
                      ->where('classroom_id', $assignment->classroom_id);
                });
            }
        })
        ->with(['student', 'subject', 'classroom'])
        ->get();
}
```

---

## 3. Score Entry Rules

### 3.1. Automatic Data Scoping

When a Teacher opens the **Score Entry Page** (Filament):

| Data Element | Scoping Rule |
|--------------|--------------|
| **Students** | Only students enrolled in the teacher's assigned classes for the selected session/term |
| **Subjects** | Only subjects the teacher is assigned to teach |
| **Classes** | Only classes assigned to the teacher for the selected subject |
| **Session** | Current active academic session (or selectable from authorized sessions) |
| **Term** | Current term or selectable (1, 2, or 3) |

> [!WARNING]
> Teachers MUST NOT be able to manually select students, subjects, or classes outside their assignments. All dropdowns and filters must be pre-scoped.

### 3.2. Score Record Structure

Every score record MUST include the following fields for complete traceability:

```php
// Migration: scores table
Schema::create('scores', function (Blueprint $table) {
    $table->id();
    $table->foreignId('student_id')->constrained()->cascadeOnDelete();
    $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
    $table->foreignId('classroom_id')->constrained('school_classes')->cascadeOnDelete();
    $table->foreignId('score_header_id')->constrained()->cascadeOnDelete();
    $table->string('session'); // e.g., "2024/2025"
    $table->tinyInteger('term'); // 1, 2, or 3
    $table->decimal('value', 5, 2); // Score value (e.g., 85.50)
    $table->timestamps();
    
    // Ensure unique score per student-subject-header-session-term
    $table->unique([
        'student_id', 
        'subject_id', 
        'score_header_id', 
        'session', 
        'term'
    ], 'unique_score_entry');
    
    // Optimize queries
    $table->index(['session', 'term', 'classroom_id']);
    $table->index(['student_id', 'session', 'term']);
});
```

### 3.3. Score Entry Validation

```php
// app/Http/Requests/StoreScoreRequest.php
public function rules(): array
{
    return [
        'student_id' => 'required|exists:students,id',
        'subject_id' => 'required|exists:subjects,id',
        'classroom_id' => 'required|exists:school_classes,id',
        'score_header_id' => 'required|exists:score_headers,id',
        'session' => 'required|string',
        'term' => 'required|integer|between:1,3',
        'value' => 'required|numeric|min:0|max:100',
    ];
}

public function authorize(): bool
{
    $user = auth()->user();
    
    // Admin can enter any score
    if ($user->isAdmin()) {
        return true;
    }
    
    // Teacher must be assigned to this subject-class combination
    if ($user->isTeacher()) {
        return DB::table('teacher_subject_class')
            ->where('teacher_id', $user->id)
            ->where('subject_id', $this->subject_id)
            ->where('classroom_id', $this->classroom_id)
            ->where('session', $this->session)
            ->exists();
    }
    
    return false;
}
```

---

## 4. Result Traceability & Historical Integrity

### 4.1. Permanent Traceability Requirements

Every score MUST be permanently traceable by the following **5 dimensions**:

| Dimension | Purpose | Example |
|-----------|---------|---------|
| **Student** | Identify whose score it is | Student ID: 123 (John Doe) |
| **Subject** | Identify what was assessed | Subject ID: 5 (Biology) |
| **Class/Year** | Identify the academic level | Classroom ID: 2 (Year 2) |
| **Academic Session** | Identify the school year | "2024/2025" |
| **Term** | Identify the term within the session | Term 2 |

### 4.2. Guaranteed Outcomes

This 5-dimensional traceability ensures:

| Guarantee | Benefit |
|-----------|---------|
| ✅ **Accurate Historical Reports** | Retrieve exact scores for any past session/term |
| ✅ **Safe Data Migrations** | Move students between classes without losing history |
| ✅ **Reliable Analytics** | Generate trend reports across sessions |
| ✅ **No Data Ambiguity** | Every score is uniquely identifiable |
| ✅ **Audit Compliance** | Full accountability for all score entries |

### 4.3. Query Example

**Requirement:** Retrieve Biology scores for Student A in Year 1, Term 1, Session 2024/2025

```php
$scores = Score::where('student_id', 123)
    ->where('subject_id', 5) // Biology
    ->where('classroom_id', 1) // Year 1
    ->where('session', '2024/2025')
    ->where('term', 1)
    ->with('scoreHeader')
    ->get();
```

**Result:** Always returns the **same historical result**, regardless of:
- Student's current class (they may have been promoted)
- Current session (query is for historical data)
- Teacher assignments (historical data is immutable)

---

## 5. Security & Data Integrity Requirements

### 5.1. Security Enforcement Checklist

The system MUST implement the following security measures:

| Requirement | Implementation | Verification |
|-------------|----------------|--------------|
| ✅ **Prevent unauthorized data access** | Policies + Service scoping | Unit tests + Policy tests |
| ✅ **Prevent cross-class data leaks** | Query scoping at all levels | Integration tests |
| ✅ **Validate all inputs** | Form Requests + Rules | Validation tests |
| ✅ **Use DB transactions** | Wrap score saves in transactions | Transaction tests |
| ✅ **Log all changes** | Audit log (who, what, when) | Audit trail verification |

### 5.2. Database Transaction Example

```php
// app/Services/ScoreService.php
public function bulkUpdateScores(array $scores, User $teacher): void
{
    DB::transaction(function () use ($scores, $teacher) {
        foreach ($scores as $scoreData) {
            // Verify authorization
            $this->authorizeScoreEntry($teacher, $scoreData);
            
            // Update or create score
            $score = Score::updateOrCreate(
                [
                    'student_id' => $scoreData['student_id'],
                    'subject_id' => $scoreData['subject_id'],
                    'score_header_id' => $scoreData['score_header_id'],
                    'session' => $scoreData['session'],
                    'term' => $scoreData['term'],
                ],
                ['value' => $scoreData['value']]
            );
            
            // Log the change
            AuditLog::create([
                'user_id' => $teacher->id,
                'action' => 'score_updated',
                'model' => 'Score',
                'model_id' => $score->id,
                'old_value' => $score->getOriginal('value'),
                'new_value' => $score->value,
                'ip_address' => request()->ip(),
            ]);
        }
    });
}
```

### 5.3. Authorization Gate

No score may be **created, updated, or deleted** unless:

```php
// app/Providers/AuthServiceProvider.php
Gate::define('manage-score', function (User $user, Score $score) {
    // Admins can manage any score
    if ($user->isAdmin()) {
        return true;
    }
    
    // Teachers can only manage scores for their assigned subject-class pairs
    if ($user->isTeacher()) {
        $isAssigned = DB::table('teacher_subject_class')
            ->where('teacher_id', $user->id)
            ->where('subject_id', $score->subject_id)
            ->where('classroom_id', $score->classroom_id)
            ->where('session', $score->session)
            ->exists();
        
        return $isAssigned;
    }
    
    // Students cannot manage scores
    return false;
});
```

---

## 6. Implementation Priority Summary

> [!IMPORTANT]
> **Final Priority Statement**

### Core Principles

1. **Teacher Authorization**
   - Teachers can **ONLY** view and enter scores for subjects and classes **explicitly assigned** to them by the Admin
   - No manual selection of students outside assignments
   - No access to other teachers' data

2. **Data Traceability**
   - All scores MUST be stored with: **Student + Subject + Class + Session + Term**
   - Results can **always** be retrieved accurately and securely
   - Historical data is **immutable** and **auditable**

3. **Multi-Level Security**
   - Enforcement at: **UI (Filament) + Policy (Laravel) + Service (Query Scoping)**
   - No single point of failure
   - Defense in depth

### Integration with Results System PRD

This specification integrates with:
- **FR2: Result Computation Engine** - Uses traceable score data
- **FR4: Excel Score Import/Export** - Validates teacher assignments before import
- **NFR4-6: Security Requirements** - Implements role-based access control
- **Section 5: Data & Models** - Extends Score model with assignment validation

---

## 7. Testing Requirements

### 7.1. Unit Tests

```php
// tests/Unit/TeacherAssignmentTest.php
test('teacher can only access assigned subjects', function () {
    $teacher = User::factory()->teacher()->create();
    $subject1 = Subject::factory()->create();
    $subject2 = Subject::factory()->create();
    $class = SchoolClass::factory()->create();
    
    // Assign teacher to subject1 only
    DB::table('teacher_subject_class')->insert([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject1->id,
        'classroom_id' => $class->id,
        'session' => '2024/2025',
    ]);
    
    actingAs($teacher);
    
    expect($teacher->canAccessSubject($subject1))->toBeTrue();
    expect($teacher->canAccessSubject($subject2))->toBeFalse();
});
```

### 7.2. Policy Tests

```php
// tests/Feature/ScorePolicyTest.php
test('teacher cannot update score for unassigned class', function () {
    $teacher = User::factory()->teacher()->create();
    $score = Score::factory()->create();
    
    actingAs($teacher);
    
    $response = $this->putJson("/api/scores/{$score->id}", [
        'value' => 85,
    ]);
    
    $response->assertForbidden();
});
```

### 7.3. Integration Tests

```php
// tests/Feature/ScoreEntryFlowTest.php
test('complete score entry flow respects teacher assignments', function () {
    $teacher = User::factory()->teacher()->create();
    $class = SchoolClass::factory()->create();
    $subject = Subject::factory()->create();
    $student = Student::factory()->create();
    
    // Assign teacher
    DB::table('teacher_subject_class')->insert([
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
        'classroom_id' => $class->id,
        'session' => '2024/2025',
    ]);
    
    actingAs($teacher);
    
    // Teacher should see only assigned students
    $response = $this->get('/teacher/scores/entry');
    $response->assertSee($student->name);
    
    // Teacher can enter score
    $response = $this->post('/teacher/scores', [
        'student_id' => $student->id,
        'subject_id' => $subject->id,
        'classroom_id' => $class->id,
        'session' => '2024/2025',
        'term' => 1,
        'score_header_id' => 1,
        'value' => 85,
    ]);
    
    $response->assertSuccessful();
    $this->assertDatabaseHas('scores', [
        'student_id' => $student->id,
        'value' => 85,
    ]);
});
```

---

## 8. Filament Implementation Guide

### 8.1. Teacher Panel Configuration

```php
// app/Filament/Teacher/TeacherPanelProvider.php
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('teacher')
        ->path('teacher')
        ->authMiddleware(['auth', 'role:teacher'])
        ->resources([
            ScoreResource::class,
            StudentResource::class,
        ])
        ->pages([
            ScoreEntryPage::class,
            MyClassesPage::class,
        ]);
}
```

### 8.2. Score Entry Page (Filament)

```php
// app/Filament/Teacher/Pages/ScoreEntryPage.php
class ScoreEntryPage extends Page
{
    protected static string $view = 'filament.teacher.pages.score-entry';
    
    public $selectedSession;
    public $selectedTerm;
    public $selectedSubject;
    public $selectedClass;
    
    public function mount(): void
    {
        $this->selectedSession = AcademicSession::current()->name;
        $this->selectedTerm = AcademicSession::current()->term;
    }
    
    public function getSubjectsProperty()
    {
        return auth()->user()->assignedSubjects()
            ->where('session', $this->selectedSession)
            ->get();
    }
    
    public function getClassesProperty()
    {
        if (!$this->selectedSubject) {
            return collect();
        }
        
        return auth()->user()->assignedClassrooms()
            ->where('subject_id', $this->selectedSubject)
            ->where('session', $this->selectedSession)
            ->get();
    }
    
    public function getStudentsProperty()
    {
        if (!$this->selectedClass || !$this->selectedSubject) {
            return collect();
        }
        
        return Student::whereHas('sessionData', function ($query) {
            $query->where('session', $this->selectedSession)
                  ->where('term', $this->selectedTerm)
                  ->where('school_class_id', $this->selectedClass);
        })->get();
    }
}
```

---

## Appendix: Related PRD Sections

### A. Functional Requirements Mapping

| Priority Rule | PRD Section | Implementation |
|---------------|-------------|----------------|
| Teacher Assignment | FR1: Result Settings Management | Admin Panel - Teacher Resource |
| Score Entry Access Control | FR2: Result Computation Engine | Service Layer Scoping |
| Excel Import/Export | FR4: Excel Score Import/Export | Teacher Panel - Import Page |
| Audit Logging | NFR5: Audit log all changes | AuditLog Model + Observer |

### B. Database Schema Integration

This specification extends the Results System database schema with:
- `teacher_subject_class` pivot table
- Enhanced `scores` table with session/term/classroom fields
- `audit_logs` table for change tracking

### C. Service Layer Integration

Priority rules are enforced in:
- `ScoreService` - Query scoping and authorization
- `ResultComputationService` - Uses traceable score data
- `ScoreImportService` - Validates teacher assignments before import