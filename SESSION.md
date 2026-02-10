Product Requirements Document (PRD)

Module: Academic Session & Term Management
Platform: Laravel 12 + Filament Admin Panel
Audience: Engineering (FVB)
Authoring Context: Senior engineer–level implementation standards

1. Business Objective

Enable the school system to:
• Organize all data by Academic Session → Term
• Preserve historical records permanently
• Allow safe, sequential migration between terms
• Support fast retrieval of records by session/term (e.g., “2024–2025 / First Term”)

2. Core Concepts
Concept	Definition
Academic Session	A full school year (e.g. 2024–2025)
Term	First / Second / Third Term
Active Context	The current session + term where new data is written
3. Functional Requirements
3.1 Session Structure

• Each Academic Session must have exactly 3 Terms
• Only one session and one term can be active at any time
• All records are saved with:
– session_id
– term_id

3.2 Migration Logic (Strict)
From	Allowed	Blocked
First Term	→ Second Term	→ Third
Second Term	→ Third Term	→ First
Third Term	→ First Term (New Session)	→ Any other
3.3 Historical Access

Admin must be able to:
• Filter by:
– Session (e.g. 2024–2025)
– Term (e.g. First Term)
• Retrieve any record from any year in the past

4. Database Design (Atomic Setup)
Step 1 — Create Tables

sessions

id
name (string) // "2024-2025"
is_active (boolean)
start_year (int)
end_year (int)
timestamps


terms

id
session_id (foreign key)
name (enum: First, Second, Third)
order (1,2,3)
is_active (boolean)
timestamps


All academic tables (results, attendance, lesson_notes, etc.) must include:

session_id
term_id

Step 2 — Enforce Constraints

• Only ONE session can have is_active = true
• Only ONE term per session can have is_active = true

Enforce via:
• DB unique indexes
• Application-level guards

5. Filament Panel – Admin UI Requirements
Step 3 — Filament Resources

Create:
• SessionResource
• TermResource

Admin views:
• Active Session Card
• Active Term Badge
• “Migrate Term” Action Button

Step 4 — Migration Action (Filament)

Create a Filament Action:

Action::make('migrateTerm')


Logic:

Fetch current active session

Fetch active term

Validate allowed transition

If invalid → throw error

If valid:
• Deactivate current term
• Activate next term

If current term is Third:
• Auto-create new session
• Auto-create 3 terms
• Set new session + First Term active

6. Security & Permissions
Step 5 — Authorization

Use Laravel Policies:

Role	Can Migrate	Can View
Sudo User	Yes	Yes
Admin	Yes	Yes
Teacher	No	Limited
Student	No	Own data only

Middleware:

->middleware(['auth', 'verified'])

Step 6 — Audit Logging

Every migration must log:

• Who migrated
• From term → To term
• Timestamp
• Session IDs

Table: term_migrations_log

7. Data Integrity & Safety
Step 7 — Prevent Data Loss

• Never delete session or term data
• Old records remain untouched
• New writes always use:

Session::active()->first()
Term::active()->first()

8. Error Handling
Step 8 — Validation Rules

If admin tries invalid migration:

Return:

{
  "error": "Invalid term migration. You must follow the academic sequence."
}

9. Performance & Indexing
Step 9 — Query Optimization

Add indexes:

INDEX(session_id, term_id)
INDEX(is_active)

10. Step-by-Step Implementation Plan

Create sessions & terms migrations

Add session_id & term_id to all academic tables

Create Eloquent Models with:
• active() scopes

Build Filament Resources

Add Migration Action logic

Add Policies + Middleware

Add Audit Logging

Add Indexes

Test transitions

Lock production routes

11. Acceptance Criteria

• Admin can migrate only in correct order
• New session auto-creates on Third → First
• All old data remains queryable
• All academic modules use active session/term context

12. Technical Stack

• Laravel 12
• Filament Admin Panel
• MySQL
• Policy-based RBAC
• Audit Logs Enabled