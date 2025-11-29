<?php

use App\Models\Post;
use App\Models\User;

describe('Post Model', function () {
    it('creates a post with valid data', function () {
        // Arrange & Act
        $post = Post::factory()->create([
            'title' => 'Test Post',
            'body' => 'This is a test post body.',
        ]);

        // Assert
        expect($post->title)->toBe('Test Post')
            ->and($post->body)->toBe('This is a test post body.')
            ->and($post->exists)->toBeTrue();
    });

    it('has a published_at timestamp', function () {
        $post = Post::factory()->create();

        expect($post->published_at)->not->toBeNull()
            ->and($post->published_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('can have an image', function () {
        $post = Post::factory()->create([
            'image' => 'posts/test-image.jpg',
        ]);

        expect($post->image)->toBe('posts/test-image.jpg');
    });

    it('orders posts by published_at descending by default', function () {
        // Arrange
        $oldPost = Post::factory()->create([
            'published_at' => now()->subDays(5),
        ]);
        $newPost = Post::factory()->create([
            'published_at' => now(),
        ]);

        // Act
        $posts = Post::latest('published_at')->get();

        // Assert
        expect($posts->first()->id)->toBe($newPost->id)
            ->and($posts->last()->id)->toBe($oldPost->id);
    });
});
