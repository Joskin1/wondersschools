TERM MIGRATION FEATURE REQUIREMENTS

I am building a School Management System using Filament Admin Panel. I need a Term Migration feature that controls how the school's academic term progresses.

Current Logic

The academic session consists of three terms:

First Term

Second Term

Third Term

The system must enforce a strict term progression. Admins are not allowed to jump terms.

Required Behaviour

On the Admin Panel, there should be a Term Migration Control where:

The admin clicks a toggle/action button.

A dropdown appears, showing the possible next term.

The admin selects the term to migrate to.

The system validates whether the migration is permitted based on the current term.

VALIDATION RULES
Current Term	Allowed Next Term	Disallowed Actions
First Term	Second Term	Cannot migrate directly to Third Term or First Term
Second Term	Third Term	Cannot migrate to First Term or remain in Second Term
Third Term	First Term	Cannot migrate to Second or Third Term again
SPECIAL RULE ON THIRD TERM

When the admin migrates from Third Term to First Term:

It signifies the start of a new session.

All students must be automatically promoted to their next class.

Students in the terminal class (e.g., SS3, JSS3) must be marked as graduated.

ERROR VALIDATION MESSAGES

If the admin selects an invalid migration option, the system must respond with:

"You cannot migrate to this term. Please follow the term sequence."

Example:

If current term = First Term, and admin selects Third Term → Show the error message.

If current term = Second Term, and admin selects First Term → Show the error message.

EXPECTED ADMIN PANEL FLOW
Current Term: First Term
[ Migrate Term ] button

Click → Dropdown options:
- Second Term

Admin selects "Second Term" → Confirmation modal
→ Migration executes successfully

If current term = Third Term:
Dropdown shows only "First Term"
Selecting "First Term" triggers:
- Student Promotion
- New Session Creation

NON-FUNCTIONAL REQUIREMENTS

No manual editing of the current term field.

Migration must be atomic and irreversible once confirmed.

Logs should record: old term, new term, admin who initiated migration, timestamp.

READY TO USE PROMPT
Implement a Term Migration feature in a Filament Admin Panel with the following rules:

- The system has three terms (First, Second, Third) and term progression must be sequential.
- On the admin panel, provide a button that opens a dropdown listing only the allowed next term.
- Validate the admin’s selection. If an invalid term is selected, show an error message: "You cannot migrate to this term. Please follow the term sequence."
- First Term can only migrate to Second Term.
- Second Term can only migrate to Third Term.
- Third Term can only migrate to First Term, and this migration triggers student promotion and starts a new session.
- Promotion moves each student to the next class. Terminal classes should be marked as graduated.
