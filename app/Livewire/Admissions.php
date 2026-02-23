<?php

namespace App\Livewire;

use App\Models\Inquiry;
use Filament\Notifications\Notification;
use Livewire\Component;

class Admissions extends Component
{
    public $name;
    public $email;
    public $phone;
    public $child_age;
    public $message;

    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email',
        'phone' => 'required',
        'child_age' => 'required',
        'message' => 'nullable',
    ];

    public function submit()
    {
        $this->validate();

        Inquiry::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'child_age' => $this->child_age,
            'message' => $this->message,
        ]);

        $this->reset();

        Notification::make()
            ->title('Inquiry submitted successfully')
            ->success()
            ->send();
            
        session()->flash('message', 'Thank you for your inquiry. We will contact you shortly.');
    }

    public function render()
    {
        return view('livewire.admissions');
    }
}
