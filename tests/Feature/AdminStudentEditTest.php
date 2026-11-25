<?php

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\StudentResource\Pages\EditStudent;
use App\Filament\Resources\StudentResource\Pages\ListStudents;
use App\Filament\Resources\StudentResource\Pages\ViewStudent;
use App\Models\ClassArm;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->admin = User::factory()->create([
        'role' => 'admin',
        'is_primary_admin' => true,
        'is_active' => true,
    ]);

    $this->session = Session::factory()->create(['is_active' => true]);
    $this->juniorClassroom = Classroom::factory()->create([
        'name' => 'JSS 1',
        'class_order' => 1,
        'has_departments' => false,
    ]);
    $this->seniorClassroom = Classroom::factory()->create([
        'name' => 'SS 1',
        'class_order' => 4,
        'has_departments' => true,
    ]);
    $this->department = Department::create([
        'name' => 'Science',
        'code' => 'SCI',
        'is_active' => true,
    ]);
});

describe('Admin Student Editing', function () {
    it('prefills and updates student identity, profile, guardian, and enrollment details', function () {
        $studentUser = User::factory()->create([
            'surname' => 'Adeyemi',
            'first_name' => 'Tunde',
            'other_name' => 'Bola',
            'email' => '2026001@student.local',
            'password' => Hash::make('OriginalPass123'),
            'role' => 'student',
            'is_active' => false,
        ]);

        $student = Student::factory()->create([
            'user_id' => $studentUser->id,
            'admission_number' => '2026001',
            'status' => 'pending',
            'is_portal_active' => false,
            'gender' => 'male',
            'address' => 'Old Address',
        ]);

        StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $this->juniorClassroom->id,
            'session_id' => $this->session->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListStudents::class)
            ->assertTableActionVisible('edit', $student);

        Livewire::test(ViewStudent::class, ['record' => $student->getRouteKey()])
            ->assertActionVisible('edit');

        Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
            ->assertFormSet([
                'surname' => 'Adeyemi',
                'first_name' => 'Tunde',
                'other_name' => 'Bola',
                'admission_number' => '2026001',
                'classroom_id' => $this->juniorClassroom->id,
                'session_id' => $this->session->id,
                'gender' => 'male',
                'address' => 'Old Address',
                'is_portal_active' => false,
            ])
            ->fillForm([
                'surname' => 'Okafor',
                'first_name' => 'Chinedu',
                'other_name' => 'Emeka',
                'admission_number' => '2026099',
                'classroom_id' => $this->seniorClassroom->id,
                'department_id' => $this->department->id,
                'session_id' => $this->session->id,
                'date_of_birth' => '2011-05-14',
                'gender' => 'male',
                'previous_school' => 'Bright Future Academy',
                'address' => '42 Victory Road',
                'parent_name' => 'Mr Okafor',
                'parent_phone' => '08098765432',
                'parent_email' => 'okafor@example.com',
                'is_portal_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $student->refresh();
        $studentUser->refresh();
        $enrollment = $student->currentEnrollment();

        expect($student->admission_number)->toBe('2026099')
            ->and($student->date_of_birth->format('Y-m-d'))->toBe('2011-05-14')
            ->and($student->previous_school)->toBe('Bright Future Academy')
            ->and($student->address)->toBe('42 Victory Road')
            ->and($student->parent_name)->toBe('Mr Okafor')
            ->and($student->parent_phone)->toBe('08098765432')
            ->and($student->parent_email)->toBe('okafor@example.com')
            ->and($student->is_portal_active)->toBeTrue()
            ->and($student->status)->toBe('active')
            ->and($studentUser->surname)->toBe('Okafor')
            ->and($studentUser->first_name)->toBe('Chinedu')
            ->and($studentUser->other_name)->toBe('Emeka')
            ->and($studentUser->email)->toBe('2026099@student.local')
            ->and($studentUser->is_active)->toBeTrue()
            ->and(Hash::check('OriginalPass123', $studentUser->password))->toBeTrue()
            ->and($enrollment)->not->toBeNull()
            ->and($enrollment->classroom_id)->toBe($this->seniorClassroom->id)
            ->and($enrollment->department_id)->toBe($this->department->id);
    });

    it('updates password and clears department when moving student to a junior classroom without departments', function () {
        Setting::updateOrCreate(['key' => 'enable_class_arms'], ['value' => '1']);

        $seniorArm = ClassArm::create([
            'classroom_id' => $this->seniorClassroom->id,
            'name' => 'Gold',
            'is_active' => true,
        ]);
        $juniorArm = ClassArm::create([
            'classroom_id' => $this->juniorClassroom->id,
            'name' => 'Brown',
            'is_active' => true,
        ]);

        $studentUser = User::factory()->create([
            'surname' => 'Bello',
            'first_name' => 'Amina',
            'password' => Hash::make('OldPass123'),
            'role' => 'student',
            'is_active' => true,
        ]);

        $student = Student::factory()->active()->create([
            'user_id' => $studentUser->id,
            'admission_number' => 'LRSC2026010',
            'is_portal_active' => true,
        ]);

        StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $this->seniorClassroom->id,
            'class_arm_id' => $seniorArm->id,
            'department_id' => $this->department->id,
            'session_id' => $this->session->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
            ->fillForm([
                'surname' => 'Bello',
                'first_name' => 'Amina',
                'admission_number' => 'LRSC2026010',
                'initial_password' => 'NewSecret99',
                'classroom_id' => $this->juniorClassroom->id,
                'class_arm_id' => $juniorArm->id,
                'session_id' => $this->session->id,
                'is_portal_active' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $student->refresh();
        $studentUser->refresh();
        $enrollment = $student->currentEnrollment();

        expect(Hash::check('NewSecret99', $studentUser->password))->toBeTrue()
            ->and($student->is_portal_active)->toBeFalse()
            ->and($studentUser->is_active)->toBeFalse()
            ->and($enrollment->classroom_id)->toBe($this->juniorClassroom->id)
            ->and($enrollment->class_arm_id)->toBe($juniorArm->id)
            ->and($enrollment->department_id)->toBeNull();
    });

    it('rejects duplicate admission numbers belonging to another student', function () {
        Student::factory()->create(['admission_number' => 'EXISTING001']);

        $studentUser = User::factory()->create([
            'surname' => 'Dada',
            'first_name' => 'Seyi',
            'role' => 'student',
        ]);
        $student = Student::factory()->create([
            'user_id' => $studentUser->id,
            'admission_number' => 'OWN002',
        ]);
        StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $this->juniorClassroom->id,
            'session_id' => $this->session->id,
        ]);

        $this->actingAs($this->admin);

        Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])
            ->fillForm([
                'surname' => 'Dada',
                'first_name' => 'Seyi',
                'admission_number' => 'EXISTING001',
                'classroom_id' => $this->juniorClassroom->id,
                'session_id' => $this->session->id,
            ])
            ->call('save')
            ->assertHasFormErrors(['admission_number' => 'unique']);

        expect($student->fresh()->admission_number)->toBe('OWN002');
    });

    it('enforces update permissions and blocks editing soft-deleted students', function () {
        $viewOnlyAdmin = User::factory()->create([
            'role' => 'admin',
            'is_primary_admin' => false,
            'allowed_admin_pages' => ['students.view'],
            'is_active' => true,
        ]);

        $updateAdmin = User::factory()->create([
            'role' => 'admin',
            'is_primary_admin' => false,
            'allowed_admin_pages' => ['students.view', 'students.update'],
            'is_active' => true,
        ]);

        $student = Student::factory()->create(['admission_number' => 'PERM001']);
        StudentEnrollment::create([
            'student_id' => $student->id,
            'classroom_id' => $this->juniorClassroom->id,
            'session_id' => $this->session->id,
        ]);

        $this->actingAs($viewOnlyAdmin);
        Livewire::test(ListStudents::class)->assertTableActionHidden('edit', $student);
        Livewire::test(EditStudent::class, ['record' => $student->getRouteKey()])->assertForbidden();

        $this->actingAs($updateAdmin);
        expect(StudentResource::canEdit($student))->toBeTrue();

        $student->delete();
        expect(StudentResource::canEdit($student->fresh()))->toBeFalse();
    });

    it('configures autocomplete to prevent browser credential autofill', function () {
        $response = $this->actingAs($this->admin)
            ->get(StudentResource::getUrl('create'));

        $response->assertSuccessful();
        $response->assertSee('autocomplete="new-password"', false);
        $response->assertSee('data-lpignore="true"', false);
    });
});
