$tenant = \App\Models\Tenant::find('soso');
if (!$tenant) { echo "Tenant 'soso' not found!\n"; exit; }

$admin = \App\Models\User::where('email', 'bikepyl@mailinator.com')->first();
if ($admin) {
    $admin->password = \Illuminate\Support\Facades\Hash::make('SVvvy13C8kdQ');
    $admin->save();
    try {
        \App\Models\TenantAdminAssignment::firstOrCreate([
            'global_user_id' => $admin->id,
            'tenant_id' => $tenant->id
        ]);
    } catch (\Throwable $e) {}
}

tenancy()->initialize($tenant);

$teacher = \App\Models\User::where('role', 'teacher')->first();

$studentUser = \App\Models\User::where('role', 'student')->first();
if (!$studentUser) {
    $studentUser = \App\Models\User::create([
        'name' => 'Test Student',
        'email' => 'student@mailinator.com',
        'role' => 'student',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'is_active' => true,
    ]);
} else {
    $studentUser->password = \Illuminate\Support\Facades\Hash::make('password');
    $studentUser->save();
}

$studentInfo = \App\Models\Student::first();
if ($studentInfo) {
    $studentInfo->user_id = $studentUser->id;
    $studentInfo->save();
}

echo "Teacher credentials: {$teacher->email} / password\n";
echo "Student credentials: {$studentUser->email} / password\n";
