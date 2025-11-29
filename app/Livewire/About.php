<?php

namespace App\Livewire;

use App\Models\Staff;
use Livewire\Component;

class About extends Component
{
    public function render()
    {
        return view('livewire.about', [
            'staff' => Staff::orderBy('order')->get(),
        ]);
    }
}
