<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

App\Models\Central\School::where('name', 'Valley Institute')->first()->configureTenantConnection();

$admin = App\Models\User::where('email', 'admin@wonders.test')->first();
if ($admin) {
    $admin->role = 'admin';
    $admin->is_active = true;
    $admin->save();
    echo "Fixed admin user\n";
}

$teacher = App\Models\User::where('email', 'teacher1@wondersschools.com')->first();
if ($teacher) {
    $teacher->is_active = true;
    $teacher->registration_completed_at = now();
    $teacher->password = bcrypt('password');
    $teacher->save();
    echo "Activated teacher1\n";
}
