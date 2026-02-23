TENANCY AUTH DEBUG PROMPT (FOR CLAUDE)

You are a senior Laravel 12 + stancl/tenancy v3 + Filament v4 SaaS architect.

I need deep debugging, not suggestions.

Problem Summary:

Multi-database tenancy using stancl/tenancy v3

Sudo users live in landlord DB

School admins are created inside tenant DB

Domains are correctly stored in domains table

Tenant DB is created, migrated, seeded correctly

Email is sent to new school admin with credentials

Issue:

When logging into school-domain/admin:

Tenant admin credentials return “incorrect credentials”.

Sudo credentials successfully log in on the tenant domain.

When logged in via sudo on tenant domain, I see populated central data instead of empty tenant data.

This proves authentication is happening against the landlord DB instead of the tenant DB.

Your task:

Perform a structured root-cause investigation.

PHASE 1 — VERIFY TENANCY INITIALIZATION TIMING

Inspect:

app/Providers/Filament/AdminPanelProvider.php

app/Providers/Filament/TeacherPanelProvider.php

routes/tenant.php

app/Providers/TenancyServiceProvider.php

config/tenancy.php

Determine:

Is InitializeTenancyByDomain middleware registered?

Is PreventAccessFromCentralDomains registered?

Are they placed BEFORE:

EncryptCookies

StartSession

Authenticate

Filament auth middleware

Explain exact middleware order and whether DB switching occurs before auth.

If tenancy runs after authentication, explain why that causes the issue.

PHASE 2 — VERIFY DATABASE CONNECTION DURING LOGIN

Simulate login lifecycle:

Request → middleware stack → DB connection active → auth attempt.

Identify what config('database.default') would be during login request on tenant domain.

Explain whether it resolves to:

landlord connection
OR

tenant connection

Trace exact order of:

DatabaseTenancyBootstrapper

Auth middleware

PHASE 3 — VERIFY TENANT ADMIN CREATION CONTEXT

Inspect:

TenantAdminResource

CreateAction logic

Any manual tenancy()->initialize() calls

Confirm:

Is the tenant admin user actually being created inside the tenant DB?

Or accidentally inside landlord DB?

Explain clearly which DB connection is active at user creation time.

PHASE 4 — VERIFY PANEL DOMAIN BEHAVIOR

Determine:

Is Filament panel domain bound statically?
Is it resolving tenancy dynamically per request?
Is panel boot() running before tenancy initialization?

Explain whether Filament panel registration interferes with tenancy.

REQUIRED OUTPUT FORMAT

Root Cause (single primary cause)

Secondary contributing factors (if any)

Exact file and line causing failure

Correct middleware order

Corrected code snippet

Verification steps

Why sudo login works but tenant admin fails

Do not give generic advice.
Trace execution lifecycle step-by-step.
Be precise.

If the issue is middleware ordering, clearly demonstrate it.

If the issue is DB context during admin creation, prove it.

If the issue is panel boot timing, explain it.

Think like a production SaaS engineer debugging authentication isolation.