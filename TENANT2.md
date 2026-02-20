TECHNICAL EXECUTION BLUEPRINT
Multi-Tenant Conversion – Database-Per-School Architecture

System: Laravel + Filament School Management System
Goal: Convert existing single-school system into secure SaaS multi-tenant architecture
Isolation Model: Database-Per-Tenant
Security Level: Production-grade

1. IMPLEMENTATION STRATEGY OVERVIEW

Execution is divided into controlled stages:

Environment Preparation

Central Database Creation

Tenancy Infrastructure Installation

Migration Refactor

Tenant Resolution Layer

Tenant Provisioning Automation

Filament Panel Separation

Security Hardening

Caching Strategy

Testing & Isolation Validation

Deployment Architecture

Each phase must be completed and verified before moving to the next.

2. PHASE 1 — ENVIRONMENT PREPARATION
2.1 Backup & Freeze

Before modifying architecture:

Backup current production database.

Backup entire project repository.

Create staging environment.

Disable new feature development.

2.2 Separate Environments

You must have:

Local

Staging

Production

Each environment must have:

Separate central database

Separate Redis (if used)

Separate queue workers

No shared credentials across environments.

3. PHASE 2 — CENTRAL DATABASE DESIGN

Create new database:

school_platform

This database will contain only:

schools
Field	Type	Notes
id	bigint	primary key
name	string	school name
database_name	string	tenant DB name
database_username	string	unique
database_password	text	encrypted
status	enum	active/suspended
created_at	timestamp	
updated_at	timestamp	
domains
Field	Type
id	bigint
school_id	FK
domain	string (unique)
is_primary	boolean
created_at	timestamp
updated_at	timestamp

IMPORTANT:

Central database must never contain student or teacher records.

No academic data allowed here.

4. PHASE 3 — TENANCY INFRASTRUCTURE

Use a mature tenancy system. Recommended:

Stancl Tenancy (database-per-tenant support)

Or custom middleware (if fully controlled)

If using Stancl:

Configure database mode

Disable shared tenant tables

Disable path-based identification

Enable domain-based identification only

5. PHASE 4 — MIGRATION RESTRUCTURE

Current state:
All migrations in /database/migrations

New structure:

/database/migrations/central
/database/migrations/tenant
5.1 Move Migrations

Move ALL academic tables to tenant folder:

users

students

teachers

subjects

classroom_subject

teacher_subject_assignments

enrollments

sessions

terms

lesson_notes

roles

permissions

Only SaaS tables remain in central folder.

5.2 Enforce Foreign Keys

Inside tenant DB:

student_enrollments → student_id (FK)

teacher_subject_assignments → teacher_id (FK)

lesson_notes → subject_id (FK)

classroom_subject → classroom_id (FK)

All constraints must be strict.

6. PHASE 5 — TENANT RESOLUTION MIDDLEWARE

Create middleware:

ResolveTenantFromDomain

Execution logic:

Extract host from request.

Query central DB:

SELECT * FROM domains WHERE domain = request_host

If null → abort 404.

Retrieve school.

If status != active → abort 403.

Decrypt DB password.

Dynamically configure DB connection.

Set default connection to tenant.

Must run before:

Authentication

Filament service provider

Session start

7. PHASE 6 — TENANT PROVISIONING WORKFLOW

When super admin creates a new school:

Step 1 — Generate Unique DB Name

Example:

school_acme_2026
Step 2 — Create Database

Execute SQL:

CREATE DATABASE school_acme_2026;
Step 3 — Create Dedicated DB User
CREATE USER 'school_acme_user'@'%' IDENTIFIED BY 'strongpassword';
GRANT ALL PRIVILEGES ON school_acme_2026.* TO 'school_acme_user'@'%';

Principle: least privilege.

Step 4 — Encrypt Credentials

Use Laravel encryption before storing password.

Never store plain text.

Step 5 — Run Tenant Migrations

Execute:

php artisan tenants:migrate

Or programmatic migration trigger.

Step 6 — Seed Default Admin

Seed:

Admin role

Super admin user for that school

Force password change on first login.

Step 7 — Attach Domain

Insert into domains table.

All steps must:

Log success/failure

Roll back on failure

8. PHASE 7 — FILAMENT PANEL SEPARATION
8.1 Central Panel

Domain:

admin.platform.com

Features:

Create school

Suspend school

View stats

Reset school admin password

Must:

Use central DB only

Never resolve tenant

8.2 Tenant Panel

Domain:

schooldomain.com

Must:

Resolve tenant DB before Filament boots

Use tenant DB connection

Load only tenant models

Ensure:

No static model connection overrides.

No cross-tenant caching.

9. CACHING STRATEGY (CRITICAL)
9.1 Cache Isolation

All cache keys must be prefixed:

tenant_{id}_key

If using Redis:

Option A:

Separate Redis DB per tenant.

Option B:

Prefix strategy.

9.2 What to Cache

Safe to cache:

Academic sessions

Classroom lists

Teacher subject assignments

Do NOT cache:

Authentication tokens without tenant prefix

User role checks without tenant scoping

9.3 Mobile App Caching Strategy

For future mobile app:

Cache static config (school info)

Cache session lists

Use API endpoints with ETag support

Enable HTTP cache headers

Never cache sensitive student data locally without encryption.

10. SECURITY HARDENING CHECKLIST
10.1 HTTP Security

HTTPS enforced.

HSTS enabled.

Secure cookies.

SameSite=strict.

10.2 Session Security

Regenerate session on login.

Domain-scoped session cookie.

Expire idle sessions.

10.3 Authentication

Rate limit login.

Lock account after X failed attempts.

Enforce strong password policy.

Enable 2FA for tenant admins.

10.4 Authorization

Use policies for:

LessonNote

Enrollment

TeacherSubjectAssignment

Never rely only on UI restrictions.

10.5 Logging

Log:

Tenant ID

User ID

IP

Action

Timestamp

Alert if:

Cross-domain login attempt

DB connection error

Privilege escalation attempt

11. TESTING PROTOCOL

Create:

Tenant A
Tenant B

Verify:

✔ Student in A not visible in B
✔ Teacher login in A fails in B
✔ Different student counts per DB
✔ Suspending A blocks access
✔ B remains unaffected
✔ Session cookie not reusable across domains

Manual penetration test:

Change domain header manually

Try accessing another school’s URL

Attempt cached session replay

All must fail safely.

12. PERFORMANCE CONSIDERATIONS

Resolve DB only once per request.

Use eager loading in Filament.

Index foreign keys.

Index enrollment by session.

Monitor slow query logs.

13. DEPLOYMENT ARCHITECTURE

Recommended stack:

Nginx

PHP-FPM

MySQL (or managed DB)

Redis

Supervisor for queues

DNS:

Each school domain:

A record → platform server IP

Nginx:

Single Laravel root handling all domains.

Tenant resolution occurs inside application.

14. FAILURE HANDLING

Unknown domain → 404
Suspended school → 403
DB connection failure → 503

No debug traces in production.

15. RISK CONTROLS

Major risks:

DB switching too late in lifecycle

Shared cache contamination

Filament boot before tenancy

Accidental cross-tenant joins

Mitigation:

Register tenancy middleware globally.

Disable debug mode in production.

Code review tenant-aware queries.

Automated integration tests.

16. SUCCESS CRITERIA

The migration is complete only when:

Multiple domains function independently.

Separate DB per school verified manually.

Full student-teacher-subject flow works.

No cross-tenant access possible.

School creation is automated.

Suspension is immediate.

Backups restore single tenant successfully.

FINAL RESULT

After execution:

You will have:

SaaS-ready school platform

Enterprise-grade isolation

Domain-based onboarding

Scalable tenant provisioning

Secure multi-database architecture

Clean separation of control plane and tenant plane