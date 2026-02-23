<?php

namespace App\Livewire;

use App\Models\Staff;
use App\Services\FrontendContentService;
use Livewire\Component;

class About extends Component
{
    public function render()
    {
        return view('livewire.about', [
            'site'  => app(FrontendContentService::class),
            'staff' => Staff::orderBy('order')->get(),
        ]);
    }
}
