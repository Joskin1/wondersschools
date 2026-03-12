<?php

namespace App\Livewire;

use App\Models\ContactSubmission;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Notifications\Notification;
use Livewire\Component;

class Contact extends Component
{
    use WithRateLimiting;

    public $name;
    public $email;
    public $message;

    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email',
        'message' => 'required|min:10',
    ];

    public function submit()
    {
        try {
            $this->rateLimit(5); // 5 submissions per minute
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Too many submissions. Please try again in ' . $exception->secondsUntilAvailable . ' seconds.')
                ->danger()
                ->send();

            return;
        }

        $this->validate();

        ContactSubmission::create([
            'name' => $this->name,
            'email' => $this->email,
            'message' => $this->message,
        ]);

        $this->reset();

        Notification::make()
            ->title('Message sent successfully')
            ->success()
            ->send();
            
        session()->flash('message', 'Thank you for contacting us. We will get back to you soon.');
    }

    public function render()
    {
        return view('livewire.contact');
    }
}
