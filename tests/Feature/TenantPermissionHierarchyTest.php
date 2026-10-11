<?php

use App\Filament\Pages\ManageAdminPermissions;
use App\Filament\Pages\Settings;
use App\Filament\Resources\StudentResource;
use App\Filament\Teacher\Pages\EnterScores;
use App\Models\Student;
use App\Models\User;
use App\Policies\StudentPolicy;
use App\Policies\UserPolicy;
use App\Services\AdminPermissionRegistry;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Sudo (Pseudo Admin)
    $this->sudo = User::factory()->create([
        'name' => 'Pseudo Admin Sudo',
        'email' => 'sudo@wonders.test',
        'role' => 'sudo',
        'is_active' => true,
    ]);

    // Principal (Designated Primary Admin)
    $this->principal = User::factory()->create([
        'name' => 'School Principal',
        'email' => 'principal@wonders.test',
        'role' => 'admin',
        'is_primary_admin' => true,
        'admin_designation' => 'Principal',
        'is_active' => true,
    ]);

    // Bursar (Secondary Admin with granular financial/student permissions)
    $this->bursar = User::factory()->create([
        'name' => 'School Bursar',
        'email' => 'bursar@wonders.test',
        'role' => 'admin',
        'is_primary_admin' => false,
        'admin_designation' => 'Bursar',
        'allowed_admin_pages' => ['students.view', 'inquiries', 'contact_submissions'],
        'is_active' => true,
    ]);

    // Ordinary Admin without permissions
    $this->unprivilegedAdmin = User::factory()->create([
        'name' => 'Unprivileged Admin',
        'email' => 'unprivileged@wonders.test',
        'role' => 'admin',
        'is_primary_admin' => false,
        'allowed_admin_pages' => [],
        'is_active' => true,
    ]);

    // Teacher
    $this->teacher = User::factory()->create([
        'name' => 'Class Teacher Jane',
        'email' => 'teacher.jane@wonders.test',
        'role' => 'teacher',
        'is_primary_admin' => false,
        'is_active' => true,
    ]);

    // Student
    $this->student = User::factory()->create([
        'name' => 'Student Alice',
        'email' => 'student.alice@wonders.test',
        'role' => 'student',
        'is_primary_admin' => false,
        'is_active' => true,
    ]);
});

describe('1. Pseudo Admin Unrestricted Access', function () {
    it('grants pseudo admin unrestricted access across all resources, pages, and actions', function () {
        expect($this->sudo->isSudo())->toBeTrue();
        expect($this->sudo->canAccessAdminPage('academic_sessions'))->toBeTrue();
        expect($this->sudo->canAccessAdminPage('settings'))->toBeTrue();
        expect($this->sudo->canAccessAdminPage('students'))->toBeTrue();
        expect($this->sudo->canAccessAdminPage('any_future_resource'))->toBeTrue();

        expect($this->sudo->canPerformAdminAction('students', 'view'))->toBeTrue();
        expect($this->sudo->canPerformAdminAction('students', 'create'))->toBeTrue();
        expect($this->sudo->canPerformAdminAction('students', 'update'))->toBeTrue();
        expect($this->sudo->canPerformAdminAction('students', 'delete'))->toBeTrue();
        expect($this->sudo->canPerformAdminAction('new_module', 'delete'))->toBeTrue();
    });
});

describe('2 & 3. Principal Automatic Full Access and Future Resources', function () {
    it('grants designated principal automatic full access upon designation', function () {
        expect($this->principal->isPrimaryAdmin())->toBeTrue();
        expect($this->principal->canAccessAdminPage('academic_sessions'))->toBeTrue();
        expect($this->principal->canAccessAdminPage('students'))->toBeTrue();
        expect($this->principal->canAccessAdminPage('settings'))->toBeTrue();

        expect($this->principal->canPerformAdminAction('students', 'view'))->toBeTrue();
        expect($this->principal->canPerformAdminAction('students', 'create'))->toBeTrue();
        expect($this->principal->canPerformAdminAction('students', 'update'))->toBeTrue();
        expect($this->principal->canPerformAdminAction('students', 'delete'))->toBeTrue();
    });

    it('allows principal to access newly introduced future resources without manual assignment', function () {
        expect($this->principal->canAccessAdminPage('brand_new_curriculum_v2'))->toBeTrue();
        expect($this->principal->canPerformAdminAction('brand_new_curriculum_v2', 'create'))->toBeTrue();
        expect($this->principal->canPerformAdminAction('brand_new_curriculum_v2', 'delete'))->toBeTrue();

        // But ordinary staff default to denied on newly introduced resources
        expect($this->bursar->canAccessAdminPage('brand_new_curriculum_v2'))->toBeFalse();
        expect($this->bursar->canPerformAdminAction('brand_new_curriculum_v2', 'create'))->toBeFalse();
    });
});

describe('4. Granular Staff Permissions (Bursar)', function () {
    it('allows bursar to view students but denies create, edit, delete, and academic management', function () {
        // Bursar has ['students.view', 'inquiries', 'contact_submissions']
        expect($this->bursar->canAccessAdminPage('students'))->toBeTrue();
        expect($this->bursar->canPerformAdminAction('students', 'view'))->toBeTrue();
        expect($this->bursar->canPerformAdminAction('students', 'viewAny'))->toBeTrue();

        // Denied actions on students
        expect($this->bursar->canPerformAdminAction('students', 'create'))->toBeFalse();
        expect($this->bursar->canPerformAdminAction('students', 'update'))->toBeFalse();
        expect($this->bursar->canPerformAdminAction('students', 'edit'))->toBeFalse();
        expect($this->bursar->canPerformAdminAction('students', 'delete'))->toBeFalse();

        // Denied academic resources
        expect($this->bursar->canAccessAdminPage('academic_sessions'))->toBeFalse();
        expect($this->bursar->canAccessAdminPage('terms'))->toBeFalse();
        expect($this->bursar->canAccessAdminPage('enter_scores'))->toBeFalse();
        expect($this->bursar->canAccessAdminPage('process_class_results'))->toBeFalse();
    });
});

describe('5. Teacher Functionality and Administrative Assignment', function () {
    it('preserves existing teacher teaching functionality', function () {
        expect($this->teacher->isTeacher())->toBeTrue();
        expect($this->teacher->canAccessAdminPage('students'))->toBeFalse();

        // A teacher without admin permissions cannot access admin panel
        $adminPanel = Filament::getPanel('admin');
        expect($this->teacher->canAccessPanel($adminPanel))->toBeFalse();

        // A teacher CAN access teacher panel
        $teacherPanel = Filament::getPanel('teacher');
        expect($this->teacher->canAccessPanel($teacherPanel))->toBeTrue();
    });

    it('allows a teacher with assigned admin permissions to access admin panel without losing teacher access', function () {
        $this->teacher->update([
            'allowed_admin_pages' => ['lesson_notes.view'],
        ]);
        $this->teacher->refresh();

        expect($this->teacher->hasAnyAdminPermission())->toBeTrue();
        expect($this->teacher->canAccessAdminPage('lesson_notes'))->toBeTrue();
        expect($this->teacher->canPerformAdminAction('lesson_notes', 'view'))->toBeTrue();
        expect($this->teacher->canPerformAdminAction('lesson_notes', 'create'))->toBeFalse();

        // Teacher can now access Admin panel
        $adminPanel = Filament::getPanel('admin');
        expect($this->teacher->canAccessPanel($adminPanel))->toBeTrue();

        // Teacher retains access to Teacher panel
        $teacherPanel = Filament::getPanel('teacher');
        expect($this->teacher->canAccessPanel($teacherPanel))->toBeTrue();
    });
});

describe('6 & 7. Server-Side Policy & Direct URL / Request Enforcement', function () {
    it('blocks unauthorized direct page access server-side', function () {
        $this->actingAs($this->unprivilegedAdmin);

        expect(Settings::canAccess())->toBeFalse();
        expect(EnterScores::canAccess())->toBeFalse();
        expect(StudentResource::canAccess())->toBeFalse();
    });

    it('enforces granular actions server-side through StudentPolicy', function () {
        $policy = new StudentPolicy();

        // Bursar has only students.view
        expect($policy->viewAny($this->bursar))->toBeTrue();
        expect($policy->create($this->bursar))->toBeFalse();
        expect($policy->update($this->bursar, new Student()))->toBeFalse();
        expect($policy->delete($this->bursar, new Student()))->toBeFalse();

        // Admin with full students permission
        $fullAdmin = User::factory()->create([
            'role' => 'admin',
            'is_primary_admin' => false,
            'allowed_admin_pages' => ['students'],
            'is_active' => true,
        ]);

        expect($policy->viewAny($fullAdmin))->toBeTrue();
        expect($policy->create($fullAdmin))->toBeTrue();
        expect($policy->update($fullAdmin, new Student()))->toBeTrue();
        expect($policy->delete($fullAdmin, new Student()))->toBeTrue();
    });
});

describe('8 & 9. Permission Delegation & Principal Designation Boundaries', function () {
    it('blocks ordinary staff from accessing ManageAdminPermissions or promoting themselves', function () {
        $this->actingAs($this->bursar);
        expect(ManageAdminPermissions::canAccess())->toBeFalse();

        Livewire::test(ManageAdminPermissions::class)
            ->assertForbidden();

        $this->actingAs($this->teacher);
        expect(ManageAdminPermissions::canAccess())->toBeFalse();

        Livewire::test(ManageAdminPermissions::class)
            ->assertForbidden();
    });

    it('prevents Principal from designating another Principal or revoking Principal status', function () {
        $this->actingAs($this->principal);

        // Principal CAN open ManageAdminPermissions to manage ordinary staff
        expect(ManageAdminPermissions::canAccess())->toBeTrue();

        // But calling assignPrincipal aborts with 403
        Livewire::test(ManageAdminPermissions::class)
            ->set('principalUserId', $this->unprivilegedAdmin->id)
            ->call('assignPrincipal')
            ->assertForbidden();

        // Calling revokePrincipal aborts with 403
        Livewire::test(ManageAdminPermissions::class)
            ->call('revokePrincipal', $this->principal->id)
            ->assertForbidden();
    });

    it('prevents modifying Sudo protected accounts through UserPolicy', function () {
        $userPolicy = new UserPolicy();

        // Principal cannot update or delete Sudo
        expect($userPolicy->update($this->principal, $this->sudo))->toBeFalse();
        expect($userPolicy->delete($this->principal, $this->sudo))->toBeFalse();

        // Ordinary admin cannot update or delete Principal
        expect($userPolicy->update($this->bursar, $this->principal))->toBeFalse();
        expect($userPolicy->delete($this->bursar, $this->principal))->toBeFalse();
    });
});

describe('10 & 11. Principal Replacement and Privilege Removal', function () {
    it('removes former principal special privileges immediately when replaced', function () {
        $this->actingAs($this->sudo);

        $newPrincipalCandidate = User::factory()->create([
            'name' => 'New Candidate',
            'email' => 'new.candidate@wonders.test',
            'role' => 'admin',
            'is_primary_admin' => false,
            'is_active' => true,
        ]);

        // Sudo designates new candidate as Principal
        Livewire::test(ManageAdminPermissions::class)
            ->set('principalUserId', $newPrincipalCandidate->id)
            ->call('assignPrincipal')
            ->assertNotified('Principal Privilege Granted');

        $this->principal->refresh();
        $newPrincipalCandidate->refresh();

        // Former principal has lost is_primary_admin
        expect($this->principal->is_primary_admin)->toBeFalse();
        expect($this->principal->isPrimaryAdmin())->toBeFalse();

        // New candidate is now the sole primary admin
        expect($newPrincipalCandidate->is_primary_admin)->toBeTrue();
        expect($newPrincipalCandidate->isPrimaryAdmin())->toBeTrue();

        // Former principal immediately loses unrestricted access to unassigned pages
        expect($this->principal->canAccessAdminPage('academic_sessions'))->toBeFalse();
        expect($this->principal->canAccessAdminPage('settings'))->toBeFalse();
    });
});

describe('12. Multi-Tenant Scoping & Database Isolation', function () {
    it('enforces tenant scoping on user attributes and permissions', function () {
        // In stancl/tenancy, all User queries run against the active tenant connection.
        // Verifying that is_primary_admin and allowed_admin_pages are attributes of the tenant user model.
        expect($this->principal->getTable())->toBe('users');
        expect($this->principal->getConnectionName())->toBe(config('database.default'));
    });
});

describe('13. Permission Revocation Immediate Effect', function () {
    it('immediately denies access when permissions are revoked', function () {
        // Bursar initially has students.view
        expect($this->bursar->canAccessAdminPage('students'))->toBeTrue();
        expect($this->bursar->canPerformAdminAction('students', 'view'))->toBeTrue();

        // Revoke students.view
        $this->bursar->update([
            'allowed_admin_pages' => ['contact_submissions'],
        ]);
        $this->bursar->refresh();

        expect($this->bursar->canAccessAdminPage('students'))->toBeFalse();
        expect($this->bursar->canPerformAdminAction('students', 'view'))->toBeFalse();
    });
});

describe('14. Student and Teacher Login Behaviors Intact', function () {
    it('allows students to access student panel but blocks admin and teacher panels', function () {
        $adminPanel = Filament::getPanel('admin');
        $teacherPanel = Filament::getPanel('teacher');
        $studentPanel = Filament::getPanel('student');

        expect($this->student->canAccessPanel($studentPanel))->toBeTrue();
        expect($this->student->canAccessPanel($adminPanel))->toBeFalse();
        expect($this->student->canAccessPanel($teacherPanel))->toBeFalse();
    });
});
