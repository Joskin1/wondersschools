<?php

use App\Livewire\Gallery;
use App\Models\GalleryImage;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

describe('Gallery Page', function () {
    it('displays the gallery page successfully', function () {
        get('/gallery')
            ->assertOk()
            ->assertSeeLivewire(Gallery::class);
    });

    it('shows the gallery page headline', function () {
        get('/gallery')
            ->assertSee('Gallery')
            ->assertSee('Moments captured at WKFS');
    });

    it('displays all gallery images', function () {
        // Arrange
        $images = GalleryImage::factory()->count(6)->create();

        // Act & Assert
        $response = get('/gallery');
        
        foreach ($images as $image) {
            $response->assertSee($image->caption);
        }
    });

    it('shows category filter buttons', function () {
        // Arrange
        GalleryImage::factory()->create(['category' => 'sports']);
        GalleryImage::factory()->create(['category' => 'events']);

        // Act & Assert
        get('/gallery')
            ->assertSee('All')
            ->assertSee('Sports')
            ->assertSee('Events');
    });

    it('displays empty state when no images exist', function () {
        // Arrange
        GalleryImage::query()->delete();

        // Act & Assert
        get('/gallery')
            ->assertSee('No images found');
    });
});

describe('Gallery Filtering', function () {
    it('filters images by category', function () {
        // Arrange
        $sportsImage = GalleryImage::factory()->create([
            'category' => 'sports',
            'caption' => 'Sports Day 2024',
        ]);
        $eventImage = GalleryImage::factory()->create([
            'category' => 'events',
            'caption' => 'Cultural Festival',
        ]);

        // Act
        $component = Livewire::test(Gallery::class)
            ->call('setCategory', 'sports');

        // Assert
        $component->assertSee($sportsImage->caption)
            ->assertDontSee($eventImage->caption);
    });

    it('shows all images when all filter is selected', function () {
        // Arrange
        $sportsImage = GalleryImage::factory()->create(['category' => 'sports']);
        $eventImage = GalleryImage::factory()->create(['category' => 'events']);

        // Act
        $component = Livewire::test(Gallery::class)
            ->call('setCategory', 'all');

        // Assert
        $component->assertSee($sportsImage->caption)
            ->assertSee($eventImage->caption);
    });

    it('updates active filter state', function () {
        Livewire::test(Gallery::class)
            ->assertSet('category', 'all')
            ->call('setCategory', 'sports')
            ->assertSet('category', 'sports');
    });
});
