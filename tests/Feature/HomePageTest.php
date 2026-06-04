<?php

use App\Livewire\Home;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('Home Page', function () {
    it('displays the home page successfully', function () {
        get('/')
            ->assertOk()
            ->assertSeeLivewire(Home::class);
    });

    it('shows the hero section with school name', function () {
        Setting::create(['key' => 'school_name', 'value' => 'Wonders Kiddies Foundation Schools']);

        get('/')
            ->assertSee('Wonders Kiddies Foundation Schools')
            ->assertSee('A Foundation That')
            ->assertSee('Builds Futures.');
    });

    it('displays the pillars of the school', function () {
        get('/')
            ->assertSee('Science Laboratory')
            ->assertSee('Practical Work')
            ->assertSee('Information Technology')
            ->assertSee('Creative Arts');
    });

    it('shows the What We Do section', function () {
        get('/')
            ->assertSee('What We Do')
            ->assertSee('Effective Teaching')
            ->assertSee('Arts & Creativity');
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

    it('shows call to action buttons', function () {
        get('/')
            ->assertSee('Explore Our Campus')
            ->assertSee('Book a Tour')
            ->assertSee('Enrol Now');
    });

    it('displays WhatsApp chat link', function () {
        get('/')
            ->assertSee('Chat on WhatsApp');
    });
});
