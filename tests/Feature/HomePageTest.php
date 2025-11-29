<?php

use App\Livewire\Home;
use App\Models\Post;
use App\Models\Staff;
use function Pest\Laravel\get;

describe('Home Page', function () {
    it('displays the home page successfully', function () {
        get('/')
            ->assertOk()
            ->assertSeeLivewire(Home::class);
    });

    it('shows the hero section with school name', function () {
        get('/')
            ->assertSee('Wonders Kiddies Foundation Schools')
            ->assertSee('A Foundation That Builds Futures');
    });

    it('displays the trust strip with verification badges', function () {
        get('/')
            ->assertSee('Verified Curriculum')
            ->assertSee('Experienced Educators')
            ->assertSee('Secure Campus')
            ->assertSee('Proven Results');
    });

    it('shows the Why WKFS section', function () {
        get('/')
            ->assertSee('Why "Wonders"?')
            ->assertSee('Academic Excellence')
            ->assertSee('Holistic Development');
    });

    it('displays statistics section', function () {
        get('/')
            ->assertSee('Years of Excellence')
            ->assertSee('Happy Students')
            ->assertSee('Expert Staff');
    });

    it('shows latest news posts when available', function () {
        // Arrange
        $posts = Post::factory()->count(3)->create();

        // Act & Assert
        $response = get('/');
        
        foreach ($posts as $post) {
            $response->assertSee($post->title);
        }
    });

    it('displays leadership team members', function () {
        // Arrange
        $staff = Staff::factory()->count(3)->create();

        // Act & Assert
        $response = get('/');
        
        foreach ($staff as $member) {
            $response->assertSee($member->name);
            $response->assertSee($member->role);
        }
    });

    it('shows call to action buttons', function () {
        get('/')
            ->assertSee('Explore Our Curriculum')
            ->assertSee('Book a Tour')
            ->assertSee('Enrol Now');
    });

    it('displays WhatsApp chat link', function () {
        get('/')
            ->assertSee('Chat on WhatsApp');
    });
});
