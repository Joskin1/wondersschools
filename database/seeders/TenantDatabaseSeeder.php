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

        foreach ([
            ['name' => 'Mrs. Grace Williams', 'role' => 'Nursery Lead', 'bio' => 'Mrs. Williams supports early learners with patient, play-based teaching.', 'order' => 3],
            ['name' => 'Mr. Daniel Okafor', 'role' => 'Mathematics Teacher', 'bio' => 'Mr. Okafor helps pupils build confidence with numbers and problem solving.', 'order' => 4],
            ['name' => 'Mrs. Amina Bello', 'role' => 'Literacy Coordinator', 'bio' => 'Mrs. Bello leads reading and writing activities across the school.', 'order' => 5],
            ['name' => 'Mr. Peter Adeyemi', 'role' => 'Sports Coordinator', 'bio' => 'Mr. Adeyemi encourages teamwork, fitness, and healthy competition.', 'order' => 6],
        ] as $staff) {
            Staff::firstOrCreate(
                ['name' => $staff['name']],
                array_merge($staff, ['image' => null])
            );
        }
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

        foreach ([
            'Reading Week Begins' => 'Students are taking part in daily reading circles, spelling games, and storytelling sessions this week.',
            'STEM Club Launch' => 'Our new STEM club introduces pupils to practical experiments, robotics basics, and creative problem solving.',
            'Parent Teacher Forum' => 'Parents are invited to meet teachers and discuss learning goals for the current academic term.',
            'Inter-House Sports Update' => 'Preparations are underway for track events, relays, and friendly house competitions.',
            'Art Exhibition Preview' => 'Learners are preparing paintings, crafts, and mixed-media projects for the school art showcase.',
        ] as $title => $body) {
            Post::firstOrCreate(
                ['title' => $title],
                [
                    'slug' => \Illuminate\Support\Str::slug($title),
                    'body' => $body,
                    'image' => null,
                    'published_at' => now()->subDays(random_int(1, 30)),
                    'is_featured' => false,
                ]
            );
        }
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
            foreach (['Highlights', 'Moments'] as $suffix) {
                GalleryImage::firstOrCreate(
                    ['caption' => "{$category} {$suffix}"],
                    [
                        'category' => $category,
                        'image' => 'https://placehold.co/600x400',
                    ]
                );
            }
        }
    }

    private function seedInquiries(): void
    {
        if (Inquiry::count() === 0) {
            foreach (range(1, 5) as $index) {
                Inquiry::create([
                    'name' => "Prospective Parent {$index}",
                    'email' => "parent{$index}@example.com",
                    'phone' => '+2348000000000',
                    'child_age' => (string) random_int(3, 10),
                    'message' => 'I would like to learn more about admission requirements.',
                    'status' => 'pending',
                ]);
            }
        }

        if (\App\Models\ContactSubmission::count() === 0) {
            foreach (range(1, 5) as $index) {
                \App\Models\ContactSubmission::create([
                    'name' => "Website Visitor {$index}",
                    'email' => "visitor{$index}@example.com",
                    'message' => 'Please contact me with more information about the school.',
                    'status' => 'new',
                ]);
            }
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
