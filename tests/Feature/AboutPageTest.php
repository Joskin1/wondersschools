<?php

use App\Livewire\About;
use App\Models\Staff;
use function Pest\Laravel\get;

describe('About Us Page', function () {
    it('displays the about page successfully', function () {
        get('/about-us')
            ->assertOk()
            ->assertSeeLivewire(About::class);
    });

    it('shows the page headline', function () {
        get('/about-us')
            ->assertSee('We Build Foundations That Last')
            ->assertSee('Wonders Kiddies Foundation Schools');
    });

    it('displays the school mission statement', function () {
        get('/about-us')
            ->assertSee('Our Mission')
            ->assertSee('secure, well-planned education');
    });

    it('displays the school vision statement', function () {
        get('/about-us')
            ->assertSee('Our Vision')
            ->assertSee('most trusted educational brand');
    });

    it('shows all five core values', function () {
        get('/about-us')
            ->assertSee('Our Core Values')
            ->assertSee('Integrity of Instruction')
            ->assertSee('Student-Centric Nurturing')
            ->assertSee('Strategic Curriculum Delivery')
            ->assertSee('Transparent Parent Partnership')
            ->assertSee('Long-term Value Creation');
    });

    it('displays leadership team members', function () {
        // Arrange
        $staff = Staff::factory()->count(5)->create();

        // Act & Assert
        $response = get('/about-us');
        
        foreach ($staff as $member) {
            $response->assertSee($member->name);
            $response->assertSee($member->role);
        }
    });

    it('shows leadership section heading', function () {
        get('/about-us')
            ->assertSee('Meet Our Leadership')
            ->assertSee('The dedicated team guiding our school');
    });

    it('displays empty state when no staff members exist', function () {
        // Arrange
        Staff::query()->delete();

        // Act & Assert
        get('/about-us')
            ->assertSee('Leadership team information coming soon');
    });
});
