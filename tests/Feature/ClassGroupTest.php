<?php

use App\Filament\Resources\ClassGroupResource;
use App\Models\ClassGroup;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'is_active' => true,
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);
});

test('admin can view class groups list page', function () {
    $group1 = ClassGroup::create(['name' => 'Nursery', 'order' => 1]);
    $group2 = ClassGroup::create(['name' => 'Primary', 'order' => 2]);

    Livewire::test(ClassGroupResource\Pages\ListClassGroups::class)
        ->assertSuccessful()
        ->assertSee('Nursery')
        ->assertSee('Primary');
});

test('admin can create a new class group and assign classes to it', function () {
    $class1 = Classroom::factory()->create(['name' => 'Primary 1']);
    $class2 = Classroom::factory()->create(['name' => 'Primary 2']);

    Livewire::test(ClassGroupResource\Pages\CreateClassGroup::class)
        ->fillForm([
            'name' => 'Primary Section',
            'order' => 2,
            'description' => 'Primary classes 1 to 6',
            'is_active' => true,
            'classrooms' => [$class1->id, $class2->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(ClassGroup::where('name', 'Primary Section')->exists())->toBeTrue();

    $group = ClassGroup::where('name', 'Primary Section')->first();
    expect($group->classrooms)->toHaveCount(2)
        ->and($class1->fresh()->class_group_id)->toBe($group->id)
        ->and($class2->fresh()->class_group_id)->toBe($group->id);
});

test('admin can edit a class group', function () {
    $group = ClassGroup::create([
        'name' => 'JSS',
        'order' => 3,
        'description' => 'Junior secondary',
        'is_active' => true,
    ]);

    $class = Classroom::factory()->create(['name' => 'JSS 1']);

    Livewire::test(ClassGroupResource\Pages\EditClassGroup::class, [
        'record' => $group->id,
    ])
        ->fillForm([
            'name' => 'Junior Secondary School',
            'classrooms' => [$class->id],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($group->fresh()->name)->toBe('Junior Secondary School')
        ->and($class->fresh()->class_group_id)->toBe($group->id);
});

test('admin can delete a class group', function () {
    $group = ClassGroup::create(['name' => 'To Delete', 'order' => 10]);

    Livewire::test(ClassGroupResource\Pages\EditClassGroup::class, [
        'record' => $group->id,
    ])
        ->callAction('delete');

    expect(ClassGroup::count())->toBe(0);
});

test('classroom model belongs to class group', function () {
    $group = ClassGroup::create(['name' => 'Secondary', 'order' => 1]);
    $class = Classroom::factory()->create([
        'name' => 'SS 1',
        'class_group_id' => $group->id,
    ]);

    expect($class->classGroup)->not->toBeNull()
        ->and($class->classGroup->id)->toBe($group->id)
        ->and($class->classGroup->name)->toBe('Secondary');
});

test('classroom scopeInGroup filters correctly', function () {
    $group1 = ClassGroup::create(['name' => 'Group A', 'order' => 1]);
    $group2 = ClassGroup::create(['name' => 'Group B', 'order' => 2]);

    $class1 = Classroom::factory()->create(['name' => 'A1', 'class_group_id' => $group1->id]);
    $class2 = Classroom::factory()->create(['name' => 'B1', 'class_group_id' => $group2->id]);

    $inGroupA = Classroom::inGroup($group1->id)->pluck('id')->toArray();

    expect($inGroupA)->toContain($class1->id)
        ->and($inGroupA)->not->toContain($class2->id);
});

test('non-admin cannot access class groups page', function () {
    $this->actingAs($this->teacher);

    Livewire::test(ClassGroupResource\Pages\ListClassGroups::class)
        ->assertForbidden();
});
