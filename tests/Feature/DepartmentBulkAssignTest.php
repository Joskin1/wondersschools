<?php

use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\DepartmentResource\Pages\EditDepartment;
use App\Filament\Resources\DepartmentResource\Pages\ListDepartments;
use App\Filament\Resources\DepartmentResource\RelationManagers\EnrollmentsRelationManager;
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
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Setting::updateOrCreate(['key' => 'enable_departments'], ['value' => '1']);
    Setting::updateOrCreate(['key' => 'enable_class_arms'], ['value' => '1']);

    $this->admin = User::factory()->create([
        'role' => 'admin',
        'is_primary_admin' => true,
        'is_active' => true,
    ]);

    $this->session = Session::factory()->create(['is_active' => true]);

    $this->jss1 = Classroom::factory()->create([
        'name' => 'JSS 1',
        'class_order' => 1,
        'has_departments' => false,
    ]);
    $this->ss2 = Classroom::factory()->create([
        'name' => 'SS 2',
        'class_order' => 5,
        'has_departments' => true,
    ]);

    $this->ss2Gold = ClassArm::create([
        'classroom_id' => $this->ss2->id,
        'name' => 'Gold',
        'is_active' => true,
    ]);
    $this->ss2Brown = ClassArm::create([
        'classroom_id' => $this->ss2->id,
        'name' => 'Brown',
        'is_active' => true,
    ]);

    $this->science = Department::create([
        'name' => 'Science',
        'code' => 'SCI',
        'is_active' => true,
    ]);
    $this->arts = Department::create([
        'name' => 'Arts',
        'code' => 'ART',
        'is_active' => true,
    ]);

    $this->student1 = Student::factory()->create(['admission_number' => 'DEP001']);
    $this->student2 = Student::factory()->create(['admission_number' => 'DEP002']);
    $this->student3 = Student::factory()->create(['admission_number' => 'DEP003']);
    $this->alreadyInScience = Student::factory()->create(['admission_number' => 'DEP004']);
    $this->juniorStudent = Student::factory()->create(['admission_number' => 'DEP005']);

    $this->enrollment1 = StudentEnrollment::create([
        'student_id' => $this->student1->id,
        'classroom_id' => $this->ss2->id,
        'class_arm_id' => $this->ss2Gold->id,
        'session_id' => $this->session->id,
    ]);
    $this->enrollment2 = StudentEnrollment::create([
        'student_id' => $this->student2->id,
        'classroom_id' => $this->ss2->id,
        'class_arm_id' => $this->ss2Brown->id,
        'session_id' => $this->session->id,
    ]);
    $this->enrollment3 = StudentEnrollment::create([
        'student_id' => $this->student3->id,
        'classroom_id' => $this->ss2->id,
        'class_arm_id' => $this->ss2Gold->id,
        'department_id' => $this->arts->id,
        'session_id' => $this->session->id,
    ]);
    $this->scienceEnrollment = StudentEnrollment::create([
        'student_id' => $this->alreadyInScience->id,
        'classroom_id' => $this->ss2->id,
        'class_arm_id' => $this->ss2Gold->id,
        'department_id' => $this->science->id,
        'session_id' => $this->session->id,
    ]);
    $this->juniorEnrollment = StudentEnrollment::create([
        'student_id' => $this->juniorStudent->id,
        'classroom_id' => $this->jss1->id,
        'session_id' => $this->session->id,
    ]);
});

describe('Department Bulk Student Assignment', function () {
    it('filters eligible senior students by classroom, arm, department, and unassigned toggle', function () {
        $this->actingAs($this->admin);

        $component = Livewire::test(ListDepartments::class)
            ->mountAction('assign_students_to_department')
            ->set('mountedActions.0.data.session_id', $this->session->id)
            ->set('mountedActions.0.data.classroom_id', $this->ss2->id)
            ->set('mountedActions.0.data.department_id', $this->science->id);

        $getOptions = function () use ($component): array {
            $schema = $component->instance()->mountedActionSchema0;
            foreach ($schema->getFlatComponents() as $field) {
                if ($field instanceof \Filament\Forms\Components\CheckboxList && $field->getName() === 'student_ids') {
                    return $field->getOptions();
                }
            }

            return [];
        };

        $options = $getOptions();
        expect($options)->toHaveKey($this->student1->id)
            ->and($options)->toHaveKey($this->student2->id)
            ->and($options)->toHaveKey($this->student3->id)
            ->and($options)->not->toHaveKey($this->alreadyInScience->id)
            ->and($options)->not->toHaveKey($this->juniorStudent->id);

        $component->set('mountedActions.0.data.class_arm_id', $this->ss2Gold->id);
        $goldOptions = $getOptions();
        expect($goldOptions)->toHaveKey($this->student1->id)
            ->and($goldOptions)->toHaveKey($this->student3->id)
            ->and($goldOptions)->not->toHaveKey($this->student2->id);

        $component->set('mountedActions.0.data.only_unassigned', true);
        $unassignedGoldOptions = $getOptions();
        expect($unassignedGoldOptions)->toHaveKey($this->student1->id)
            ->and($unassignedGoldOptions)->not->toHaveKey($this->student3->id);
    });

    it('allows selecting a senior class, target department, and multiple students at once from ListDepartments header action', function () {
        $this->actingAs($this->admin);

        Livewire::test(ListDepartments::class)
            ->callAction('assign_students_to_department', data: [
                'session_id' => $this->session->id,
                'classroom_id' => $this->ss2->id,
                'department_id' => $this->science->id,
                'only_unassigned' => false,
                'student_ids' => [$this->student1->id, $this->student2->id, $this->student3->id],
            ])
            ->assertHasNoActionErrors();

        expect($this->enrollment1->fresh()->department_id)->toBe($this->science->id)
            ->and($this->enrollment2->fresh()->department_id)->toBe($this->science->id)
            ->and($this->enrollment3->fresh()->department_id)->toBe($this->science->id)
            ->and($this->scienceEnrollment->fresh()->department_id)->toBe($this->science->id)
            ->and($this->juniorEnrollment->fresh()->department_id)->toBeNull();
    });

    it('allows assigning multiple students via the row action on DepartmentResource table', function () {
        $this->actingAs($this->admin);

        Livewire::test(ListDepartments::class)
            ->callTableAction('assign_students', $this->arts, data: [
                'session_id' => $this->session->id,
                'classroom_id' => $this->ss2->id,
                'student_ids' => [$this->student1->id, $this->student2->id],
            ])
            ->assertHasNoTableActionErrors();

        expect($this->enrollment1->fresh()->department_id)->toBe($this->arts->id)
            ->and($this->enrollment2->fresh()->department_id)->toBe($this->arts->id)
            ->and($this->scienceEnrollment->fresh()->department_id)->toBe($this->science->id);
    });

    it('allows assigning and removing students from EditDepartment and its EnrollmentsRelationManager', function () {
        $this->actingAs($this->admin);

        Livewire::test(EditDepartment::class, ['record' => $this->science->getRouteKey()])
            ->callAction('assign_students', data: [
                'session_id' => $this->session->id,
                'classroom_id' => $this->ss2->id,
                'student_ids' => [$this->student1->id, $this->student2->id],
            ])
            ->assertHasNoActionErrors();

        expect($this->enrollment1->fresh()->department_id)->toBe($this->science->id)
            ->and($this->enrollment2->fresh()->department_id)->toBe($this->science->id);

        Livewire::test(EnrollmentsRelationManager::class, [
            'ownerRecord' => $this->science,
            'pageClass' => EditDepartment::class,
        ])
            ->assertCanSeeTableRecords([$this->scienceEnrollment, $this->enrollment1, $this->enrollment2])
            ->callTableAction('remove_from_department', $this->enrollment1)
            ->callTableBulkAction('remove_selected_from_department', [$this->enrollment2]);

        expect($this->enrollment1->fresh()->department_id)->toBeNull()
            ->and($this->enrollment2->fresh()->department_id)->toBeNull()
            ->and($this->scienceEnrollment->fresh()->department_id)->toBe($this->science->id);
    });

    it('enforces departments.update permission for bulk assignment and removal', function () {
        $viewOnlyAdmin = User::factory()->create([
            'role' => 'admin',
            'is_primary_admin' => false,
            'allowed_admin_pages' => ['departments.view'],
            'is_active' => true,
        ]);

        $this->actingAs($viewOnlyAdmin);

        Livewire::test(ListDepartments::class)
            ->assertActionHidden('assign_students_to_department')
            ->assertTableActionHidden('assign_students', $this->science);

        expect(fn () => DepartmentResource::handleAssignStudents([
            'session_id' => $this->session->id,
            'classroom_id' => $this->ss2->id,
            'department_id' => $this->science->id,
            'student_ids' => [$this->student1->id],
        ]))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    });
});
