<?php

namespace Database\Seeders;

use App\Models\GalleryImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class GalleryImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $placeholders = [
            'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?q=80&w=2070&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=2064&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1577896851231-70ef18881754?q=80&w=2070&auto=format&fit=crop',
        ];

        foreach ($placeholders as $index => $url) {
            GalleryImage::create([
                'image' => $url,
                'category' => 'Campus Life',
                'caption' => 'Hero Slider Image ' . ($index + 1),
                'is_hero_slider' => true,
            ]);
        }
    }
}
