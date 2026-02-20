<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$school = App\Models\Central\School::where('name', 'Valley Institute')->first();
$school->configureTenantConnection();

$email = 'teacher1@wondersschools.com';
$user = App\Models\User::where('email', $email)->first();
if (!$user) {
    echo "User not found in tenant db!\n";
    exit;
}

echo "Role: {$user->role}, Active: {$user->is_active}\n";

$valid = Illuminate\Support\Facades\Hash::check('password', $user->password);
echo "Password is valid: " . ($valid ? 'true' : 'false') . "\n";

$attempt = Illuminate\Support\Facades\Auth::attempt(['email' => $email, 'password' => 'password']);
echo "Auth attempt successful: " . ($attempt ? 'true' : 'false') . "\n";

// Ensure the user role is purely 'teacher'
if ($user->role !== 'teacher') {
    $user->role = 'teacher';
    $user->save();
    echo "Fixed user role to exactly 'teacher'\n";
}
