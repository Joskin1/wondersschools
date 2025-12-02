<?php

use App\Models\GalleryImage;

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
uses()->group('feature', 'models');

test('creates a gallery image with valid data', function () {
    $image = GalleryImage::factory()->create([
        'image' => 'gallery/sports-day.jpg',
        'caption' => 'Annual Sports Day 2024',
        'category' => 'sports',
    ]);

    expect($image->image)->toBe('gallery/sports-day.jpg')
        ->and($image->caption)->toBe('Annual Sports Day 2024')
        ->and($image->category)->toBe('sports')
        ->and($image->exists)->toBeTrue();
});

test('allows nullable caption', function () {
    $image = GalleryImage::factory()->create(['caption' => null]);

    expect($image->caption)->toBeNull();
});

test('filters images by category', function () {
    GalleryImage::factory()->create(['category' => 'sports']);
    GalleryImage::factory()->create(['category' => 'sports']);
    GalleryImage::factory()->create(['category' => 'events']);

    $sportsImages = GalleryImage::where('category', 'sports')->get();

    expect($sportsImages)->toHaveCount(2);
});

test('returns all images when category is all', function () {
    GalleryImage::factory()->count(5)->create();

    $allImages = GalleryImage::all();

    expect($allImages)->toHaveCount(5);
});
