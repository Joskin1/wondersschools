<?php

namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        return view('livewire.home', [
            'latestNews' => Post::where('published_at', '<=', now())
                ->orderBy('published_at', 'desc')
                ->take(3)
                ->get(),
        ]);
    }
}
