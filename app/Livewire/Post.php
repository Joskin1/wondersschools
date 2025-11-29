<?php

namespace App\Livewire;

use App\Models\Post as PostModel;
use Livewire\Component;

class Post extends Component
{
    public PostModel $post;

    public function mount(PostModel $post)
    {
        $this->post = $post;
    }

    public function render()
    {
        return view('livewire.post');
    }
}
