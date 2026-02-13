<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Staff;
use App\Models\GalleryImage;
use App\Models\Setting;
use App\Models\Inquiry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Setup Image Storage
        $this->command->info('Setting up image storage...');
        Storage::disk('public')->deleteDirectory('images');
        Storage::disk('public')->makeDirectory('images');

        $sourceDir = resource_path('images/seeds');
        $destinationDir = storage_path('app/public/images');

        if (!File::exists($sourceDir)) {
            $this->command->error("Source directory not found: $sourceDir");
            return;
        }

        // Helper function to copy and return path
        $copyImage = function ($filename) use ($sourceDir, $destinationDir) {
            if (File::exists("$sourceDir/$filename")) {
                File::copy("$sourceDir/$filename", "$destinationDir/$filename");
                return "images/$filename";
            }
            return null;
        };

        // Copy all assets
        // Hero Images (User Provided)
        $hero1 = $copyImage('hero-1.jpg');
        $hero2 = $copyImage('hero-2.jpg');
        $hero3 = $copyImage('hero-3.jpg');
        
        // Other User Provided Images
        $staffGroup = $copyImage('staff-group.jpg');
        $graduation = $copyImage('graduation.jpg');
        $culturalDance = $copyImage('cultural-dance.jpg');

        // Fallback/Generated Images (PNGs)
        $whyWkfs = $copyImage('why-wkfs.png');
        $academicsStem = $copyImage('academics-stem.png');
        $aboutCampus = $copyImage('about-campus.png');
        $headOfSchool = $copyImage('head-of-school.png');
        $logo = $copyImage('logo.png');

        // Admin User (Sudo)
        $this->call(SudoUserSeeder::class);
        // School Admin
        $this->call(SchoolAdminSeeder::class);

        // Create current academic session with terms
        $this->command->info('Creating academic session and terms...');
        $currentYear = now()->year;
        $currentSession = \App\Models\Session::createWithTerms($currentYear);
        
        // Activate the session and first term
        $currentSession->activate();
        $currentSession->terms()->where('order', 1)->first()->update(['is_active' => true]);
        
        $this->command->info("Created session: {$currentSession->name} with First Term active");

        // Lesson Notes Module
        $this->command->info('Seeding lesson notes module...');
        $this->call([
            SubjectSeeder::class,
            ClassroomSeeder::class,
            TeacherSubjectAssignmentSeeder::class,
            SubmissionWindowSeeder::class,
            LessonNoteSeeder::class,
        ]);

        // Staff - Leadership
        Staff::factory()->create([
            'name' => 'Mrs. Jane Doe',
            'role' => 'Head of School',
            'bio' => 'Mrs. Doe has over 20 years of experience in early childhood education. She is passionate about creating a nurturing environment for all children.',
            'image' => $headOfSchool,
        ]);

        Staff::factory()->create([
            'name' => 'Mr. John Smith',
            'role' => 'Head of Academics',
            'bio' => 'Mr. Smith ensures our curriculum meets international standards and challenges every student to reach their full potential.',
            'image' => $staffGroup, // Using staff group photo
        ]);

        Staff::factory(4)->create();

        // News & Events
        Post::factory()->create([
            'title' => 'Welcome to the New Academic Session',
            'body' => 'We are thrilled to welcome all our students back to school! This term promises to be full of exciting learning opportunities and events.',
            'published_at' => now()->subDays(2),
            'image' => $hero1,
            'is_featured' => true,
        ]);

        Post::factory()->create([
            'title' => 'Cultural Day Celebrations',
            'body' => 'Our students showcased the rich cultural heritage of Nigeria through dance, music, and fashion. It was a colorful and memorable event.',
            'published_at' => now()->subDays(5),
            'image' => $culturalDance,
            'is_featured' => true,
        ]);

        Post::factory()->create([
            'title' => 'Graduation Ceremony 2024',
            'body' => 'Congratulations to our graduating class! We are so proud of your achievements and wish you the best in your future endeavors.',
            'published_at' => now()->subDays(10),
            'image' => $graduation,
            'is_featured' => true,
        ]);

        Post::factory(5)->create();

        // Gallery
        $categories = ['Sports Day', 'Graduation', 'Field Trips', 'Classroom Activities', 'Art Exhibition', 'Cultural Day'];
        
        // Seed specific gallery images
        GalleryImage::factory()->create(['category' => 'Cultural Day', 'image' => $culturalDance, 'caption' => 'Cultural Dance Performance']);
        GalleryImage::factory()->create(['category' => 'Graduation', 'image' => $graduation, 'caption' => 'Class of 2024']);
        GalleryImage::factory()->create(['category' => 'Classroom Activities', 'image' => $hero2, 'caption' => 'Learning in Action']);
        GalleryImage::factory()->create(['category' => 'Classroom Activities', 'image' => $hero3, 'caption' => 'Student Engagement']);
        
        foreach ($categories as $category) {
            GalleryImage::factory(2)->create(['category' => $category]);
        }

        // Inquiries & Contact Submissions
        Inquiry::factory(5)->create();
        \App\Models\ContactSubmission::factory(5)->create();

        // Settings
        $settings = [
            'school_name' => 'Wonders Kiddies Foundation Schools',
            'school_email' => 'info@wkfs.com',
            'school_phone' => '+234 800 123 4567',
            'school_address' => '123 Education Street, Lagos, Nigeria',
            'fee_schedule_link' => '#',
            'facebook_link' => 'https://facebook.com',
            'instagram_link' => 'https://instagram.com',
            'twitter_link' => 'https://twitter.com',
            'site_logo' => $logo,
            'hero_images' => json_encode([$hero1, $hero2, $hero3]),
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
