<?php

namespace App\Livewire;

use App\Models\GalleryImage;
use App\Services\FrontendContentService;
use Livewire\Component;

class Gallery extends Component
{
    public $category = 'all';

    public function setCategory($category)
    {
        $this->category = $category;
    }

    public function render()
    {
        $query = GalleryImage::query();

        if ($this->category !== 'all') {
            $query->where('category', $this->category);
        }

        return view('livewire.gallery', [
            'images' => $query->latest()->get(),
            'categories' => GalleryImage::distinct()->pluck('category'),
            'site' => app(FrontendContentService::class),
        ]);
    }
}
