PRODUCT REQUIREMENTS DOCUMENT (PRD)
Conversion of Existing School System to Secure Multi-Tenant (Database-Per-School) Architecture

1. PURPOSE 09077959833

Convert the existing Laravel-based school management system (students, teachers, subjects, classroom assignments, lesson notes, sessions, terms, enrollments, Filament panels, and frontend work already completed) into a secure, domain-based, database-per-tenant SaaS architecture.

This PRD defines:

Structural changes required

Security requirements

Data isolation model

Operational safeguards

Migration strategy

Risk controls

Validation criteria

This document is written for step-by-step implementation by AI agents or engineering automation systems.

2. BACKGROUND CONTEXT
   2.1 Current System

Current application characteristics:

Laravel backend

Filament admin interface

Single database

One school assumption

Core modules implemented:

Students

Teachers

Subjects (global + classroom assignment)

Teacher subject assignments (session scoped)

Student enrollment (session scoped)

Academic sessions and terms

Lesson notes

Role-based access

Current architecture:

One Application
One Database
One School

3. TARGET ARCHITECTURE
   3.1 Architectural Model
   One Application
   One Codebase
   Many Databases (one per school)
   Each Domain → One School

Strict isolation via:

Separate MySQL database per school

Separate user authentication tables per school

No shared tenant tables

4. TENANCY MODEL
   4.1 Tenancy Type

Database-Per-Tenant model.

No shared-database multi-tenancy.
No tenant_id column strategy.

Each school database is completely independent.

5. SYSTEM COMPONENTS
   5.1 Central System (Control Plane)

The central system manages tenants only.

It must use a dedicated database (central DB).

Central Database Tables
schools

id

name

database_name

database_username

database_password (encrypted at rest)

status (active / suspended)

created_at

updated_at

domains

id

school_id

domain

is_primary

created_at

updated_at

Optional (future-proof):

subscriptions

plans

invoices

audit_logs

5.2 Tenant Databases

Each tenant database contains ALL academic and user data:

users

roles / permissions

students

student_profiles

teachers

classrooms

subjects

classroom_subject

teacher_subject_assignments

student_enrollments

academic_sessions

academic_terms

lesson_notes

assessments (future)

attendance (future)

All existing migrations (except SaaS tables) must be migrated to tenant scope.

6. DOMAIN-BASED TENANT RESOLUTION
   6.1 Request Flow

For every HTTP request:

Extract request host.

Query central database:

SELECT \* FROM domains WHERE domain = host

Retrieve school.

Validate school status.

Decrypt database credentials.

Dynamically set Laravel DB connection.

Continue request lifecycle.

If domain not found:
→ Return 404 (School Not Found)

If school suspended:
→ Return 403

If DB connection fails:
→ Return 503

No system stack traces exposed in production.

7. SECURITY REQUIREMENTS

Security is primary priority.

7.1 Data Isolation

No cross-database joins.

No shared academic tables.

Each tenant has unique DB credentials.

Central DB must not contain academic records.

7.2 Credential Protection

Database passwords encrypted using Laravel encryption.

Decryption occurs only during connection resolution.

No plain text credentials in logs.

7.3 Authentication Isolation

Each tenant DB has its own users table.

Authentication uses tenant DB connection.

Session cookies domain-scoped.

Session fixation protection enabled.

Regenerate session on login.

7.4 Super Admin Isolation

Super admin panel must:

Be accessible only via dedicated domain:

admin.platform.com

Use central database only.

Never resolve tenant DB.

Be protected via IP restriction (recommended).

Enforce 2FA.

7.5 Authorization Controls

Inside tenant:

Strict role-based access:

Admin

Teacher

Student

Teachers cannot access other teachers’ classroom data.

Students can only access their own records.

All lesson notes scoped by:

classroom

subject

teacher

session

7.6 Logging Requirements

Log per request:

Tenant ID

Domain

User ID

IP

Action type

Log:

Failed domain resolution

Failed DB connection

Suspended tenant access attempt

Cross-tenant login attempts

Logs must not contain decrypted credentials.

8. MIGRATION STRATEGY
   Phase 1 — Preparation

Freeze new feature development.

Backup current database.

Export schema.

Create new central database.

Phase 2 — Introduce Tenant Layer

Install tenancy infrastructure.

Separate migration folders:

central_migrations

tenant_migrations

Phase 3 — Refactor Migrations

Move ALL existing academic migrations to tenant migration directory.

Only SaaS tables remain central.

Phase 4 — Database Switching Middleware

Implement middleware:

Resolves tenant

Switches DB connection

Validates status

Aborts on failure

Must execute before:

Authentication

Authorization

Filament panel loading

Phase 5 — Tenant Provisioning Workflow

When super admin creates a school:

Generate unique database name.

Create database.

Create DB user with limited privileges.

Store encrypted credentials.

Run tenant migrations.

Seed default admin user.

Attach domain.

All steps must be transactional where possible.

9. FILAMENT PANEL STRUCTURE
   9.1 Central Panel

Accessible via central domain.

Capabilities:

Create school

Suspend school

Delete school

View school metadata

Reset tenant admin password

Must not load tenant models.

9.2 Tenant Panel

Accessible via school domain.

Includes:

Student management

Teacher management

Subject management

Classroom management

Lesson notes

Academic sessions

Enrollments

All models use default connection after tenant resolution.

No model-level connection overrides allowed.

10. DATA CONSISTENCY RULES

Student enrollment tied to academic session.

Teacher subject assignment tied to session.

Lesson notes tied to:

classroom

subject

session

teacher

Foreign key constraints must exist within tenant DB.

11. PERFORMANCE REQUIREMENTS

DB connection resolved once per request.

Use connection caching within request lifecycle.

Index required on:

foreign keys

enrollment lookups

teacher assignments

session lookups

Avoid N+1 queries in Filament tables.

12. FAILURE SCENARIOS
    Scenario 1: Unknown Domain

Return:

School Not Found

Scenario 2: Suspended School

Return:

School Account Suspended

Scenario 3: Tenant DB Corrupted

Return:

Service Temporarily Unavailable

Alert logged.

13. BACKUP AND RESTORE STRATEGY

Daily backup per tenant DB.

Central DB backed up separately.

Ability to restore single tenant DB without affecting others.

14. TESTING REQUIREMENTS

Create at least two tenants:

Tenant A
Tenant B

Test:

Students in A not visible in B.

Teacher login in A fails in B.

Classroom count differs correctly.

Deleting A does not affect B.

Suspending A blocks access but B remains active.

Perform penetration-style tests:

Attempt manual domain swapping.

Attempt session reuse across domains.

Attempt cross-tenant URL access.

15. NON-FUNCTIONAL REQUIREMENTS

All secrets stored securely.

Production must use HTTPS only.

CSRF protection enabled.

Rate limiting on login routes.

Password hashing via bcrypt or Argon2.

2FA recommended for admin users.

16. RISK MITIGATION

Main risks:

Incorrect DB switching

Shared cache keys across tenants

Session misconfiguration

Filament resolving models before tenancy boot

Mitigation:

Ensure tenancy middleware runs earliest.

Prefix cache keys with tenant ID.

Separate Redis DB if used.

Strict environment separation for staging vs production.

17. ACCEPTANCE CRITERIA

The conversion is successful only if:

Two independent schools operate fully.

Separate domains resolve correctly.

Separate databases confirmed via direct inspection.

No cross-tenant query possible.

All original modules operate unchanged.

School creation automated.

School suspension blocks access instantly.

18. STRATEGIC OUTCOME

After completion:

You will have:

SaaS-capable infrastructure

Enterprise-grade data isolation

Scalable onboarding

Improved security posture

Maintainable long-term architecture
