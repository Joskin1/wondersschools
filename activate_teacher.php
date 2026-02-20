<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$school = App\Models\Central\School::where('name', 'Valley Institute')->first();
$school->configureTenantConnection();

$user = App\Models\User::where('email', 'teacher@schoolb.com')->first();
if ($user) {
    $user->role = 'teacher';
    $user->is_active = true;
    $user->registration_completed_at = now();
    $user->password = Illuminate\Support\Facades\Hash::make('password');
    $user->save();
    echo "Activated John Teacher\n";

    // Create a staff profile for him if needed?
    // Let's check if Staff is required
} else {
    echo "User not found\n";
}
