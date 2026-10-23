<?php

namespace App\Livewire;

use App\Models\ClassArm;
use App\Models\Classroom;
use App\Models\Department;
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
    public bool $enableClassArms = false;
    public ?int $classroom_id = null;
    public ?int $class_arm_id = null;
    public ?int $department_id = null;
    public string $surname = '';
    public string $first_name = '';
    public string $other_name = '';
    public string $full_name = '';
    public ?string $date_of_birth = null;
    public string $gender = 'male';
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
        $this->enableClassArms = FrontendLibrary::getBooleanSetting('enable_class_arms', false);
    }

    public function updatedFullName(): void
    {
        $parsed = \App\Console\Commands\SplitFullNames::parseFullName($this->full_name);
        $this->surname = $parsed['surname'];
        $this->first_name = $parsed['first_name'];
        $this->other_name = $parsed['other_name'] ?? '';
    }

    public function updatedClassroomId(): void
    {
        $this->class_arm_id = null;
        $this->department_id = null;
    }

    protected function rules(): array
    {
        $this->enableClassArms = FrontendLibrary::getBooleanSetting('enable_class_arms', false);
        $this->allowManualAdmissionNumber = FrontendLibrary::getBooleanSetting('allow_manual_admission_number', false);

        $rules = [
            'surname' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'other_name' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female',
            'profile_picture' => 'nullable|image|max:2048', // 2MB Max
            'previous_school' => 'nullable|string|max:255',
            'address' => 'required|string|max:500',
            'parent_name' => 'required|string|max:255',
            'parent_phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'parent_email' => 'nullable|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];

        $rules['classroom_id'] = 'required|exists:classrooms,id';
        if ($this->enableClassArms) {
            $rules['class_arm_id'] = 'nullable|exists:class_arms,id';
        }

        $selectedClassroom = $this->classroom_id ? Classroom::find($this->classroom_id) : null;
        if ($selectedClassroom?->has_departments) {
            $rules['department_id'] = 'required|exists:departments,id';
        } else {
            $rules['department_id'] = 'nullable';
        }

        if ($this->allowManualAdmissionNumber) {
            $rules['admission_number'] = 'required|string|max:50|unique:students,admission_number';
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'classroom_id.required' => 'Please select your classroom / grade.',
            'department_id.required' => 'Please select your department / stream for this senior class.',
            'admission_number.required' => 'Admission / Registration number is required.',
            'admission_number.unique' => 'This admission number has already been registered to another student.',
            'surname.required' => 'Student surname is required.',
            'first_name.required' => 'Student first name is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'gender.required' => 'Gender is required.',
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

        $this->enableClassArms = FrontendLibrary::getBooleanSetting('enable_class_arms', false);
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
                    'surname' => trim($this->surname),
                    'first_name' => trim($this->first_name),
                    'other_name' => filled($this->other_name) ? trim($this->other_name) : null,
                    'admission_number' => $manualAdmission,
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

                // 2. Enroll in active session and selected classroom (with optional arm and department)
                $activeSession = Session::where('is_active', true)->first() ?? Session::first();
                if ($activeSession && $this->classroom_id) {
                    StudentEnrollment::create([
                        'student_id' => $student->id,
                        'session_id' => $activeSession->id,
                        'classroom_id' => $this->classroom_id,
                        'class_arm_id' => $this->class_arm_id,
                        'department_id' => $this->department_id,
                        'enrolled_at' => now(),
                    ]);
                }

                // 3. Create or configure linked User account for student login
                $accountService = app(StudentAccountService::class);
                $user = $student->user;
                if (! $user) {
                    $user = $accountService->createOrLinkUser(
                        $student,
                        $this->surname,
                        $this->first_name,
                        $this->other_name
                    );
                }

                $user->update([
                    'surname' => trim($this->surname),
                    'first_name' => trim($this->first_name),
                    'other_name' => filled($this->other_name) ? trim($this->other_name) : null,
                    'password' => Hash::make($this->password),
                    'role' => 'student',
                    'is_active' => false,
                    'registration_completed_at' => now(),
                    'email_verified_at' => now(),
                ]);

                $student->forceFill([
                    'user_id' => $user->id,
                    'is_portal_active' => false,
                ])->saveQuietly();

                $internalEmail = $user->email;
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
        $classrooms = Classroom::where('is_active', true)->orderBy('class_order')->orderBy('name')->get();

        $availableArms = [];
        if ($this->enableClassArms && $this->classroom_id) {
            $availableArms = ClassArm::where('classroom_id', $this->classroom_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        $selectedClassroom = $this->classroom_id ? Classroom::find($this->classroom_id) : null;
        $hasDepartments = (bool) ($selectedClassroom?->has_departments);
        $availableDepartments = $hasDepartments
            ? Department::active()->orderBy('name')->get()
            : collect();

        return view('livewire.public-student-registration-form', [
            'appName' => $branding->getAppName(),
            'classrooms' => $classrooms,
            'availableArms' => $availableArms,
            'hasDepartments' => $hasDepartments,
            'availableDepartments' => $availableDepartments,
            'enableClassArms' => $this->enableClassArms,
            'allowManualAdmissionNumber' => $this->allowManualAdmissionNumber,
        ])->layout('components.layouts.app', [
            'title' => 'Student Online Registration',
        ]);
    }
}
