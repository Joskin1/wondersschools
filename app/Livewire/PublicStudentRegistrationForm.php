<?php

namespace App\Livewire;

use App\Models\Classroom;
use App\Models\Session;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Services\BrandingService;
use App\Services\FrontendLibrary;
use App\Services\StudentAccountService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithFileUploads;

class PublicStudentRegistrationForm extends Component
{
    use WithFileUploads, WithRateLimiting;

    // Student fields
    public ?string $admission_number = null;
    public bool $allowManualAdmissionNumber = false;
    public string $full_name = '';
    public ?string $date_of_birth = null;
    public string $gender = 'male';
    public ?int $classroom_id = null;
    public $profile_picture = null;
    public string $previous_school = '';
    public string $address = '';

    // Parent/Guardian fields
    public string $parent_name = '';
    public string $parent_phone = '';
    public string $parent_email = '';

    // Account fields
    public string $password = '';
    public string $password_confirmation = '';

    public bool $submitted = false;
    public ?Student $createdStudent = null;
    public ?string $generatedAdmissionNumber = null;
    public ?string $generatedEmail = null;

    public function mount(): void
    {
        $this->allowManualAdmissionNumber = FrontendLibrary::getBooleanSetting('allow_manual_admission_number', false);
    }

    protected function rules(): array
    {
        $rules = [
            'full_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'classroom_id' => 'required|exists:classrooms,id',
            'profile_picture' => 'nullable|image|max:2048', // 2MB Max
            'previous_school' => 'nullable|string|max:255',
            'address' => 'required|string|max:500',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'parent_email' => 'nullable|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];

        if ($this->allowManualAdmissionNumber) {
            $rules['admission_number'] = 'required|string|max:50|unique:students,admission_number';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'admission_number.required' => 'Admission / Registration number is required.',
            'admission_number.unique' => 'This admission number has already been registered to another student.',
            'full_name.required' => 'Student full name is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'gender.required' => 'Gender is required.',
            'classroom_id.required' => 'Please select a classroom/grade.',
            'address.required' => 'Home address is required.',
            'parent_name.required' => 'Parent/Guardian name is required.',
            'parent_phone.required' => 'Parent/Guardian phone number is required.',
            'parent_phone.regex' => 'Please enter a valid phone number.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    public function submit(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Too many registration attempts')
                ->body('Please wait ' . $exception->secondsUntilAvailable . ' seconds before trying again.')
                ->danger()
                ->send();

            return;
        }

        $this->allowManualAdmissionNumber = FrontendLibrary::getBooleanSetting('allow_manual_admission_number', false);

        $this->validate();

        $profilePicturePath = null;
        if ($this->profile_picture) {
            $profilePicturePath = $this->profile_picture->store('profile-pictures', config('filesystems.upload_disk', 'public'));
        }

        $manualAdmission = ($this->allowManualAdmissionNumber && filled($this->admission_number))
            ? strtoupper(trim($this->admission_number))
            : null;

        try {
            DB::transaction(function () use ($profilePicturePath, $manualAdmission) {
                // 1. Create Student record
                $student = Student::create([
                    'admission_number' => $manualAdmission,
                    'full_name' => trim($this->full_name),
                    'date_of_birth' => $this->date_of_birth,
                    'gender' => $this->gender,
                    'address' => trim($this->address),
                    'previous_school' => trim($this->previous_school),
                    'parent_name' => trim($this->parent_name),
                    'parent_phone' => trim($this->parent_phone),
                    'parent_email' => strtolower(trim($this->parent_email)),
                    'profile_picture' => $profilePicturePath,
                    'status' => 'pending',
                    'registration_completed_at' => now(),
                    'is_portal_active' => false, // Inactive until admin toggles portal
                ]);

                // Ensure admission number is assigned
                $admissionNum = $student->ensureAdmissionNumber();

                // 2. Enroll in active session and selected classroom
                $activeSession = Session::where('is_active', true)->first() ?? Session::first();
                if ($activeSession && $this->classroom_id) {
                    StudentEnrollment::create([
                        'student_id' => $student->id,
                        'session_id' => $activeSession->id,
                        'classroom_id' => $this->classroom_id,
                        'enrolled_at' => now(),
                    ]);
                }

                // 3. Create linked User account for student login
                $accountService = app(StudentAccountService::class);
                $internalEmail = $accountService->internalEmailFor($student);

                $user = User::create([
                    'name' => $student->full_name,
                    'email' => $internalEmail,
                    'password' => Hash::make($this->password),
                    'role' => 'student',
                    'is_active' => false, // Pending portal activation
                    'registration_completed_at' => now(),
                    'email_verified_at' => now(),
                ]);

                $student->update(['user_id' => $user->id]);

                $this->createdStudent = $student;
                $this->generatedAdmissionNumber = $admissionNum;
                $this->generatedEmail = $internalEmail;
            });

            $this->submitted = true;
        } catch (\Throwable $e) {
            \Log::error('Public student registration failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', 'An error occurred during student registration. Please try again.');
        }
    }

    public function render()
    {
        $branding = app(BrandingService::class);
        $classrooms = Classroom::orderBy('name')->get();

        return view('livewire.public-student-registration-form', [
            'appName' => $branding->getAppName(),
            'classrooms' => $classrooms,
            'allowManualAdmissionNumber' => $this->allowManualAdmissionNumber,
        ])->layout('components.layouts.app', [
            'title' => 'Student Online Registration',
        ]);
    }
}
