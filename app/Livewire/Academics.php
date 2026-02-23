<?php

namespace App\Livewire;

use App\Services\FrontendContentService;
use Livewire\Component;

class Academics extends Component
{
    public function render()
    {
        return view('livewire.academics', [
            'site' => app(FrontendContentService::class),
        ]);
    }
}
