<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Central\Domain;
use App\Models\Central\School;

// Add domain for Test Academy
$school = School::first();
if (!$school) {
    echo "ERROR: No school found\n";
    exit(1);
}

echo "School: {$school->name} (ID: {$school->id})\n";

// Check if domain already exists
$existing = Domain::where('domain', 'schoola.test')->first();
if ($existing) {
    echo "Domain 'schoola.test' already exists for school ID {$existing->school_id}\n";
} else {
    Domain::create([
        'school_id' => $school->id,
        'domain' => 'schoola.test',
        'is_primary' => true,
    ]);
    echo "Domain 'schoola.test' added as primary for '{$school->name}'\n";
}

echo "\nAll domains:\n";
foreach (Domain::with('school')->get() as $domain) {
    echo "  {$domain->domain} -> {$domain->school->name} (primary: " . ($domain->is_primary ? 'yes' : 'no') . ")\n";
}

echo "\nDone.\n";
