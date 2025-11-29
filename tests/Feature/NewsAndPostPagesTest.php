<?php

use App\Livewire\News;
use App\Livewire\Post as PostView;
use App\Models\Post;
use function Pest\Laravel\get;

describe('News Page', function () {
    it('displays the news page successfully', function () {
        get('/news')
            ->assertOk()
            ->assertSeeLivewire(News::class);
    });

    it('shows the news page headline', function () {
        get('/news')
            ->assertSee('News & Events')
            ->assertSee('Stay updated with the latest happenings at WKFS');
    });

    it('displays published news posts', function () {
        // Arrange
        $posts = Post::factory()->count(5)->create();

        // Act & Assert
        $response = get('/news');
        
        foreach ($posts as $post) {
            $response->assertSee($post->title);
        }
    });

    it('shows post publication dates', function () {
        // Arrange
        $post = Post::factory()->create([
            'published_at' => now(),
        ]);

        // Act & Assert
        get('/news')
            ->assertSee($post->published_at->format('M d, Y'));
    });

    it('displays empty state when no posts exist', function () {
        // Arrange
        Post::query()->delete();

        // Act & Assert
        get('/news')
            ->assertSee('No news available at the moment');
    });

    it('paginates news posts', function () {
        // Arrange
        Post::factory()->count(20)->create();

        // Act & Assert
        get('/news')
            ->assertSee('Next');
    });
});

describe('Single Post Page', function () {
    it('displays a single post successfully', function () {
        // Arrange
        $post = Post::factory()->create();

        // Act & Assert
        get("/news/{$post->id}")
            ->assertOk()
            ->assertSeeLivewire(PostView::class);
    });

    it('shows post title and content', function () {
        // Arrange
        $post = Post::factory()->create([
            'title' => 'Important School Announcement',
            'body' => 'This is the full content of the announcement.',
        ]);

        // Act & Assert
        get("/news/{$post->id}")
            ->assertSee($post->title)
            ->assertSee($post->body);
    });

    it('shows post publication date', function () {
        // Arrange
        $post = Post::factory()->create();

        // Act & Assert
        get("/news/{$post->id}")
            ->assertSee($post->published_at->format('F d, Y'));
    });

    it('shows back to news link', function () {
        // Arrange
        $post = Post::factory()->create();

        // Act & Assert
        get("/news/{$post->id}")
            ->assertSee('Back to News');
    });

    it('returns 404 for non-existent post', function () {
        get('/news/99999')
            ->assertNotFound();
    });
});
