ATOMIC FEATURE ADDITION
Add “Class Teacher” Without Modifying Existing Subject Teacher Logic
1. DO NOT TOUCH (Critical Instruction to Agent)

The agent must:

• Keep existing teacher_assignments (or current subject teacher system)
• Keep existing lesson note logic
• Keep existing policies
• Not rename any tables
• Not remove any columns

This is an additive feature only.

2. NEW FEATURE TO ADD

Add support for:

A teacher assigned as Class Teacher to a specific class (per session).

Nothing else.

3. DATABASE ADDITION (Only This)

Create a new table:

class_teacher_assignments
id BIGINT PRIMARY KEY
teacher_id BIGINT NOT NULL
class_id BIGINT NOT NULL
session_id BIGINT NOT NULL
created_at
updated_at

UNIQUE (class_id, session_id)
INDEX (teacher_id)
INDEX (session_id)


Rules:

• Only one class teacher per class per session
• A teacher can manage multiple classes
• This table does NOT affect subject teacher table

That is all.

4. ADMIN FEATURE (Minimal)

Add simple admin capability:

Assign Class Teacher

Admin selects:

• Class
• Teacher
• Session

System:

• Inserts into class_teacher_assignments
• Prevents duplicate class assignment
• Logs action

No complex UI logic required.

5. AUTHORIZATION LOGIC (Minimal & Safe)

We now add ONE extra permission check.

Wherever the system checks subject teacher authority, modify like this:

For Lesson Notes

Current system likely checks:

Is teacher assigned to subject in this class?


Now update to:

IF teacher is Class Teacher for this class
    → Allow for all subjects

ELSE IF teacher is Subject Teacher for this subject/class
    → Allow

ELSE
    → Deny


That’s it.

Do not rewrite the whole permission layer.

6. CLASS TEACHER PERMISSIONS (For Now)

For this phase, Class Teacher can:

• Submit lesson notes for any subject in that class
• View all lesson notes in that class

Nothing else yet.

Attendance and results will come later.

7. HYBRID CASE

If a teacher:

• Is Class Teacher of Class A
• Is Subject Teacher in Class B

System must:

• Grant full access in Class A
• Grant limited access in Class B

No conflicts.

8. DO NOT MODIFY SUBJECT TEACHER SYSTEM

Important:

Subject teacher system remains:

• As-is
• Same table
• Same logic
• Same naming

We are extending, not replacing.

9. COMPLETION CHECKLIST

This phase is complete when:

✔ Class teacher table exists
✔ Admin can assign class teacher
✔ Only one class teacher per class per session
✔ Lesson note respects class teacher authority
✔ Subject teacher logic still works
✔ No existing feature breaks

10. STRICT BOUNDARY

Do NOT:

❌ Refactor roles
❌ Rename tables
❌ Change teacher_assignments
❌ Modify subject schema
❌ Touch attendance
❌ Touch results

This is a small additive enhancement only.