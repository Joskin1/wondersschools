<?php

namespace App\Livewire;

use App\Models\Student;
use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class RegisterStudent extends Component
{
    public ?Student $student = null;
    public ?string $slug = null;
    public ?string $token = null;
    public bool $isValid = false;
    public bool $isExpired = false;
    public bool $isCompleted = false;
    public string $error = '';

    // Form fields
    public string $date_of_birth = '';
    public string $gender = '';
    public string $address = '';
    public string $previous_school = '';
    public string $parent_name = '';
    public string $parent_phone = '';
    public string $parent_email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $slug)
    {
        $this->slug = $slug;
        $this->token = request()->query('token', '');

        // Validate registration link
        if (empty($this->token)) {
            $this->error = 'Invalid registration link. Token is missing.';
            return;
        }

        $this->student = Student::validateRegistration($this->slug, $this->token);

        if (!$this->student) {
            // Check if student exists but link is invalid
            $student = Student::where('registration_slug', $this->slug)->first();

            if (!$student) {
                $this->error = 'Invalid registration link. Student not found.';
            } elseif ($student->isActive()) {
                $this->isCompleted = true;
            } elseif ($student->hasExpiredRegistration()) {
                $this->isExpired = true;
            } else {
                $this->error = 'Invalid or expired registration link.';
            }

            return;
        }

        $this->isValid = true;
    }

    public function submit()
    {
        if (!$this->isValid || !$this->student) {
            return;
        }

        // Validate form data
        $validated = Validator::make([
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'address' => $this->address,
            'previous_school' => $this->previous_school,
            'parent_name' => $this->parent_name,
            'parent_phone' => $this->parent_phone,
            'parent_email' => $this->parent_email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ], [
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'address' => 'required|string|max:255',
            'previous_school' => 'nullable|string|max:255',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:20',
            'parent_email' => 'nullable|email|max:255',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ])->validate();

        // Create User account for the student (inactive until admin activates)
        $email = $validated['parent_email']
            ?? strtolower(str_replace(' ', '.', $this->student->full_name)) . '@student.local';

        $user = User::create([
            'name'      => $this->student->full_name,
            'email'     => $email,
            'password'  => Hash::make($validated['password']),
            'role'      => 'student',
            'is_active' => false,
        ]);

        // Link user to student
        $this->student->update(['user_id' => $user->id]);

        // Complete registration (sets registration_completed_at, clears token)
        unset($validated['password'], $validated['password_confirmation']);
        $this->student->completeRegistration($validated);

        // Mark as completed
        $this->isCompleted = true;
        $this->isValid = false;
    }

    public function render()
    {
        return view('livewire.register-student')
            ->layout('components.layouts.app');
    }
}
