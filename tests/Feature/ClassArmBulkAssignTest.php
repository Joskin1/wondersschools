<?php

use App\Filament\Resources\ClassArmResource;
use App\Filament\Resources\ClassArmResource\Pages\EditClassArm;
use App\Filament\Resources\ClassArmResource\Pages\ListClassArms;
use App\Filament\Resources\ClassArmResource\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Resources\ClassroomResource\Pages\EditClassroom;
use App\Filament\Resources\ClassroomResource\RelationManagers\ArmsRelationManager;
use App\Models\ClassArm;
use App\Models\Classroom;
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
    ]);
    $this->jss2 = Classroom::factory()->create([
        'name' => 'JSS 2',
        'class_order' => 2,
    ]);

    $this->goldArm = ClassArm::create([
        'classroom_id' => $this->jss1->id,
        'name' => 'Gold',
        'is_active' => true,
    ]);
    $this->silverArm = ClassArm::create([
        'classroom_id' => $this->jss1->id,
        'name' => 'Silver',
        'is_active' => true,
    ]);

    $this->student1 = Student::factory()->create(['admission_number' => 'ARM001']);
    $this->student2 = Student::factory()->create(['admission_number' => 'ARM002']);
    $this->student3 = Student::factory()->create(['admission_number' => 'ARM003']);
    $this->alreadyInGold = Student::factory()->create(['admission_number' => 'ARM004']);
    $this->otherClassStudent = Student::factory()->create(['admission_number' => 'ARM005']);

    $this->enrollment1 = StudentEnrollment::create([
        'student_id' => $this->student1->id,
        'classroom_id' => $this->jss1->id,
        'session_id' => $this->session->id,
    ]);
    $this->enrollment2 = StudentEnrollment::create([
        'student_id' => $this->student2->id,
        'classroom_id' => $this->jss1->id,
        'session_id' => $this->session->id,
    ]);
    $this->enrollment3 = StudentEnrollment::create([
        'student_id' => $this->student3->id,
        'classroom_id' => $this->jss1->id,
        'class_arm_id' => $this->silverArm->id,
        'session_id' => $this->session->id,
    ]);
    $this->goldEnrollment = StudentEnrollment::create([
        'student_id' => $this->alreadyInGold->id,
        'classroom_id' => $this->jss1->id,
        'class_arm_id' => $this->goldArm->id,
        'session_id' => $this->session->id,
    ]);
    $this->otherClassEnrollment = StudentEnrollment::create([
        'student_id' => $this->otherClassStudent->id,
        'classroom_id' => $this->jss2->id,
        'session_id' => $this->session->id,
    ]);
});

describe('Class Arm Bulk Student Assignment', function () {
    it('filters eligible students by classroom, target arm, and unassigned toggle', function () {
        $this->actingAs($this->admin);

        $component = Livewire::test(ListClassArms::class)
            ->mountAction('assign_students_to_arm')
            ->set('mountedActions.0.data.session_id', $this->session->id)
            ->set('mountedActions.0.data.classroom_id', $this->jss1->id)
            ->set('mountedActions.0.data.class_arm_id', $this->goldArm->id);

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
            ->and($options)->not->toHaveKey($this->alreadyInGold->id)
            ->and($options)->not->toHaveKey($this->otherClassStudent->id);

        $component->set('mountedActions.0.data.only_unassigned', true);

        $unassignedOptions = $getOptions();
        expect($unassignedOptions)->toHaveKey($this->student1->id)
            ->and($unassignedOptions)->toHaveKey($this->student2->id)
            ->and($unassignedOptions)->not->toHaveKey($this->student3->id);
    });

    it('allows selecting a class, target arm, and multiple students at once from ListClassArms header action', function () {
        $this->actingAs($this->admin);

        Livewire::test(ListClassArms::class)
            ->callAction('assign_students_to_arm', data: [
                'session_id' => $this->session->id,
                'classroom_id' => $this->jss1->id,
                'class_arm_id' => $this->goldArm->id,
                'only_unassigned' => false,
                'student_ids' => [$this->student1->id, $this->student2->id, $this->student3->id],
            ])
            ->assertHasNoActionErrors();

        expect($this->enrollment1->fresh()->class_arm_id)->toBe($this->goldArm->id)
            ->and($this->enrollment2->fresh()->class_arm_id)->toBe($this->goldArm->id)
            ->and($this->enrollment3->fresh()->class_arm_id)->toBe($this->goldArm->id)
            ->and($this->goldEnrollment->fresh()->class_arm_id)->toBe($this->goldArm->id)
            ->and($this->otherClassEnrollment->fresh()->class_arm_id)->toBeNull();
    });

    it('allows assigning multiple students via the row action on ClassArmResource table', function () {
        $this->actingAs($this->admin);

        Livewire::test(ListClassArms::class)
            ->callTableAction('assign_students', $this->silverArm, data: [
                'session_id' => $this->session->id,
                'student_ids' => [$this->student1->id, $this->student2->id],
            ])
            ->assertHasNoTableActionErrors();

        expect($this->enrollment1->fresh()->class_arm_id)->toBe($this->silverArm->id)
            ->and($this->enrollment2->fresh()->class_arm_id)->toBe($this->silverArm->id)
            ->and($this->goldEnrollment->fresh()->class_arm_id)->toBe($this->goldArm->id);
    });

    it('allows assigning and removing students from EditClassArm and its EnrollmentsRelationManager', function () {
        $this->actingAs($this->admin);

        Livewire::test(EditClassArm::class, ['record' => $this->goldArm->getRouteKey()])
            ->callAction('assign_students', data: [
                'session_id' => $this->session->id,
                'student_ids' => [$this->student1->id, $this->student2->id],
            ])
            ->assertHasNoActionErrors();

        expect($this->enrollment1->fresh()->class_arm_id)->toBe($this->goldArm->id)
            ->and($this->enrollment2->fresh()->class_arm_id)->toBe($this->goldArm->id);

        Livewire::test(EnrollmentsRelationManager::class, [
            'ownerRecord' => $this->goldArm,
            'pageClass' => EditClassArm::class,
        ])
            ->assertCanSeeTableRecords([$this->goldEnrollment, $this->enrollment1, $this->enrollment2])
            ->callTableAction('remove_from_arm', $this->enrollment1)
            ->callTableBulkAction('remove_selected_from_arm', [$this->enrollment2]);

        expect($this->enrollment1->fresh()->class_arm_id)->toBeNull()
            ->and($this->enrollment2->fresh()->class_arm_id)->toBeNull()
            ->and($this->goldEnrollment->fresh()->class_arm_id)->toBe($this->goldArm->id);
    });

    it('supports assigning students from Classroom ArmsRelationManager', function () {
        $this->actingAs($this->admin);

        Livewire::test(ArmsRelationManager::class, [
            'ownerRecord' => $this->jss1,
            'pageClass' => EditClassroom::class,
        ])
            ->callTableAction('assign_students', $this->goldArm, data: [
                'session_id' => $this->session->id,
                'student_ids' => [$this->student1->id],
            ])
            ->assertHasNoTableActionErrors();

        expect($this->enrollment1->fresh()->class_arm_id)->toBe($this->goldArm->id);
    });

    it('enforces class_arms.update permission for bulk assignment and removal', function () {
        $viewOnlyAdmin = User::factory()->create([
            'role' => 'admin',
            'is_primary_admin' => false,
            'allowed_admin_pages' => ['class_arms.view'],
            'is_active' => true,
        ]);

        $this->actingAs($viewOnlyAdmin);

        Livewire::test(ListClassArms::class)
            ->assertActionHidden('assign_students_to_arm')
            ->assertTableActionHidden('assign_students', $this->goldArm);

        expect(fn () => ClassArmResource::handleAssignStudents([
            'session_id' => $this->session->id,
            'classroom_id' => $this->jss1->id,
            'class_arm_id' => $this->goldArm->id,
            'student_ids' => [$this->student1->id],
        ]))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    });
});
