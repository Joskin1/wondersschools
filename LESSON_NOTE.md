Enterprise PRD – Lesson Notes Module

System: School Management Platform
Module: Lesson Notes Submission & Review
Stack: Laravel 12 + Filament + Cloud Infrastructure
Scale Target: 5,000,000+ concurrent teachers/admins

1. Business Objective (High Priority)

Build a lesson note system that:

• Allows subject teachers to upload PDF/DOC lesson notes
• Allows admin to review, comment, approve/reject
• Enforces week-based submission windows
• Preserves all versions
• Remains fast, stable, and secure even with millions of users online at once

2. Functional Scope
Teacher Capabilities

• Upload lesson notes (PDF/DOC/DOCX)
• Select:
– Subject
– Class
– Week
• Only upload when portal is open
• View approval status
• Re-upload corrected versions

Admin Capabilities

• Open/close submission portals by:
– Week
– Start date
– End date
• Review lesson notes by:
– Class → Week → Subject
• Comment, approve, reject
• Track who has / hasn’t submitted

3. Core Data Model (Scalable & Indexed)
lesson_notes
id (BIGINT)
teacher_id
subject_id
class_id
session_id
term_id
week_number
latest_version_id
status
created_at
updated_at

INDEX (session_id, term_id, week_number)
INDEX (class_id, subject_id)
INDEX (teacher_id)

lesson_note_versions
id (BIGINT)
lesson_note_id
file_path
file_hash
uploaded_by
comment
status
created_at

INDEX (lesson_note_id)

submission_windows
id
session_id
term_id
week_number
opens_at
closes_at
is_open
updated_by
created_at

UNIQUE (session_id, term_id, week_number)

4. Non-Functional Requirements (CRITICAL)
🔐 Security (Zero-Trust Model)

• All file uploads:
– Virus scanned
– MIME-type validated
– Size limited
• Use signed URLs (S3 / Cloudflare R2 / GCS)
• Files never served directly from app server
• Role-based access:
– Teacher: upload only
– Admin: review/approve only
• All actions audited

🚀 Performance (5M Concurrent Users)

Mandatory Architecture:

• Object storage for files (S3 / R2 / GCS)
• Redis for:
– Caching
– Session handling
– Rate limiting
• Queues for:
– Upload processing
– Notifications
– Logging

Never process files synchronously.

⚡ Speed Targets
Action	Max Response Time
Upload form load	< 300ms
Submit lesson note	< 1s (async upload)
Admin review page	< 500ms
Approve / Reject	< 300ms
🧠 Scalability

System must:

• Scale horizontally
• Support:
– Multiple app servers
– Load balancer
• Stateless API layer
• Cache all:
– Submission windows
– Active session/term
– Class/subject mappings

🔄 Reliability

• Uploads retryable
• Queues resilient
• Auto-retries on failure
• Versioning never deletes files
• No single point of failure

5. Infrastructure Blueprint
Required Stack

• Laravel 12
• Filament
• Redis
• Queue Worker (Supervisor / Horizon)
• Object Storage (S3 / R2 / GCS)
• CDN for file delivery
• Load balancer (NGINX / ALB / Cloudflare)

6. Upload Flow (Non-Blocking)

Teacher selects subject/class/week

System checks submission window (cached)

File is uploaded to storage directly via signed URL

App stores metadata

Version record created

Admin notified (async queue)

7. Admin Review Flow (Optimized UI)

• Admin selects:
– Class
– Week
• System loads:
– Only metadata (not file blobs)
• File streamed via CDN

8. Caching Strategy

Cache:

• Active session/term
• Submission windows
• Teacher → subject → class permissions

TTL: 5–15 minutes
Invalidate on update

9. Queue Strategy

Use queues for:

• Notifications
• Audit logs
• Version cleanup
• File validation

Never block UI with queue tasks.

10. Audit & Compliance

Log:

• Who uploaded
• Who approved/rejected
• What was changed
• When it happened

Keep logs append-only.

11. Failure Handling

If:
• Storage fails → retry
• Queue fails → retry
• CDN fails → fallback

System must degrade gracefully, not crash.

12. Acceptance Criteria (Non-Negotiable)

✔ Handles 5M concurrent users
✔ No blocking file uploads
✔ No synchronous heavy jobs
✔ Uploads never lost
✔ Admin UI remains fast
✔ Security enforced everywhere

13. Engineering Priorities (Give to AI Agents)

File upload → signed URLs

Object storage + CDN

Redis caching

Queue workers

Submission window enforcement

Versioning system

Role policies

Audit logging

Load balancing

Stress testing (JMeter / k6)