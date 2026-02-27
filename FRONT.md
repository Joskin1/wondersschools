IMPLEMENTATION BRIEF


Objective


Make all hardcoded frontend content dynamic and tenant-aware.



Critical Constraints (Non-Negotiable)
DO NOT modify or delete any existing:

Filament resources

Admin panel components

Routes

Middleware

Tenancy configuration

Existing database tables

Existing models

DO NOT refactor structure.

DO NOT rename anything.

Only:

Add new migration(s)

Add new model

Add a service/library class

Add optional Filament resource (new only)

Substitute hardcoded frontend values with dynamic retrieval

PHASE 1 — Create Tenant-Scoped Frontend Content Storage


1. Create Tenant Migration


Location: database/migrations/tenant



Create table:

Schema::create('frontend_contents', function (Blueprint $table) {
    $table->id();
    $table->string('key')->index();
    $table->string('group')->nullable(); 
    $table->longText('value')->nullable();
    $table->timestamps();
});
Notes:

This table must live in the tenant database.

Do not attach foreign keys.

Do not modify tenancy bootstrapping.

2. Create Model
app/Models/FrontendContent.php
class FrontendContent extends Model
{
    protected $fillable = ['key', 'group', 'value'];
}
Do not modify any existing model.

PHASE 2 — Create Frontend Library Service


Create:

app/Services/FrontendLibrary.php
namespace App\Services;

use App\Models\FrontendContent;

class FrontendLibrary
{
    public static function get(string $key, $default = null)
    {
        return FrontendContent::where('key', $key)->value('value') ?? $default;
    }
}
Rules:

No caching layer for now.

No global helpers.

Keep it simple.

PHASE 3 — Substitute Hardcoded Frontend Values


Scan only:

resources/views

Livewire Blade components



For each hardcoded value:



Before:

<h1>Welcome to Our School</h1>
After:

<h1>{{ \App\Services\FrontendLibrary::get('hero_title', 'Welcome to Our School') }}</h1>
Rules:

DO NOT change layout structure.

DO NOT change CSS classes.

DO NOT move components.

DO NOT modify Livewire logic.

Only replace literal strings and static image paths.



If image:



Before:

<img src="/images/hero.jpg">
After:

<img src="{{ \App\Services\FrontendLibrary::get('hero_image', '/images/hero.jpg') }}">
PHASE 4 — Create Optional Filament Resource (NEW ONLY)


Create new resource:

FrontendContentResource
Fields:

key (text input, required, unique)

group (text input, optional)

value (textarea)



Rules:

Do not modify existing resources.

Do not alter navigation grouping unless adding a new menu item.

Do not remove any panel configuration.

PHASE 5 — Tenant Seeder


Create:

TenantFrontendContentSeeder
Seed defaults:

FrontendContent::insert([
    ['key' => 'hero_title', 'group' => 'hero', 'value' => 'Welcome to Our School'],
    ['key' => 'hero_subtitle', 'group' => 'hero', 'value' => 'Excellence in Education'],
    ['key' => 'hero_image', 'group' => 'hero', 'value' => '/images/hero.jpg'],
]);
Seeder must run only in tenant context.



Do not seed in central database.

PHASE 6 — Testing


Add tests to confirm:

Tenant A content does not appear in Tenant B.

Updating content updates frontend rendering.

Default fallback works if no value exists.



Example:

$this->assertDatabaseHas('frontend_contents', [
    'key' => 'hero_title'
]);
Explicitly Forbidden Actions
No restructuring of Livewire components.

No touching existing admin panel logic.

No editing tenancy config.

No replacing current frontend resources (gallery, inquiry, etc.).

No schema modification of existing tables.

No automated refactors.

Expected Outcome
All frontend text/images become dynamic.

Admin can edit content per tenant.

Existing functionality remains untouched.

No deletion or refactor occurs.

Multi-tenancy remains intact.

