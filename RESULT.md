You are to review the existing codebase for a Laravel + Filament school management system. Abort or refactor anything already done that contradicts the specification below. Delete unused code paths, redundant migrations, redundant tests, and any logic that does not align with the system’s functional rules. Where any part of the implementation is incomplete or missing, implement it correctly. After implementing, write proper feature and unit tests. All features must be stable, atomic, and database-driven.

SYSTEM REQUIREMENTS
1. Actors and Access

Admin Panel built with Filament

Teacher Panel

Student Panel

Each role must see only relevant models and actions.

2. Class, Teacher, Subject & Student Relations

Each Teacher is assigned to one or more Classes.

A Teacher can only see students belonging to the Classes assigned to them.

A Teacher can only input results for subjects assigned to them for their assigned class(es).

A Student belongs to a Class and is automatically scoped by session and term when results are viewed.

Admin must be able to assign:

Classes → Teachers

Subjects → Teachers per Class

Evaluation formula for each session

3. Evaluation System (Score Composition)

The admin sets evaluation parameters per session, e.g.:

continuous_assessment = 40
exam = 60
total = 100


These values must be stored in DB and dynamic.

Teachers cannot exceed allotted score components.

The system must validate:

CA score ≤ continuous_assessment

Exam score ≤ exam

CA + Exam = total (100)

Changing evaluation structure mid-session must not break existing results. Each evaluation setting must be versioned per session.

4. Result Entry Rules

Results are recorded per student, per subject, per term, per session.

If a result for a student/subject/term already exists, teacher must update rather than create duplicates.

Prevent accidental overwrites unless explicitly confirmed.

5. Term and Session Migration Logic
Definitions

One Session = 3 terms:

First Term

Second Term

Third Term

Migration Rules

System must track: current_session and current_term in a central config table.

Only Admin can migrate.

Migration UI must:

Display current term & session

Ask which term to migrate to

Validate allowed transitions

Allowed Transitions
If current_term = First
   allowed → Second
   blocked → Third or First

If current_term = Second
   allowed → Third
   blocked → First or Second

If current_term = Third
   allowed → First ONLY if session increments
   session = session + 1


If admin attempts illegal migration:

Show error: “Migration to <term> not permitted. Complete terms sequentially.”

When migrating from Third Term → First Term:

Auto generate next session as current_session + 1

Reset current term to First

Carry-over class promotions handled separately (not mandatory here, just design for future extensibility)

6. Tests Required

Delete any existing test that contradicts these rules.

Write tests for:

Unit Tests

Evaluation validation logic

Allowed vs. blocked term migration

Score summation and max limit

Prevent duplicate result creation per student/subject/term/session

Feature Tests

Teacher cannot input scores for subjects not assigned to them

Admin sets evaluation and it affects score forms dynamically

Migration updates session/term correctly

Session increment when migrating from Third → First

7. Additional Robustness Rules

Add these if missing:

Every student result record must include:

student_id

subject_id

teacher_id

term

session

CA score

Exam score

Total score (computed)

Evaluation version ID (foreign key)

Denormalize computed totals only after validation.

Introduce evaluation_settings table with session reference.

8. Developer Constraints

DO NOT hard-code any session, term, or score value.

Everything must be DB-driven and modifiable by admin.

Ensure Filament forms auto-adapt to evaluation settings.

Apply policies to enforce role-based access.

YOUR TASK

Review existing implementation.

Abort or modify any mismatched logic.

Complete missing features.

Delete redundant or conflicting tests.

Implement new logic per specification.

Write passing tests demonstrating correctness.

Refactor for clarity, maintainability, and scalability.