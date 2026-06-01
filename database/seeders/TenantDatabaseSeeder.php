<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GalleryImage;
use App\Models\Inquiry;
use App\Models\Post;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

// Added imports for the new seeders
use Database\Seeders\ChizyliteAcademySeeder;
use Database\Seeders\TeacherSeeder;
use Database\Seeders\StudentSeeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seeds a freshly-created tenant DB with default settings and dev sample data.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->seed();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private: orchestration
    // ──────────────────────────────────────────────────────────────────────────

    private function seed(): void
    {
        $name = tenant('name') ?? 'Wonders School';

        // Settings are always idempotent (firstOrCreate per key).
        $this->seedSettings($name);

        // Frontend content — delegates to its own idempotent seeder.
        $this->call(TenantFrontendContentSeeder::class);

        // School admin user — delegates to its own idempotent seeder.
        $this->call(SchoolAdminSeeder::class);

        // Academic session + terms (only when none exist yet).
        if (Session::count() === 0) {
            $this->command?->info('Creating academic session and terms…');
            $currentSession = Session::createWithTerms(now()->year);
            $currentSession->activate();
            $currentSession->terms()->where('order', 1)->first()?->update(['is_active' => true]);
            $this->command?->info("Created session: {$currentSession->name} with First Term active.");
        }

        // Lesson-notes module (each sub-seeder uses firstOrCreate internally).
        $this->call([
            SubjectSeeder::class,
            ClassroomSeeder::class,
            // Include the comprehensive academy seeder which creates teachers, students, and assignments
            ChizyliteAcademySeeder::class,
            SubmissionWindowSeeder::class,
            LessonNoteSeeder::class,
        ]);

        // Results module — score heads (always idempotent).
        $this->call(ScoreHeadSeeder::class);

        // Sample data: only seed when the target table is completely empty so
        // subsequent retries of the provisioning job never attempt duplicates.
        $this->seedStaff();
        $this->seedPosts();
        $this->seedGallery();
        $this->seedInquiries();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private: per-entity idempotent seeders
    // ──────────────────────────────────────────────────────────────────────────

    private function seedStaff(): void
    {
        if (Staff::count() > 0) {
            return;
        }

        $this->command?->info('Seeding staff…');

        Staff::firstOrCreate(
            ['name' => 'Mrs. Jane Doe'],
            [
                'role'  => 'Head of School',
                'bio'   => 'Mrs. Doe has over 20 years of experience in early childhood education.',
                'image' => null,
            ]
        );

        Staff::firstOrCreate(
            ['name' => 'Mr. John Smith'],
            [
                'role'  => 'Head of Academics',
                'bio'   => 'Mr. Smith ensures our curriculum meets international standards and challenges every student to reach their full potential.',
                'image' => null,
            ]
        );

        Staff::factory(4)->create(['image' => null]);
    }

    private function seedPosts(): void
    {
        if (Post::count() > 0) {
            return;
        }

        $this->command?->info('Seeding posts…');

        $articles = [
            [
                'title'        => 'Welcome to the New Academic Session',
                'body'         => 'We are thrilled to welcome all our students back to school! This term promises to be full of exciting learning opportunities and events.',
                'published_at' => now()->subDays(2),
                'image'        => null,
                'is_featured'  => true,
            ],
            [
                'title'        => 'Cultural Day Celebrations',
                'body'         => 'Our students showcased the rich cultural heritage of Nigeria through dance, music, and fashion.',
                'published_at' => now()->subDays(5),
                'image'        => null,
                'is_featured'  => true,
            ],
            [
                'title'        => 'Graduation Ceremony 2024',
                'body'         => 'Congratulations to our graduating class! We are so proud of your achievements.',
                'published_at' => now()->subDays(10),
                'image'        => null,
                'is_featured'  => true,
            ],
        ];

        foreach ($articles as $article) {
            Post::firstOrCreate(
                ['title' => $article['title']],
                array_merge($article, ['slug' => \Illuminate\Support\Str::slug($article['title'])])
            );
        }

        Post::factory(5)->create(['image' => null]);
    }

    private function seedGallery(): void
    {
        if (GalleryImage::count() > 0) {
            return;
        }

        $this->command?->info('Seeding gallery…');

        $featured = [
            ['category' => 'Cultural Day',         'caption' => 'Cultural Dance Performance'],
            ['category' => 'Graduation',            'caption' => 'Class of 2024'],
            ['category' => 'Classroom Activities',  'caption' => 'Learning in Action'],
            ['category' => 'Classroom Activities',  'caption' => 'Student Engagement'],
        ];

        foreach ($featured as $attrs) {
            GalleryImage::firstOrCreate(
                ['caption' => $attrs['caption']],
                [
                    'category' => $attrs['category'],
                    'image'    => 'https://placehold.co/600x400',
                ]
            );
        }

        $categories = ['Sports Day', 'Graduation', 'Field Trips', 'Classroom Activities', 'Art Exhibition', 'Cultural Day'];

        foreach ($categories as $category) {
            GalleryImage::factory(2)->create(['category' => $category]);
        }
    }

    private function seedInquiries(): void
    {
        if (Inquiry::count() === 0) {
            Inquiry::factory(5)->create();
        }

        if (\App\Models\ContactSubmission::count() === 0) {
            \App\Models\ContactSubmission::factory(5)->create();
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private: default settings (always idempotent via firstOrCreate)
    // ──────────────────────────────────────────────────────────────────────────

    private function seedSettings(string $name): void
    {
        $defaults = [
            // ── Branding ─────────────────────────────────────────────────────
            'school_name'        => $name,
            'school_tagline'     => 'A Foundation That Builds Futures.',
            'site_logo'          => null,
            'footer_description' => "{$name} is dedicated to providing a nurturing and stimulating environment for children to learn, grow, and thrive.",
            'social_whatsapp'    => '+2348000000000',

            // ── Contact ──────────────────────────────────────────────────────
            'school_address'     => '123 School Lane, Lagos, Nigeria',
            'school_phone'       => '+234 800 000 0000',
            'school_email'       => 'info@school.edu',
            'maps_embed_url'     => 'https://www.google.com/maps/embed?...',

            // ── SEO ──────────────────────────────────────────────────────────
            'seo_title'          => "{$name} — Nurturing Excellence",
            'seo_description'    => "Welcome to {$name}. We provide quality, nurturing education for children in a safe and stimulating environment.",
            'seo_og_image'       => null,

            // ... (rest of the defaults unchanged) ...
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(
                ['key'   => $key],
                ['value' => $value]
            );
        }
    }
}
