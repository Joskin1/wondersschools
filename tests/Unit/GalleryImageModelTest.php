<?php

use App\Models\GalleryImage;

describe('GalleryImage Model', function () {
    it('creates a gallery image with valid data', function () {
        // Arrange & Act
        $image = GalleryImage::factory()->create([
            'image' => 'gallery/sports-day.jpg',
            'caption' => 'Annual Sports Day 2024',
            'category' => 'sports',
        ]);

        // Assert
        expect($image->image)->toBe('gallery/sports-day.jpg')
            ->and($image->caption)->toBe('Annual Sports Day 2024')
            ->and($image->category)->toBe('sports')
            ->and($image->exists)->toBeTrue();
    });

    it('allows nullable caption', function () {
        $image = GalleryImage::factory()->create(['caption' => null]);

        expect($image->caption)->toBeNull();
    });

    it('filters images by category', function () {
        // Arrange
        GalleryImage::factory()->create(['category' => 'sports']);
        GalleryImage::factory()->create(['category' => 'sports']);
        GalleryImage::factory()->create(['category' => 'events']);

        // Act
        $sportsImages = GalleryImage::where('category', 'sports')->get();

        // Assert
        expect($sportsImages)->toHaveCount(2);
    });

    it('returns all images when category is all', function () {
        // Arrange
        GalleryImage::factory()->count(5)->create();

        // Act
        $allImages = GalleryImage::all();

        // Assert
        expect($allImages)->toHaveCount(5);
    });
});
