<?php

namespace App\Livewire;

use App\Models\Post;
use Livewire\Component;
use Livewire\WithPagination;

class News extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.news', [
            'posts' => Post::where('published_at', '<=', now())
                ->orderBy('published_at', 'desc')
                ->paginate(9),
        ]);
    }
}
