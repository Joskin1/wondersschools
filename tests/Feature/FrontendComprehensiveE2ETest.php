<?php

use App\Livewire\About;
use App\Livewire\Academics;
use App\Livewire\Admissions;
use App\Livewire\Contact;
use App\Livewire\Gallery;
use App\Livewire\News;
use App\Livewire\Post as PostView;
use App\Livewire\PublicStudentRegistrationForm;
use App\Livewire\PublicTeacherRegistrationForm;
use App\Models\Classroom;
use App\Models\GalleryImage;
use App\Models\Post;
use App\Models\Session;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\TenantFrontendContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    (new TenantFrontendContentSeeder())->run();
});

describe('Comprehensive Frontend Suite', function () {

    it('renders all public frontend routes with 200 OK status', function () {
        // 1. Home
        get('/')->assertOk()->assertSee('Apex Crown College');

        // 2. About Us
        get('/about-us')->assertOk()->assertSeeLivewire(About::class)->assertSee('We Build Foundations That Last');

        // 3. Academics
        get('/academics')->assertOk()->assertSeeLivewire(Academics::class)->assertSee('The WKFS Advantage');

        // 4. Admissions
        get('/admissions')->assertOk()->assertSeeLivewire(Admissions::class)->assertSee('Admissions');

        // 5. Gallery
        get('/gallery')->assertOk()->assertSeeLivewire(Gallery::class)->assertSee('Gallery');

        // 6. News
        $post = Post::factory()->create([
            'title' => 'Distinguished Academic Honors 2026',
            'body' => 'Our students have excelled in state competitions.',
            'published_at' => now(),
        ]);
        get('/news')->assertOk()->assertSeeLivewire(News::class)->assertSee('News & Events', false);

        // 7. Single News Article
        get("/news/{$post->slug}")->assertOk()->assertSeeLivewire(PostView::class)->assertSee('Distinguished Academic Honors 2026')->assertSee('Back to News');

        // 8. Contact Us
        get('/contact-us')->assertOk()->assertSeeLivewire(Contact::class)->assertSee('Contact Us')->assertSee('Send a Message');

        // 9. Student Registration
        get('/register/student')->assertOk()->assertSeeLivewire(PublicStudentRegistrationForm::class)->assertSee('Student Online Registration');

        // 10. Teacher Registration
        get('/register/teacher')->assertOk()->assertSeeLivewire(PublicTeacherRegistrationForm::class)->assertSee('Teacher Self-Registration');
    });

    it('injects brand tokens and design system variables into HTML', function () {
        $response = get('/');
        $response->assertOk();
        $response->assertSee('--color-tenant-primary', false);
        $response->assertSee('--ink', false);
        $response->assertSee('--support', false);
        $response->assertSee('--accent', false);
        $response->assertSee('--rule', false);
        $response->assertSee('--paper', false);
    });

    it('handles gallery category filtering and lightbox modal state', function () {
        $sportsImage = GalleryImage::factory()->create(['category' => 'sports', 'caption' => 'Annual Athletics Meet']);
        $artsImage = GalleryImage::factory()->create(['category' => 'arts', 'caption' => 'Spring Symphony Orchestration']);

        Livewire::test(Gallery::class)
            ->assertSee('Annual Athletics Meet')
            ->assertSee('Spring Symphony Orchestration')
            ->call('setCategory', 'sports')
            ->assertSee('Annual Athletics Meet')
            ->assertDontSee('Spring Symphony Orchestration')
            ->call('setCategory', 'all')
            ->assertSee('Annual Athletics Meet')
            ->assertSee('Spring Symphony Orchestration');
    });

    it('processes contact form submissions and creates database record', function () {
        Livewire::test(Contact::class)
            ->set('name', 'Lady Eleanor Sterling')
            ->set('email', 'eleanor.sterling@example.org')
            ->set('message', 'We wish to arrange an institutional tour of the secondary campus laboratories.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Thank you for contacting us. We will get back to you soon.');

        assertDatabaseHas('contact_submissions', [
            'name' => 'Lady Eleanor Sterling',
            'email' => 'eleanor.sterling@example.org',
            'status' => 'new',
        ]);
    });

    it('processes admissions inquiries end to end', function () {
        Livewire::test(Admissions::class)
            ->set('name', 'Sir Arthur Pendelton')
            ->set('email', 'arthur.pendelton@example.com')
            ->set('phone', '+234 803 111 2222')
            ->set('child_age', '10 Years / Grade 5 Entry')
            ->set('message', 'Please forward the formal prospectus and fee structure.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Thank you for your inquiry. We will contact you shortly.');

        assertDatabaseHas('inquiries', [
            'name' => 'Sir Arthur Pendelton',
            'email' => 'arthur.pendelton@example.com',
            'child_age' => '10 Years / Grade 5 Entry',
        ]);
    });

    it('processes student public self registration with linked user account', function () {
        Storage::fake('public');
        $session = Session::factory()->create(['is_active' => true]);
        $classroom = Classroom::factory()->create(['name' => 'Basic 6 Gold']);
        $photo = UploadedFile::fake()->image('scholar.jpg', 200, 200);

        Livewire::test(PublicStudentRegistrationForm::class)
            ->set('full_name', 'Victoria Kimberly Adeleke')
            ->set('date_of_birth', '2014-06-12')
            ->set('gender', 'female')
            ->set('classroom_id', $classroom->id)
            ->set('profile_picture', $photo)
            ->set('previous_school', 'Lagos Prep International')
            ->set('address', '14 Victoria Garden City, Lekki')
            ->set('parent_name', 'Engr. Dapo Adeleke')
            ->set('parent_phone', '08023456789')
            ->set('parent_email', 'dapo.adeleke@example.com')
            ->set('password', 'SecretStudent2026!')
            ->set('password_confirmation', 'SecretStudent2026!')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSee('Student Registration Received!')
            ->assertSee('Victoria Kimberly Adeleke');

        $student = Student::where('full_name', 'Victoria Kimberly Adeleke')->first();
        expect($student)->not->toBeNull();
        expect($student->admission_number)->not->toBeNull();
        expect($student->gender)->toBe('female');
        expect($student->is_portal_active)->toBeFalse();

        $user = User::where('id', $student->user_id)->first();
        expect($user)->not->toBeNull();
        expect($user->role)->toBe('student');
        expect($user->isActive())->toBeFalse();
    });

    it('processes teacher public self registration with inactive portal access', function () {
        Storage::fake('public');
        $photo = UploadedFile::fake()->image('faculty.jpg', 200, 200);

        Livewire::test(PublicTeacherRegistrationForm::class)
            ->set('name', 'Dr. Bartholomew Higgins')
            ->set('email', 'bartholomew.higgins@example.org')
            ->set('phone', '08098765432')
            ->set('gender', 'male')
            ->set('dob', '1988-11-20')
            ->set('address', '8 University Crescent, Yaba')
            ->set('profile_picture', $photo)
            ->set('password', 'FacultyMasterPass123!')
            ->set('password_confirmation', 'FacultyMasterPass123!')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSee('Registration Submitted Successfully!')
            ->assertSee('Dr. Bartholomew Higgins');

        $user = User::where('email', 'bartholomew.higgins@example.org')->first();
        expect($user)->not->toBeNull();
        expect($user->role)->toBe('teacher');
        expect($user->isActive())->toBeFalse();

        $teacher = Teacher::where('user_id', $user->id)->first();
        expect($teacher)->not->toBeNull();
        expect($teacher->phone)->toBe('08098765432');
        expect($teacher->gender)->toBe('male');
    });

});
