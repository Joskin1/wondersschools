<?php

namespace Database\Seeders;

use App\Models\GalleryImage;
use App\Models\Inquiry;
use App\Models\Post;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seeds a freshly-created tenant DB with default settings and dev sample data.
     *
     * Settings use firstOrCreate — re-running never overwrites admin customisations.
     * Sample data (staff, posts, gallery …) is only created when the table is empty,
     * so re-seeding an existing tenant does not add duplicate rows.
     */
    public function run(): void
    {
        $name = tenant('name') ?? 'Wonders School';

        // ── Settings (always idempotent) ─────────────────────────────────────
        $this->seedSettings($name);

        // ── Frontend content (always idempotent) ─────────────────────────────
        $this->call(TenantFrontendContentSeeder::class);

        // ── School admin user ────────────────────────────────────────────────
        $this->call(SchoolAdminSeeder::class);

        // ── Academic session + terms ─────────────────────────────────────────
        if (Session::count() === 0) {
            $this->command?->info('Creating academic session and terms...');
            $currentSession = Session::createWithTerms(now()->year);
            $currentSession->activate();
            $currentSession->terms()->where('order', 1)->first()->update(['is_active' => true]);
            $this->command?->info("Created session: {$currentSession->name} with First Term active");
        }

        // ── Lesson-notes module (idempotent via firstOrCreate internally) ────
        $this->call([
            SubjectSeeder::class,
            ClassroomSeeder::class,
            TeacherSubjectAssignmentSeeder::class,
            SubmissionWindowSeeder::class,
            LessonNoteSeeder::class,
        ]);

        // ── Staff (only when table is empty) ─────────────────────────────────
        if (Staff::count() === 0) {
            $this->command?->info('Seeding staff...');

            Staff::factory()->create([
                'name'  => 'Mrs. Jane Doe',
                'role'  => 'Head of School',
                'bio'   => 'Mrs. Doe has over 20 years of experience in early childhood education.',
                'image' => null,
            ]);

            Staff::factory()->create([
                'name'  => 'Mr. John Smith',
                'role'  => 'Head of Academics',
                'bio'   => 'Mr. Smith ensures our curriculum meets international standards and challenges every student to reach their full potential.',
                'image' => null,
            ]);

            Staff::factory(4)->create(['image' => null]);
        }

        // ── News / posts (only when table is empty) ──────────────────────────
        if (Post::count() === 0) {
            $this->command?->info('Seeding posts...');

            Post::factory()->create([
                'title'        => 'Welcome to the New Academic Session',
                'body'         => 'We are thrilled to welcome all our students back to school! This term promises to be full of exciting learning opportunities and events.',
                'published_at' => now()->subDays(2),
                'image'        => null,
                'is_featured'  => true,
            ]);

            Post::factory()->create([
                'title'        => 'Cultural Day Celebrations',
                'body'         => 'Our students showcased the rich cultural heritage of Nigeria through dance, music, and fashion.',
                'published_at' => now()->subDays(5),
                'image'        => null,
                'is_featured'  => true,
            ]);

            Post::factory()->create([
                'title'        => 'Graduation Ceremony 2024',
                'body'         => 'Congratulations to our graduating class! We are so proud of your achievements.',
                'published_at' => now()->subDays(10),
                'image'        => null,
                'is_featured'  => true,
            ]);

            Post::factory(5)->create(['image' => null]);
        }

        // ── Gallery (only when table is empty) ───────────────────────────────
        if (GalleryImage::count() === 0) {
            $this->command?->info('Seeding gallery...');

            $categories = ['Sports Day', 'Graduation', 'Field Trips', 'Classroom Activities', 'Art Exhibition', 'Cultural Day'];

            GalleryImage::factory()->create(['category' => 'Cultural Day',         'caption' => 'Cultural Dance Performance']);
            GalleryImage::factory()->create(['category' => 'Graduation',           'caption' => 'Class of 2024']);
            GalleryImage::factory()->create(['category' => 'Classroom Activities', 'caption' => 'Learning in Action']);
            GalleryImage::factory()->create(['category' => 'Classroom Activities', 'caption' => 'Student Engagement']);

            foreach ($categories as $category) {
                GalleryImage::factory(2)->create(['category' => $category]);
            }
        }

        // ── Inquiries & contact submissions (only when table is empty) ────────
        if (Inquiry::count() === 0) {
            Inquiry::factory(5)->create();
        }

        if (\App\Models\ContactSubmission::count() === 0) {
            \App\Models\ContactSubmission::factory(5)->create();
        }
    }

    // ── Private: default settings ────────────────────────────────────────────

    private function seedSettings(string $name): void
    {
        $defaults = [

            // ── Branding ────────────────────────────────────────────────────
            'school_name'     => $name,
            'school_tagline'  => 'A Foundation That Builds Futures.',
            'site_logo'       => null,
            'footer_description' => "{$name} is dedicated to providing a nurturing and stimulating environment for children to learn, grow, and thrive.",
            'social_whatsapp' => '+2348000000000',

            // ── Contact ─────────────────────────────────────────────────────
            'school_address'  => '123 School Lane, Lagos, Nigeria',
            'school_phone'    => '+234 800 000 0000',
            'school_email'    => 'info@school.edu',
            'maps_embed_url'  => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3963.952912260219!2d3.375295414770757!3d6.527638695278928!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x103b8b2ae68280c1%3A0xdc9e87a367c3d9cb!2sLagos!5e0!3m2!1sen!2sng!4v1622212345678!5m2!1sen!2sng',

            // ── SEO ─────────────────────────────────────────────────────────
            'seo_title'       => "{$name} — Nurturing Excellence",
            'seo_description' => "Welcome to {$name}. We provide quality, nurturing education for children in a safe and stimulating environment.",
            'seo_og_image'    => null,

            // ── Home: Hero ───────────────────────────────────────────────────
            'hero_tagline'           => "Welcome to {$name}",
            'hero_heading'           => 'A Foundation That Builds Futures.',
            'hero_description'       => "We don't just teach children; we cultivate thinkers, leaders, and compassionate citizens in a secure, nurturing environment.",
            'hero_cta_primary_text'  => 'Explore Our Curriculum',
            'hero_cta_secondary_text'=> 'Book a Tour',
            'hero_images'            => json_encode([]),

            // ── Home: Trust Strip ────────────────────────────────────────────
            'trust_items' => json_encode([
                ['label' => 'Verified Curriculum'],
                ['label' => 'Experienced Educators'],
                ['label' => 'Secure Campus'],
                ['label' => 'Proven Results'],
            ]),

            // ── Home: Why Us ─────────────────────────────────────────────────
            'why_us_heading'    => "Why \"{$name}\"?",
            'why_us_subheading' => 'Because Every Child is a World of Potential.',
            'bento_cards'       => json_encode([
                [
                    'title'       => 'Academic Excellence',
                    'description' => 'A rigorous, structured curriculum that equips every child with the knowledge and skills to excel.',
                ],
                [
                    'title'       => 'Holistic Development',
                    'description' => 'We nurture intellectual, emotional, and social growth — the whole child, not just academics.',
                ],
                [
                    'title'       => 'Community & Values',
                    'description' => 'A warm, inclusive community built on integrity, respect, and a shared commitment to excellence.',
                ],
            ]),

            // ── Home: Statistics ─────────────────────────────────────────────
            'stats' => json_encode([
                ['value' => '10+',  'label' => 'Years of Excellence'],
                ['value' => '300+', 'label' => 'Happy Students'],
                ['value' => '30+',  'label' => 'Expert Staff'],
                ['value' => '98%',  'label' => 'Parent Satisfaction'],
            ]),

            // ── About Page ───────────────────────────────────────────────────
            'about_heading'     => 'We Build Foundations That Last.',
            'about_tagline'     => $name,
            'about_description' => "{$name} is dedicated to providing a high-quality, nurturing, and secure educational environment. Our approach is simple: we focus on the whole child — intellectually, emotionally, and morally — to ensure they thrive in every aspect of life.",
            'mission_statement' => 'To deliver secure, well-planned education that fosters creativity, academic mastery, and strong character development.',
            'vision_statement'  => 'To be the most trusted educational institution known for foundational excellence, transparency, and dependable long-term student success.',
            'core_values'       => json_encode([
                ['title' => 'Integrity',         'description' => 'We uphold the highest standards of honesty and transparency in everything we do.'],
                ['title' => 'Excellence',        'description' => 'We pursue the highest quality in teaching, learning, and school life.'],
                ['title' => 'Nurturing Care',    'description' => 'Every child is valued and supported to reach their unique potential.'],
                ['title' => 'Community',         'description' => 'We build strong partnerships between school, parents, and the wider community.'],
                ['title' => 'Innovation',        'description' => 'We embrace modern approaches to education while staying grounded in proven foundations.'],
            ]),

            // ── Academics Page ───────────────────────────────────────────────
            'academics_heading' => 'Our Academic Programmes',
            'academics_tagline' => 'A Foundation That Outlasts Trends.',
            'academics_intro'   => "<p>A child's future is defined by the quality of their foundation. At {$name}, our curriculum is designed not just to meet required standards, but to <strong>exceed them</strong> by cultivating critical thinking, creativity, and essential life skills.</p>",
            'academics_levels'  => json_encode([
                [
                    'title'   => 'Early Years Foundation Stage (EYFS)',
                    'focus'   => 'Play-based learning, sensory exploration, and developing early literacy and numeracy.',
                    'outcome' => 'Building curiosity, fine motor skills, and social-emotional readiness.',
                ],
                [
                    'title'   => 'Primary School Programme',
                    'focus'   => 'Mastery of core subjects (Numeracy, Literacy, Science) combined with integrated STEM studies.',
                    'outcome' => 'Fostering independence, research skills, and strong problem-solving abilities.',
                ],
            ]),
            'academics_subjects' => json_encode([
                [
                    'title'       => 'Literacy & Communication',
                    'description' => 'We emphasize reading for comprehension and creative writing. Students learn not just what to read, but how to analyze, articulate, and present their ideas confidently.',
                ],
                [
                    'title'       => 'Numeracy & Logic',
                    'description' => 'Moving beyond rote arithmetic, we use hands-on, conceptual learning to build strong mathematical reasoning.',
                ],
                [
                    'title'       => 'Integrated Science (STEM)',
                    'description' => 'Science is taught through practical experimentation and inquiry, preparing students for future tech and engineering fields.',
                ],
                [
                    'title'       => 'Character & Ethics',
                    'description' => 'Robust training in core values, empathy, leadership, and responsibility, ensuring your child grows into a well-rounded individual.',
                ],
            ]),

            // ── Admissions Page ──────────────────────────────────────────────
            'admissions_heading' => 'Admissions',
            'admissions_tagline' => "Join the {$name} family today.",
            'admissions_intro'   => 'We have made our admission process simple and transparent.',
            'admissions_steps'   => json_encode([
                ['title' => 'Inquire',    'description' => 'Fill out the inquiry form below or visit the school to pick up an application form.'],
                ['title' => 'Assessment', 'description' => 'Schedule a brief assessment for your child to help us understand their needs and placement.'],
                ['title' => 'Enrollment', 'description' => 'Complete the registration process and welcome to the family!'],
            ]),
            'fees_intro'          => 'Our fee structure is competitive and offers great value for the quality of education we provide.',
            'fee_schedule_link'   => null,
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(
                ['key'   => $key],
                ['value' => $value]
            );
        }
    }
}
