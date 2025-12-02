<?php

use App\Models\Post;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses()->group('feature', 'models');

test('creates a post with valid data', function () {
    $post = Post::factory()->create([
        'title' => 'Test Post',
        'body' => 'This is a test post body.',
    ]);

    expect($post->title)->toBe('Test Post')
        ->and($post->body)->toBe('This is a test post body.')
        ->and($post->exists)->toBeTrue();
});

test('has a published_at timestamp', function () {
    $post = Post::factory()->create();

    expect($post->published_at)->not->toBeNull()
        ->and($post->published_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('can have an image', function () {
    $post = Post::factory()->create([
        'image' => 'posts/test-image.jpg',
    ]);

    expect($post->image)->toBe('posts/test-image.jpg');
});

test('orders posts by published_at descending by default', function () {
    $oldPost = Post::factory()->create([
        'published_at' => now()->subDays(5),
    ]);
    $newPost = Post::factory()->create([
        'published_at' => now(),
    ]);

    $posts = Post::latest('published_at')->get();

    expect($posts->first()->id)->toBe($newPost->id)
        ->and($posts->last()->id)->toBe($oldPost->id);
});
