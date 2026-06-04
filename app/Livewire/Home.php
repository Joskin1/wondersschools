<?php

namespace App\Livewire;

use App\Models\Post;
use App\Models\Staff;
use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        try {
            $latestNews = Post::where('published_at', '<=', now())
                ->orderBy('published_at', 'desc')
                ->take(3)
                ->get();
        } catch (\Throwable) {
            $latestNews = collect();
        }

        try {
            $staffMembers = Staff::orderBy('order')->take(3)->get();
        } catch (\Throwable) {
            $staffMembers = collect();
        }

        try {
            $heroImages = \App\Models\GalleryImage::where('is_hero_slider', true)->pluck('image')->toArray();
        } catch (\Throwable) {
            $heroImages = [];
        }

        return view('livewire.home', [
            'latestNews'   => $latestNews,
            'staffMembers' => $staffMembers,
            'heroImages'   => $heroImages,
        ]);
    }
}
