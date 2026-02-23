TIMS — Deep Architectural Audit Report
1. ARCHITECTURE SUMMARY
TIMS is a Laravel 12 multi-tenant academic management SaaS platform (School Information System). It serves Nigerian higher-education institutions. Tenancy is implemented via stancl/tenancy v3.8 in single-database mode — all tenants share one MySQL database; rows are scoped by a tenant_id column automatically via the BelongsToTenant Eloquent concern.

There are currently two active tenants:

Toolz College — domain tims.test / komu.edu.ng
Queen Mabel University (BTSKAD) — domain tims-2.test / btskad.edu.ng
Tenant identity is resolved per HTTP request by domain name using InitializeTenancyByDomain middleware. There is no "team" concept (no Jetstream, no custom teams). The concept that might be called "frontend team-based" in the audit prompt refers to per-tenant theming — where one tenant (BTSKAD) gets a completely different Laravel theme loaded via hexadog/laravel-themes-manager.

2. TENANCY CONFIGURATION MAP
Package
stancl/tenancy v3.8 — composer.json:42

Mode
Single-database (shared MySQL). The DatabaseTenancyBootstrapper is commented out in config/tenancy.php:27, which means no database switching occurs. All tenant data lives in one database, scoped by tenant_id.

Central Domain List

'central_domains' => ['127.0.0.1', 'localhost'],
config/tenancy.php:18

ID Generator

'id_generator' => Stancl\Tenancy\UUIDGenerator::class,
Tenant IDs are UUIDs. config/tenancy.php:9

Tenant Model
App\Models\Tenant — extends Stancl\Tenancy\Database\Models\Tenant, implements TenantWithDatabase, uses HasDatabase, HasDomains, HasScopedValidationRules. app/Models/Tenant.php

Bootstrappers Active
Bootstrapper	Status
DatabaseTenancyBootstrapper	DISABLED (commented out)
CacheTenancyBootstrapper	DISABLED (commented out)
FilesystemTenancyBootstrapper	DISABLED (commented out)
QueueTenancyBootstrapper	ENABLED
RedisTenancyBootstrapper	DISABLED (commented out)
App\Tenancy\ConfigBootstrapper	ENABLED (custom)
config/tenancy.php:26-33

Custom ConfigBootstrapper
This is the heart of tenancy initialization. When a request hits a tenant domain, it runs:

Sets app.url to the tenant's domain (forced HTTPS in production)
Sets app.name from the settings table for that tenant
Forces the root URL for URL generation
Configures the mailer from.address as admin@<tenant-domain>
Injects tenant-specific payment processor keys (Paystack, Remita) from the settings table
Configures disk URLs to point to the tenant's URL
Activates a Blade theme for BTSKAD tenant (default = no theme override for KOMU)
app/Tenancy/ConfigBootstrapper.php

Connection Configuration
Central connection: mysql (default), env('DB_CONNECTION', 'mysql')
config/tenancy.php references env('DB_CONNECTION', 'central') — but the actual .env uses mysql, so there is effectively one connection for everything
No separate central connection is defined in config/database.php
Notable extra connections: mysql-testing (test DB), moodle-mysql (Moodle integration)
Tenant Migration Path

'migration_parameters' => [
    '--path' => [database_path('migrations/tenant')],
],
However, the database/migrations/tenant/ directory does not exist. All migrations live in database/migrations/ — there is no structural separation of central vs tenant migrations. All tables have tenant_id columns added via BelongsToTenant.

Active Tenancy Feature

Stancl\Tenancy\Features\ViteBundler::class
This enables Vite to be aware of tenancy context for asset compilation.

3. TEAM + TENANT RELATIONSHIP MODEL
There are no "teams" in this application. There is no Laravel Jetstream Teams, no custom Team model, no team_id column anywhere.

The "team-based frontend" described in the audit prompt refers to the two-tenant architecture:


┌───────────────────────────────────────────┐
│ Single MySQL Database                     │
│                                           │
│  ┌──────────────────┐  ┌──────────────────┐
│  │  Tenant: KOMU    │  │  Tenant: BTSKAD  │
│  │  tenant_id = X   │  │  tenant_id = Y   │
│  │  komu.edu.ng     │  │  btskad.edu.ng   │
│  └──────────────────┘  └──────────────────┘
└───────────────────────────────────────────┘
Tenant ≠ Team. Tenant IS the top-level isolation boundary. Each tenant gets its own settings, themes, payment processors, and scoped data rows.

The Tenant model in app/Models/Tenant.php contains hardcoded domain arrays and identity checks:


public const KOMU_DOMAINS = ['tims.test', 'komu.edu.ng'];
public const BTSKAD_DOMAINS = ['tims-2.test', 'btskad.edu.ng'];
These are referenced throughout the app to branch behavior.

4. PROVISIONING FLOW DIAGRAM
Tenant creation is performed by the Artisan command app:create-tenant — app/Console/Commands/CreateTenant.php.


php artisan app:create-tenant
         │
         ▼
  Collect inputs:
  - name (e.g. "Veritas University")
  - domain (e.g. "veritas.edu.ng")
  - collegiate system toggle
  - academic session seed toggle
  - score types seed toggle
  - admin prompt toggle
  - semester registration mode
         │
         ▼
  DB::transaction {
    ┌─────────────────────────────────────────────────────┐
    │ 1. Tenant::create(['name', 'uses_collegiate_system'])│
    │    → stores in central `tenants` table               │
    │    → UUID auto-generated                             │
    ├─────────────────────────────────────────────────────┤
    │ 2. $tenant->domains()->create(['domain' => $domain]) │
    │    → stores in central `domains` table               │
    ├─────────────────────────────────────────────────────┤
    │ 3. tenancy()->initialize($tenant)                    │
    │    → fires TenancyInitialized event                  │
    │    → BootstrapTenancy listener runs:                 │
    │       - QueueTenancyBootstrapper                     │
    │       - ConfigBootstrapper                           │
    ├─────────────────────────────────────────────────────┤
    │ 4. (optional) Seed Programs + SessionInfo            │
    │    → 2 Programs (Undergraduate, Postgraduate)        │
    │    → 1 JUPEB Program                                 │
    │    → 1 SessionInfo per program                       │
    ├─────────────────────────────────────────────────────┤
    │ 5. (optional) Seed ScoreTypes                        │
    ├─────────────────────────────────────────────────────┤
    │ 6. (optional) Create Admin user via CreateNewStaff   │
    │    → Creates Staff record                            │
    │    → Creates User record linked to Staff             │
    ├─────────────────────────────────────────────────────┤
    │ 7. SettingsService::createOrUpdate(                  │
    │      'course-registration.allow-current-semester...' │
    │    )                                                 │
    └─────────────────────────────────────────────────────┘
  }
         │
         ▼
  Manual next steps printed:
  - Add domain to TrustHosts
  - Add isXxx() method to Tenant model
  - Add case to getMailMarkdownPath()
  - Run php artisan theme:make (for BTSKAD-style)
Key observation: There is no automatic database creation, no database migration, no database seeding triggered by TenantCreated event. All jobs in the TenantCreated pipeline are commented out in app/Providers/TenancyServiceProvider.php:29-34. This is consistent with single-database mode — no separate DB needs to be created.

Events triggered: TenancyInitialized → BootstrapTenancy listener. All other lifecycle event listeners are empty arrays.

5. DATA ISOLATION MODEL
Mechanism
Stancl\Tenancy\Database\Concerns\BelongsToTenant — used in 41 models, including:

User (at app/User.php:28)
Student (at app/Models/Student.php:37)
Setting
FeeHead, Payment, Program, Course, StudentGroup, SessionData, WaivedFee, CustomForm\Form, etc.
This trait:

Adds a global scope that auto-appends WHERE tenant_id = ? to all queries
Auto-assigns tenant_id on model creation
What is NOT tenant-scoped
The tenants table (central)
The domains table (central)
Validation rule seeders (ValidationRuleSeeder is called before any tenant init in DatabaseSeeder)
How isolation is enforced

Request arrives at btskad.edu.ng
→ InitializeTenancyByDomain resolves Tenant record
→ Sets tenant() helper to BTSKAD tenant
→ ConfigBootstrapper runs
→ BelongsToTenant global scope adds WHERE tenant_id = 'btskad-uuid'
→ All Eloquent queries automatically scoped
Risk: No database-level isolation
Since all data is in one database with application-level scoping, a bug (e.g., forgetting BelongsToTenant, bypassing global scope, raw queries) can expose cross-tenant data. This is the inherent risk of single-database multi-tenancy.

6. FRONTEND ARCHITECTURE MODEL
Dual-theme system
The frontend has two distinct visual themes driven by hexadog/laravel-themes-manager:

Tenant	Theme	Path
KOMU (Toolz College)	Default (no theme override)	resources/views/
BTSKAD (Queen Mabel)	damms005/btskad	themes/damms005/btskad/resources/views/
Theme activation occurs in ConfigBootstrapper::setTheme():


match (Customer::getCurrentTenant()) {
    EnumsCustomer::BTSKAD => ThemesManager::set('damms005/btskad'),
    default => null,
};
app/Tenancy/ConfigBootstrapper.php:44-47

View resolution
When a theme is active, hexadog/laravel-themes-manager prepends the theme's resources/views/ directory to the view lookup stack. So view('homepage') resolves to:

BTSKAD: themes/damms005/btskad/resources/views/homepage.blade.php
KOMU: resources/views/homepage.blade.php
The BTSKAD theme has its own complete layout, nav, footer, and Livewire component views.

Layout structure (KOMU default)
Public frontend: resources/views/master.blade.php — Tailwind + Vite, hero image section, nav/footer partials
Student portal: portal.students.master (blade template in resources/views/portal/)
Admin portal: Filament v5 (/staff/dashboard) — Laravel Filament Panel
Branding stored in settings table
Per-tenant branding (logo, favicon, site title, contact details, color scheme) is stored in the settings table (scoped by tenant_id) and loaded via SettingsService. The Filament panel reads site.title for its brand name, site.favicon for the favicon, and colors are resolved before tenancy init via Customer::getTenantFromDomainName().

Static per-tenant color scheme
The Filament admin panel applies different primary colors:

BTSKAD: Color::Rose
KOMU (default): Color::Green
app/Providers/Filament/AppPanelProvider.php:85-98

7. AUTHENTICATION FLOW
Three authentication systems coexist
1. Staff Authentication (via Filament)
Login: POST /staff/dashboard/login (Filament panel)
Guard: web (default Laravel guard)
Backed by users table with staff_id FK → staffs table
User::canAccessPanel() checks staff_id or a specific permission
StaffAuthenticationGuardMiddleware protects /staff/admin/* routes by verifying Auth::check() AND that $user->staffModel is non-null
2. Student Authentication (custom)
Login: GET/POST /students/login/ → StudentAuthenticationController
Guard: web (same guard, same session)
Backed by users table with student_id FK → students table
Login accepts: email, matric number, application number, or JAMB registration number
password_verify() used directly against stored hash
StudentAuthenticationGuardMiddleware checks Auth::check() and redirects to student.login
3. Staff Registration (Breeze-derived)
POST /staff/register → RegisteredUserController
Creates a NewStaffUserDto and calls CreateNewStaff::execute()
Email uniqueness is scoped: Rule::unique('users')->where('tenant_id', tenant('id'))
Authentication scope
users table is in the shared database, scoped by tenant_id via BelongsToTenant
There is no central authentication — users are always tenant-scoped
Same Laravel web session guard is used for both staff and students — distinguished by whether user->staff_id or user->student_id is set
Filament adds InitializeTenancyByDomain as persistent middleware to ensure tenancy is initialized for Livewire SPA updates
Request → DB lifecycle

HTTPS request → nginx/Vite
  → Laravel Kernel (global middleware: TrustProxies, CORS, ValidatePostSize)
  → Route resolution
  → [web] middleware group: HttpsProtocol, EncryptCookies, Session, CSRF
  → InitializeTenancyByDomain (highest priority, prepended by TenancyServiceProvider)
      → Looks up Domain in central DB by request host
      → Finds associated Tenant record
      → Fires TenancyInitialized event → BootstrapTenancy
          → QueueTenancyBootstrapper
          → ConfigBootstrapper (sets app.url, theme, payment config, mail)
  → PreventAccessFromCentralDomains (blocks central domain access)
  → Controller/Livewire component
      → Eloquent models auto-scope WHERE tenant_id = ?
      → Response
8. CACHE & FILESYSTEM ISOLATION
Cache
Default driver: file (env('CACHE_DRIVER', 'file')) — config/cache.php:19
CacheTenancyBootstrapper is disabled — there is no automatic cache prefixing per tenant
Manual workaround: SettingsService::getCacheKey() manually prefixes keys with request()->host() . tenant('id') . $key — app/Services/SettingsService.php:80-85
Student model cache keys also manually include StudentID (which is inherently tenant-scoped)
Risk: Any cache call using generic keys (e.g., Cache::remember('some-key', ...)) is NOT tenant-isolated
Filesystem
FilesystemTenancyBootstrapper is disabled — no automatic storage path suffixing
Manual workaround: ConfigBootstrapper::makeDiskUrlsTenantAware() sets disk URLs to use config('app.url') (which is set per-tenant) — app/Tenancy/ConfigBootstrapper.php:81-95
This means file URLs are tenant-aware, but file paths (on disk) are shared — files for both tenants physically coexist in the same storage/ directories
The filesystems.php config shows a spatie-media-library-files disk defined twice (second definition overrides first — likely a post-hack placeholder introduced after 27th Jan 2026 incident mentioned in the comment) — config/filesystems.php:128-131
Redis
Redis client: predis
RedisTenancyBootstrapper is disabled — no Redis key prefixing
Default Redis DB: 0, Cache Redis DB: 1
S3 / GCS
Both S3 and Google Cloud Storage are configured but used based on deployment context, not tenant
9. SECURITY ANALYSIS
Implemented Safeguards
Safeguard	Implementation
PreventAccessFromCentralDomains	Applied to all routes in tenant.php and auth.php. Blocks requests to 127.0.0.1 and localhost from reaching tenant routes.
InitializeTenancyByDomain	Made highest priority middleware via prependToMiddlewarePriority() in TenancyServiceProvider
BelongsToTenant global scope	Automatically scopes 41 models; prevents cross-tenant data leakage via Eloquent
Scoped uniqueness validation	Staff registration validates email uniqueness scoped to tenant_id
CSRF protection	VerifyCsrfToken in web middleware group
Cookie encryption	EncryptCookies in web middleware group
HTTPS enforcement	HttpsProtocolMiddleware in web middleware; URL::forceScheme('https') in production
Spatie Honeypot	Applied to contact form: ProtectAgainstSpam middleware
Impersonation guard	canImpersonateUser() checks can('impersonate student') permission
Strict Eloquent model	Model::shouldBeStrict(!app()->isProduction()) — throws on lazy loading in dev
HTML purification	mews/purifier available; used in views via clean() helper
Password hashing	password_verify() for student auth; Laravel's default hasher for staff
Model::automaticallyEagerLoadRelationships()	Enabled globally — prevents N+1 and reduces surface for timing-based data inference
Identified Risks
Risk	Location	Severity
No cache isolation bootstrapper active	config/tenancy.php:28	Medium — generic cache keys are not tenant-scoped
No filesystem path isolation	config/tenancy.php:29	Low-Medium — file URLs are scoped but physical storage paths are shared
Hardcoded domain constants	app/Models/Tenant.php:17-20	Low — adding a new tenant requires code changes and deployment
TenantMaintenanceMiddleware is commented out	app/Http/Kernel.php:40	Low — maintenance mode for BTSKAD does not apply unless manually re-enabled
Raw whereRaw in student login	StudentAuthenticationController.php:91	Low — parameterized, so no SQL injection risk; but fragile
Duplicate disk definition	config/filesystems.php:128	Low — spatie-media-library-files is defined twice; second definition silently overrides the first
Post-hack placeholder disks	config/filesystems.php:122-135	Medium — dropbox disk points to missing-media/ rather than actual Dropbox storage, indicating unresolved data loss from January 2026 incident
Slow query notification emails hardcoded to developer email	AppServiceProvider.php:183	Low — production hardcoded damms005@gmail.com
Session not domain-isolated	Default Laravel session config	Low-Medium — both tenants share the same session cookie domain in development
10. PERFORMANCE DESIGN
DB Switching
None — single database, no connection switching overhead.

Eager Loading
Model::automaticallyEagerLoadRelationships() enabled globally in AppServiceProvider
Student model always loads sessionData.department.faculty and currentSessionData.department.faculty via protected $with
Query Caching
SettingsService uses Cache::memo()->remember(...) with 6-hour TTL per setting, keyed by host + tenant_id + key
Student::getAuthenticatedStudent() uses cache()->rememberForever(...) keyed by auth()->id()
Student::getDepartmentAt() and getLevelAt() use short 10-second memo cache
Student::getPayableAcademicSessions() uses 10-second memo cache
Slow Query Detection
Production-only DB::listen() that fires a notification email when any query exceeds 500ms — app/Providers/AppServiceProvider.php:164-205. Rate-limited to one notification per 6 hours via Cache.

Scaling Assumptions
The architecture assumes a small number of tenants (currently 2). The hardcoded DOMAIN_MAP and hardcoded isKomu() / isBtskad() methods make it non-scalable for many tenants without code changes.
Single database is a bottleneck at scale — but appropriate for the current tenant count.
Queue tenancy bootstrapper is enabled, so background jobs are tenant-aware.
OUTPUT 8: FULL TECHNICAL PRD
TIMS — Implementation-Driven Technical PRD
System Name: TIMS (Tenant Information Management System)
License: Proprietary — Anchis & Kolbins Company
Platform: Laravel 12, PHP 8.3+, MySQL, Livewire 4, Filament 5, stancl/tenancy 3.8

PRD Section 1: Tenancy Architecture
Pattern: Single-database multi-tenancy using stancl/tenancy v3.8 in non-database-switching mode.

Implementation steps:

Install stancl/tenancy and publish config.
Set DatabaseTenancyBootstrapper to commented out (intentional — single DB mode).
Enable only QueueTenancyBootstrapper and a custom ConfigBootstrapper.
Implement App\Models\Tenant extending BaseTenant, implementing TenantWithDatabase, using HasDatabase, HasDomains, HasScopedValidationRules.
Store tenant metadata (name, uses_collegiate_system) as JSON in the data column of the tenants table.
Store domains in the central domains table with FK to tenants.
Generate tenant IDs as UUIDs using Stancl\Tenancy\UUIDGenerator.
Tenant creation: Via php artisan app:create-tenant. No automatic DB creation, migration, or seeding — all handled in the single shared database.

PRD Section 2: Route Architecture
All routes are tenant routes. The central web.php contains only require __DIR__ . '/auth.php', which itself is wrapped in tenancy middleware.

Route groups: All routes in routes/tenant.php and routes/auth.php use:


Route::middleware(['web', InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class])
TenancyServiceProvider registers:

routes/tenant.php — main tenant routes
routes/404.php — tenant 404 handler
routes/web-catch-all.php — slug-based redirect handler
Livewire update route with InitializeTenancyByDomain
Livewire file preview controller middleware includes InitializeTenancyByDomain
Middleware priority: PreventAccessFromCentralDomains → InitializeTenancyByDomain, both prepended as highest priority.

PRD Section 3: Data Scoping
All domain models use Stancl\Tenancy\Database\Concerns\BelongsToTenant which:

Adds tenant_id global scope to all queries
Auto-populates tenant_id from tenant()->id on creation
Central tables (no tenant_id): tenants, domains, migrations

All other tables are tenant-scoped (41+ models).

PRD Section 4: Frontend & Theming
Default tenant (KOMU): Uses resources/views/ with custom Tailwind frontend.

BTSKAD tenant: Uses hexadog/laravel-themes-manager. Theme damms005/btskad is activated in ConfigBootstrapper::setTheme(). The theme directory at themes/damms005/btskad/resources/views/ overrides default views.

Tenant identity is resolved for pre-tenancy operations (e.g., Filament color scheme) via Customer::getTenantFromDomainName() which reads request()->getHost() before tenancy middleware runs.

Branding (logo, title, favicon, colors) is stored in the settings table (scoped by tenant_id) and served via the SettingsService singleton (tims-settings container binding).

PRD Section 5: Authentication
Staff: Filament panel at /staff/dashboard. Auth guard: web. User record has non-null staff_id. Panel access: canAccessPanel() checks staff_id or a permission.

Students: Custom controller at /students/login. Same web guard. User record has non-null student_id. Login accepts email, matric number, application number, or JAMB reg number.

No central auth. All users are fully tenant-scoped via BelongsToTenant.

PRD Section 6: Configuration Bootstrap Sequence
On every tenant request, ConfigBootstrapper::bootstrap() runs:

Resolve tenant domain from tenant()->domains->first()->domain
Set app.url = https://<tenant-domain>
Set app.name = SettingsService::getValue('site.title')
Force HTTPS scheme in production
Set mail.from.address = admin@<tenant-domain>
Set payment config from settings (Paystack key, Remita keys, terminal IDs)
Set filesystem disk URLs to use tenant app URL
Activate BTSKAD theme if tenant matches BTSKAD domains
PRD Section 7: Adding a New Tenant (Operational Runbook)
Run: php artisan app:create-tenant
Follow interactive prompts (or pass --name, --domain, etc.)
Add the new domain to TrustHosts middleware
Add isXxx() method + XXX_DOMAINS constant to app/Models/Tenant.php
Add a case to Tenant::getMailMarkdownPath() for the new tenant's email template
Add a case to ConfigBootstrapper::setTheme() if the tenant needs a distinct theme
Add a case to Customer::getCurrentTenant() if the tenant needs distinct behavior
Extend AppPanelProvider::getColors() for tenant-specific Filament colors
Add a case to AppServiceProvider::configureMailTransport() for the tenant's Resend API key
Add the tenant to Tenant::DOMAIN_MAP for seeding purposes
Re-deploy application
Design flaw noted: Steps 3–11 require code changes and redeployment. The architecture is not self-service scalable. Each new tenant is wired in hardcode. This limits the platform to a small known set of tenants.

Summary of Architectural Gaps vs Best Practices
Area	Status	Gap
Database isolation	Single DB	No DB-level isolation; relies solely on application scoping
Cache isolation	Not bootstrapped	Generic cache keys may leak across tenants
Storage isolation	URL-level only	Physical files share same storage path
Tenant provisioning	Manual	Adding new tenants requires code changes
Migration structure	Flat (no tenant folder)	database/migrations/tenant/ path configured but folder absent
Theme management	Works but brittle	New tenants require hardcoded match cases
Auto-DB provisioning	Disabled	TenantCreated job pipeline fully commented out
Maintenance mode	Commented out	TenantMaintenanceMiddleware not applied in Kernel
Update Todos

Compile full architectural report and PRD
That is the complete 10-phase architectural audit. Key findings summarized:

Single-database mode — DatabaseTenancyBootstrapper is deliberately commented out; all tenants share one MySQL DB scoped by tenant_id
No teams concept — "team-based frontend" refers to per-tenant theming via hexadog/laravel-themes-manager
Two live tenants (KOMU/Toolz College and BTSKAD/Queen Mabel University) with hardcoded domain constants
ConfigBootstrapper is the runtime tenancy brain — sets URLs, mail, payment keys, theme, and disk URLs per request
Cache and storage isolation are incomplete — bootstrappers disabled; only SettingsService manually prefixes its cache keys
Tenant provisioning requires code changes — not self-service; each new tenant needs hardcoded constants and match cases across multiple files
Post-hack placeholder disks in config/filesystems.php indicate unresolved media loss from January 2026