<?php

use App\Models\User;
use App\Models\Session;
use App\Models\Term;
use App\Models\TermMigrationLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Academic Session and Term Management', function () {

    it('creates a new academic session with three terms', function () {
        // Act
        $session = Session::createWithTerms(2024);

        // Assert
        expect($session)->toBeInstanceOf(Session::class);
        expect($session->name)->toBe('2024-2025');
        expect($session->start_year)->toBe(2024);
        expect($session->end_year)->toBe(2025);
        expect($session->terms)->toHaveCount(3);
        expect($session->terms->pluck('name')->toArray())->toBe(['First Term', 'Second Term', 'Third Term']);
    });


    it('marks only one session as active at a time', function () {
        // Arrange
        $session1 = Session::factory()->create(['is_active' => true]);
        $session2 = Session::factory()->create(['is_active' => false]);

        // Act
        $session2->activate();

        // Assert
        expect(Session::where('is_active', true)->count())->toBe(1);
        expect($session1->fresh()->is_active)->toBeFalse();
        expect($session2->fresh()->is_active)->toBeTrue();
    });


    it('prevents migrating from first term directly to third term', function () {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        
        $session = Session::createWithTerms(2024);
        $session->activate();
        $first = $session->terms()->where('order', 1)->first();
        $first->update(['is_active' => true]);

        // Act - migrate() always goes to next sequential term
        $result = $first->migrate();

        // Assert - it should migrate to second term (order 2), not third
        expect($result->order)->toBe(2);
        expect($result->name)->toBe('Second Term');
    });


    it('allows migrating from first term to second term', function () {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        
        $session = Session::createWithTerms(2024);
        $session->activate();
        $first = $session->terms()->where('order', 1)->first();
        $second = $session->terms()->where('order', 2)->first();
        $first->update(['is_active' => true]);

        // Act
        $result = $first->migrate();

        // Assert
        expect($first->fresh()->is_active)->toBeFalse();
        expect($second->fresh()->is_active)->toBeTrue();
        expect($result->id)->toBe($second->id);
    });


    it('creates a new academic session when migrating from third term', function () {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        
        $session = Session::createWithTerms(2024);
        $session->activate();
        $third = $session->terms()->where('order', 3)->first();
        $third->update(['is_active' => true]);

        // Act
        $newFirstTerm = $third->migrate();

        // Assert
        expect(Session::count())->toBe(2);
        $newSession = Session::where('name', '2025-2026')->first();
        expect($newSession)->not->toBeNull();
        expect($newSession->is_active)->toBeTrue();
        expect($newFirstTerm->session_id)->toBe($newSession->id);
        expect($newFirstTerm->order)->toBe(1);
        expect($newFirstTerm->is_active)->toBeTrue();
        
        // Old session should be inactive
        expect($session->fresh()->is_active)->toBeFalse();
    });


    it('prevents non-admin users from accessing session management via policies', function () {
        // Arrange
        $teacher = User::factory()->create(['role' => 'teacher']);
        $session = Session::factory()->create();

        // Assert - teachers can view but not create/update
        expect($teacher->can('viewAny', Session::class))->toBeFalse();
        expect($teacher->can('create', Session::class))->toBeFalse();
        expect($teacher->can('update', $session))->toBeFalse();
    });

    
    it('allows admin users to manage sessions', function () {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $session = Session::factory()->create();

        // Assert
        expect($admin->can('viewAny', Session::class))->toBeTrue();
        expect($admin->can('create', Session::class))->toBeTrue();
        expect($admin->can('update', $session))->toBeTrue();
    });


    it('allows sudo users to manage sessions', function () {
        // Arrange
        $sudo = User::factory()->create(['role' => 'sudo']);
        $session = Session::factory()->create();

        // Assert
        expect($sudo->can('viewAny', Session::class))->toBeTrue();
        expect($sudo->can('create', Session::class))->toBeTrue();
        expect($sudo->can('update', $session))->toBeTrue();
    });


    it('logs every term migration for audit purposes', function () {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        
        $session = Session::createWithTerms(2024);
        $session->activate();
        $first = $session->terms()->where('order', 1)->first();
        $second = $session->terms()->where('order', 2)->first();
        $first->update(['is_active' => true]);

        // Act
        $first->migrate();

        // Assert
        $log = TermMigrationLog::first();
        expect($log)->not->toBeNull();
        expect($log->user_id)->toBe($admin->id);
        expect($log->from_term_id)->toBe($first->id);
        expect($log->to_term_id)->toBe($second->id);
        expect($log->from_session_id)->toBe($session->id);
        expect($log->to_session_id)->toBe($session->id);
    });


    it('prevents deleting sessions to preserve historical data', function () {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $session = Session::factory()->create();

        // Assert
        expect($admin->can('delete', $session))->toBeFalse();
    });


    it('prevents deleting terms to preserve historical data', function () {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $session = Session::createWithTerms(2024);
        $term = $session->terms()->first();

        // Assert
        expect($admin->can('delete', $term))->toBeFalse();
    });


    it('returns the next term correctly', function () {
        // Arrange
        $session = Session::createWithTerms(2024);
        $first = $session->terms()->where('order', 1)->first();
        $second = $session->terms()->where('order', 2)->first();
        $third = $session->terms()->where('order', 3)->first();

        // Assert
        expect($first->next_term->id)->toBe($second->id);
        expect($second->next_term->id)->toBe($third->id);
        expect($third->next_term)->toBeNull(); // Third term has no next in same session
    });


    it('validates term migration transitions', function () {
        // Arrange
        $session = Session::createWithTerms(2024);
        $first = $session->terms()->where('order', 1)->first();
        $second = $session->terms()->where('order', 2)->first();
        $third = $session->terms()->where('order', 3)->first();

        // Assert
        expect($first->canMigrateTo($second))->toBeTrue();
        expect($first->canMigrateTo($third))->toBeFalse();
        expect($second->canMigrateTo($third))->toBeTrue();
        expect($second->canMigrateTo($first))->toBeFalse();
    });

});
