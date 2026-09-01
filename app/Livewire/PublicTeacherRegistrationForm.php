<?php

namespace App\Livewire;

use App\Models\Teacher;
use App\Models\User;
use App\Services\BrandingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithFileUploads;

class PublicTeacherRegistrationForm extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $gender = 'male';
    public ?string $dob = null;
    public string $address = '';
    public $profile_picture = null;
    public string $password = '';
    public string $password_confirmation = '';

    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'gender' => 'required|in:male,female,other',
            'dob' => 'required|date|before:today|after:1950-01-01',
            'address' => 'required|string|max:500',
            'profile_picture' => 'nullable|image|max:2048', // 2MB Max
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Please enter your full name.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'An account with this email address already exists.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Please enter a valid phone number.',
            'dob.required' => 'Date of birth is required.',
            'dob.before' => 'Date of birth must be in the past.',
            'address.required' => 'Home address is required.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $profilePicturePath = null;
        if ($this->profile_picture) {
            $profilePicturePath = $this->profile_picture->store('profile-pictures', 'public');
        }

        try {
            DB::transaction(function () use ($profilePicturePath) {
                $user = User::create([
                    'name' => trim($this->name),
                    'email' => strtolower(trim($this->email)),
                    'password' => Hash::make($this->password),
                    'role' => 'teacher',
                    'is_active' => false, // Pending admin activation
                    'registration_completed_at' => now(),
                    'email_verified_at' => now(),
                ]);

                Teacher::create([
                    'user_id' => $user->id,
                    'profile_picture' => $profilePicturePath,
                    'dob' => $this->dob,
                    'address' => $this->address,
                    'phone' => $this->phone,
                    'gender' => $this->gender,
                ]);
            });

            $this->submitted = true;
        } catch (\Exception $e) {
            \Log::error('Public teacher registration failed: ' . $e->getMessage());
            session()->flash('error', 'An error occurred during registration. Please try again.');
        }
    }

    public function render()
    {
        $branding = app(BrandingService::class);

        return view('livewire.public-teacher-registration-form', [
            'appName' => $branding->getAppName(),
            'primaryColor' => $branding->getPrimaryColor(),
        ])->layout('components.layouts.app', [
            'title' => 'Teacher Self-Registration',
        ]);
    }
}
