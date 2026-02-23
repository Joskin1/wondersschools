<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Seed the settings table with frontend content defaults for tests.
 * Values match what the original hardcoded views contained so existing
 * page-content assertions continue to pass after the dynamic refactor.
 */
function seedFrontendSettings(array $overrides = []): void
{
    $defaults = [
        // Branding
        'school_name'        => 'Wonders Kiddies Foundation Schools',
        'school_tagline'     => 'A Foundation That Builds Futures.',
        'site_logo'          => null,
        'footer_description' => 'Wonders Kiddies Foundation Schools is dedicated to providing a nurturing environment.',
        'social_whatsapp'    => '+2348000000000',

        // Contact
        'school_address' => '123 School Lane, Lagos, Nigeria',
        'school_phone'   => '+234 800 000 0000',
        'school_email'   => 'info@school.edu',
        'maps_embed_url' => 'https://www.google.com/maps/embed?pb=test',

        // SEO
        'seo_title'       => null,
        'seo_description' => null,
        'seo_og_image'    => null,

        // Home: Hero
        'hero_tagline'            => 'Welcome to Wonders Kiddies Foundation Schools',
        'hero_heading'            => 'A Foundation That Builds Futures.',
        'hero_description'        => "We don't just teach children; we cultivate thinkers, leaders, and compassionate citizens in a secure, nurturing environment.",
        'hero_cta_primary_text'   => 'Explore Our Curriculum',
        'hero_cta_secondary_text' => 'Book a Tour',
        'hero_images'             => '[]',

        // Home: Trust Strip
        'trust_items' => json_encode([
            ['label' => 'Verified Curriculum'],
            ['label' => 'Experienced Educators'],
            ['label' => 'Secure Campus'],
            ['label' => 'Proven Results'],
        ]),

        // Home: Why Us
        'why_us_heading'    => 'Why "Wonders"?',
        'why_us_subheading' => 'Because Every Child is a World of Potential.',
        'bento_cards'       => json_encode([
            ['title' => 'Academic Excellence',  'description' => 'Our curriculum is designed to challenge and inspire.'],
            ['title' => 'Holistic Development', 'description' => 'We nurture the whole child.'],
            ['title' => 'Community & Values',   'description' => 'We instill strong moral values.'],
        ]),

        // Home: Stats
        'stats' => json_encode([
            ['value' => '15+',  'label' => 'Years of Excellence'],
            ['value' => '500+', 'label' => 'Happy Students'],
            ['value' => '50+',  'label' => 'Expert Staff'],
            ['value' => '100%', 'label' => 'Parent Satisfaction'],
        ]),

        // About
        'about_heading'     => 'We Build Foundations That Last.',
        'about_tagline'     => 'Wonders Kiddies Foundation Schools',
        'about_description' => 'Wonders Kiddies Foundation Schools (WKFS) is dedicated to providing a high-quality, nurturing, and secure educational environment.',
        'mission_statement' => 'To deliver secure, well-planned education that fosters creativity, academic mastery, and strong character development.',
        'vision_statement'  => 'To be the most trusted educational brand known for foundational excellence, transparency, and dependable long-term student success.',
        'core_values'       => json_encode([
            ['title' => 'Integrity of Instruction',       'description' => ''],
            ['title' => 'Student-Centric Nurturing',      'description' => ''],
            ['title' => 'Strategic Curriculum Delivery',  'description' => ''],
            ['title' => 'Transparent Parent Partnership', 'description' => ''],
            ['title' => 'Long-term Value Creation',       'description' => ''],
        ]),

        // Academics
        'academics_heading'  => 'The WKFS Advantage',
        'academics_tagline'  => 'A Foundation That Outlasts Trends.',
        'academics_intro'    => "<p>A child's future is defined by the quality of their foundation. At Wonders Kiddies Foundation Schools (WKFS), our curriculum is strategically designed not just to meet required standards, but to <strong>exceed them</strong> by cultivating critical thinking, creativity, and essential life skills.</p>",
        'academics_levels'   => json_encode([
            ['title' => 'Early Years Foundation Stage (EYFS)', 'focus' => 'Play-based learning, sensory exploration, and developing early literacy and numeracy.', 'outcome' => 'Building curiosity, fine motor skills, and social-emotional readiness.'],
            ['title' => 'Primary School Programme', 'focus' => 'Mastery of core subjects (Numeracy, Literacy, Science) combined with integrated studies (STEM, Coding Introduction).', 'outcome' => 'Fostering independence, research skills, and strong problem-solving abilities.'],
        ]),
        'academics_subjects' => json_encode([
            ['title' => 'Literacy & Communication', 'description' => 'We emphasize reading for comprehension and creative writing.'],
            ['title' => 'Numeracy & Logic',          'description' => 'Moving beyond rote arithmetic, we use hands-on, conceptual learning to build strong mathematical reasoning.'],
            ['title' => 'Integrated Science (STEM)', 'description' => 'Science is taught through practical experimentation and inquiry.'],
            ['title' => 'Character & Ethics',        'description' => 'Robust training in core values, empathy, leadership, and responsibility.'],
        ]),

        // Admissions
        'admissions_heading' => 'Admissions',
        'admissions_tagline' => 'Join the WKFS family today.',
        'admissions_intro'   => 'We have made our admission process simple and transparent.',
        'admissions_steps'   => json_encode([
            ['title' => 'Inquire',    'description' => 'Fill out the inquiry form below or visit the school to pick up an application form.'],
            ['title' => 'Assessment', 'description' => 'Schedule a brief assessment for your child to help us understand their needs and placement.'],
            ['title' => 'Enrollment', 'description' => 'Complete the registration process and welcome to the family!'],
        ]),
        'fees_intro'        => 'Our fee structure is competitive and offers great value for the quality of education we provide.',
        'fee_schedule_link' => null,
    ];

    $merged = array_merge($defaults, $overrides);

    foreach ($merged as $key => $value) {
        if ($value !== null) {
            \App\Models\Setting::create(['key' => $key, 'value' => $value]);
        }
    }

    // Flush cached settings so the service picks up the freshly seeded values
    app(\App\Services\FrontendContentService::class)->flush();
}
