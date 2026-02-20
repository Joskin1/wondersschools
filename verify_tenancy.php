<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Central\School;
use App\Models\Central\Domain;
use App\Services\TenantProvisioner;
use Illuminate\Support\Facades\DB;

echo "=== Creating Second School: Valley Institute ===\n\n";

// Create school record
$school = School::create([
    'name' => 'Valley Institute',
    'database_name' => TenantProvisioner::generateDatabaseName('Valley Institute'),
    'database_username' => TenantProvisioner::generateDatabaseUsername('Valley Institute'),
    'database_password' => TenantProvisioner::generateDatabasePassword(),
    'status' => 'active',
]);

echo "School created: {$school->name} (ID: {$school->id}, DB: {$school->database_name})\n";

// Provision the tenant database
$provisioner = app(TenantProvisioner::class);
$provisioner->provision($school);
echo "Database provisioned.\n";

// Add domain
Domain::create([
    'school_id' => $school->id,
    'domain' => 'schoolb.test',
    'is_primary' => true,
]);
echo "Domain 'schoolb.test' added.\n";

// Verify: add a subject to School A's tenant DB to test isolation
$schoolA = School::where('name', 'Test Academy')->first();
$schoolA->configureTenantConnection();
DB::connection('tenant')->table('subjects')->insert([
    'name' => 'Mathematics',
    'code' => 'MATH101',
    'description' => 'Test Academy Mathematics',
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "\nAdded 'Mathematics' subject to Test Academy tenant DB.\n";

// Check: School A subjects
$subjectsA = DB::connection('tenant')->table('subjects')->get();
echo "Test Academy subjects: " . $subjectsA->count() . "\n";
foreach ($subjectsA as $s) {
    echo "  - {$s->name} ({$s->code})\n";
}

// Switch to School B
DB::purge('tenant');
$school->configureTenantConnection();
DB::reconnect('tenant');
$subjectsB = DB::connection('tenant')->table('subjects')->get();
echo "\nValley Institute subjects: " . $subjectsB->count() . "\n";
foreach ($subjectsB as $s) {
    echo "  - {$s->name} ({$s->code})\n";
}

echo "\n=== Data Isolation Test: " . ($subjectsA->count() !== $subjectsB->count() ? "PASSED ✓" : "WARNING") . " ===\n";
echo "School A has {$subjectsA->count()} subjects, School B has {$subjectsB->count()} subjects.\n";

// Summary
echo "\n=== All Schools ===\n";
foreach (School::with('domains')->get() as $s) {
    echo "{$s->name} (DB: {$s->database_name}, Status: {$s->status})\n";
    echo "  Domains: " . $s->domains->pluck('domain')->implode(', ') . "\n";
}
