<?php

namespace App\Livewire;

use App\Models\TeacherProfile;
use App\Models\TeacherRegistrationToken;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class TeacherRegistrationForm extends Component
{
    public string $token;
    public User $user;

    // Profile fields
    public $dob;
    public $address;
    public $phone;
    public $gender;
    public $password;
    public $password_confirmation;

    /**
     * Mount the component.
     */
    public function mount(string $token, User $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    /**
     * Get the validation rules.
     */
    protected function rules()
    {
        return [
            'dob' => 'required|date|before:today|after:1950-01-01',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'gender' => 'required|in:male,female,other',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        ];
    }

    /**
     * Custom validation messages.
     */
    protected function messages()
    {
        return [
            'dob.before' => 'Date of birth must be in the past.',
            'dob.after' => 'Please enter a valid date of birth.',
            'phone.regex' => 'Please enter a valid phone number.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    /**
     * Submit the registration form.
     */
    public function submit()
    {
        $this->validate();

        // Validate token again (security check)
        $tokenRecord = TeacherRegistrationToken::validate($this->token);

        if (!$tokenRecord || $tokenRecord->user_id !== $this->user->id) {
            session()->flash('error', 'Invalid or expired token. Please request a new registration link.');
            return;
        }

        // Check if user has already completed registration
        if ($this->user->hasCompletedRegistration()) {
            session()->flash('error', 'You have already completed registration.');
            return redirect()->route('login');
        }

        try {
            DB::transaction(function () use ($tokenRecord) {
                // Create teacher profile
                TeacherProfile::create([
                    'user_id' => $this->user->id,
                    'dob' => $this->dob,
                    'address' => $this->address,
                    'phone' => $this->phone,
                    'gender' => $this->gender,
                ]);

                // Update user with password and activate account
                $this->user->update([
                    'password' => Hash::make($this->password),
                    'is_active' => true,
                    'registration_completed_at' => now(),
                ]);

                // Mark token as used
                $tokenRecord->markAsUsed();
            });

            // Auto-login the user
            Auth::login($this->user);

            // Redirect to teacher dashboard
            session()->flash('success', 'Registration completed successfully! Welcome to the teacher portal.');
            return redirect()->route('filament.teacher.pages.dashboard');

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred during registration. Please try again.');
            \Log::error('Teacher registration error: ' . $e->getMessage());
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.teacher-registration-form');
    }
}
