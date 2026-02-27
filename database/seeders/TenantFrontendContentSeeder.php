<?php

namespace Database\Seeders;

use App\Models\FrontendContent;
use Illuminate\Database\Seeder;

class TenantFrontendContentSeeder extends Seeder
{
    /**
     * Seed the frontend_contents table with per-tenant defaults.
     *
     * Uses firstOrCreate so re-running never overwrites admin customisations.
     * Keys mirror exactly what FrontendLibrary::get() is called with in views.
     */
    public function run(): void
    {
        $name = tenant('name') ?? 'Our School';

        $defaults = [

            // ── Home: Hero ───────────────────────────────────────────────────
            'hero_images'           => '[]',
            'hero_tagline'          => "Welcome to {$name}",
            'hero_heading'          => 'A Foundation That',
            'hero_heading_highlight'=> 'Builds Futures.',
            'hero_description'      => "We don't just teach children; we cultivate thinkers, leaders, and compassionate citizens in a secure, nurturing environment.",
            'hero_cta_primary'      => 'Explore Our Curriculum',
            'hero_cta_secondary'    => 'Book a Tour',

            // ── Home: Trust Strip ────────────────────────────────────────────
            'trust_1' => 'Verified Curriculum',
            'trust_2' => 'Experienced Educators',
            'trust_3' => 'Secure Campus',
            'trust_4' => 'Proven Results',

            // ── Home: Why Us (Bento Cards) ───────────────────────────────────
            'why_us_heading'    => "Why \"{$name}\"?",
            'why_us_subheading' => 'Because Every Child is a World of Potential.',

            'bento_1_title'       => 'Academic Excellence',
            'bento_1_description' => 'Our curriculum is designed to challenge and inspire. We focus on building a strong foundation in literacy, numeracy, and critical thinking.',

            'bento_2_title'       => 'Holistic Development',
            'bento_2_description' => 'We nurture the whole child. From sports to arts, we provide opportunities for students to explore their passions and talents.',

            'bento_3_title'       => 'Community & Values',
            'bento_3_description' => 'We instill strong moral values and a sense of community. Our students learn to be respectful, responsible, and kind.',

            // ── Home: Statistics ─────────────────────────────────────────────
            'stat_1_value' => '15+',
            'stat_1_label' => 'Years of Excellence',
            'stat_2_value' => '500+',
            'stat_2_label' => 'Happy Students',
            'stat_3_value' => '50+',
            'stat_3_label' => 'Expert Staff',
            'stat_4_value' => '100%',
            'stat_4_label' => 'Parent Satisfaction',

            // ── Home: News Section ───────────────────────────────────────────
            'news_heading'    => 'More Than a Classroom',
            'news_subheading' => 'A Place Your Child Can Thrive.',

            // ── Home: Leadership Section ─────────────────────────────────────
            'leadership_heading'    => 'Our Commitment',
            'leadership_subheading' => 'Experienced Hands, Nurturing Hearts.',

            // ── Home: Final CTA Strip ─────────────────────────────────────────
            'cta_heading'   => "Ready for the {$name} Foundation?",
            'cta_enrol'     => 'Enrol Now',
            'cta_tour'      => 'Book a Tour',
            'cta_whatsapp'  => 'Chat on WhatsApp',

            // ── About Page ───────────────────────────────────────────────────
            'about_hero_title'    => 'We Build Foundations That Last.',
            'about_hero_subtitle' => $name,
            'about_description'   => "{$name} is dedicated to providing a high-quality, nurturing, and secure educational environment. Our approach is simple: we focus on the <strong>whole child</strong>—intellectually, emotionally, and morally—to ensure they thrive in every aspect of life. We believe a strong foundation in the early years is the rarest, most valuable asset a parent can provide.",

            'about_mission_title' => 'Our Mission',
            'about_mission_text'  => 'To deliver secure, well-planned education that fosters creativity, academic mastery, and strong character development.',

            'about_vision_title' => 'Our Vision',
            'about_vision_text'  => 'To be the most trusted educational brand known for foundational excellence, transparency, and dependable long-term student success.',

            'about_core_values_title' => 'Our Core Values',
            'core_value_1'            => 'Integrity of Instruction',
            'core_value_2'            => 'Student-Centric Nurturing',
            'core_value_3'            => 'Strategic Curriculum Delivery',
            'core_value_4'            => 'Transparent Parent Partnership',
            'core_value_5'            => 'Long-term Value Creation',

            'about_leadership_title'    => 'Meet Our Leadership',
            'about_leadership_subtitle' => 'The dedicated team guiding our school.',
            'about_leadership_empty'    => 'Leadership team information coming soon.',

            // ── Academics Page ────────────────────────────────────────────────
            'advantage_hero_title'    => 'The Academic Advantage',
            'advantage_hero_subtitle' => 'A Foundation That Outlasts Trends.',
            'advantage_intro'         => "A child's future is defined by the quality of their foundation. At {$name}, our curriculum is strategically designed not just to meet required standards, but to <strong>exceed them</strong> by cultivating critical thinking, creativity, and essential life skills. We combine a solid core curriculum with modern, integrated learning methods to ensure every student is prepared not just for the next class, but for a fast-changing world.",

            'learning_levels_title'    => 'Structured Learning Levels',
            'learning_levels_subtitle' => 'Tailored approaches for every stage of development.',

            'eyfs_title'         => 'Early Years Foundation Stage (EYFS)',
            'eyfs_focus_label'   => 'Focus',
            'eyfs_focus_text'    => 'Play-based learning, sensory exploration, and developing early literacy and numeracy.',
            'eyfs_outcome_label' => 'Key Outcome',
            'eyfs_outcome_text'  => 'Building curiosity, fine motor skills, and social-emotional readiness.',

            'primary_title'         => 'Primary School Programme',
            'primary_focus_label'   => 'Focus',
            'primary_focus_text'    => 'Mastery of core subjects (Numeracy, Literacy, Science) combined with integrated studies (STEM, Coding Introduction).',
            'primary_outcome_label' => 'Key Outcome',
            'primary_outcome_text'  => 'Fostering independence, research skills, and strong problem-solving abilities.',

            'subjects_title'    => 'Subject Highlights: Building Mastery',
            'subjects_subtitle' => 'Our approach to key subject areas.',

            'subject_literacy_title' => 'Literacy & Communication',
            'subject_literacy_text'  => 'We emphasize reading for comprehension and creative writing. Students learn not just <em>what</em> to read, but <em>how</em> to analyze, articulate, and present their ideas confidently.',

            'subject_numeracy_title' => 'Numeracy & Logic',
            'subject_numeracy_text'  => 'Moving beyond rote arithmetic, we use hands-on, conceptual learning to build strong mathematical reasoning. Our students learn to apply logic to real-world problems.',

            'subject_stem_title' => 'Integrated Science (STEM)',
            'subject_stem_text'  => 'Science is taught through practical experimentation and inquiry, preparing students for future tech and engineering fields.',

            'subject_character_title' => 'Character & Ethics',
            'subject_character_text'  => 'Robust training in core values, empathy, leadership, and responsibility, ensuring your child grows into a well-rounded and compassionate individual.',
        ];

        $groups = [
            'hero_images'            => 'home.hero',
            'hero_tagline'           => 'home.hero',
            'hero_heading'           => 'home.hero',
            'hero_heading_highlight' => 'home.hero',
            'hero_description'       => 'home.hero',
            'hero_cta_primary'       => 'home.hero',
            'hero_cta_secondary'     => 'home.hero',
            'trust_1'                => 'home.trust',
            'trust_2'                => 'home.trust',
            'trust_3'                => 'home.trust',
            'trust_4'                => 'home.trust',
            'why_us_heading'         => 'home.why',
            'why_us_subheading'      => 'home.why',
            'bento_1_title'          => 'home.why',
            'bento_1_description'    => 'home.why',
            'bento_2_title'          => 'home.why',
            'bento_2_description'    => 'home.why',
            'bento_3_title'          => 'home.why',
            'bento_3_description'    => 'home.why',
            'stat_1_value'           => 'home.stats',
            'stat_1_label'           => 'home.stats',
            'stat_2_value'           => 'home.stats',
            'stat_2_label'           => 'home.stats',
            'stat_3_value'           => 'home.stats',
            'stat_3_label'           => 'home.stats',
            'stat_4_value'           => 'home.stats',
            'stat_4_label'           => 'home.stats',
            'news_heading'           => 'home.news',
            'news_subheading'        => 'home.news',
            'leadership_heading'     => 'home.leadership',
            'leadership_subheading'  => 'home.leadership',
            'cta_heading'            => 'home.cta',
            'cta_enrol'              => 'home.cta',
            'cta_tour'               => 'home.cta',
            'cta_whatsapp'           => 'home.cta',
            'about_hero_title'       => 'about',
            'about_hero_subtitle'    => 'about',
            'about_description'      => 'about',
            'about_mission_title'    => 'about.mission',
            'about_mission_text'     => 'about.mission',
            'about_vision_title'     => 'about.vision',
            'about_vision_text'      => 'about.vision',
            'about_core_values_title'=> 'about.values',
            'core_value_1'           => 'about.values',
            'core_value_2'           => 'about.values',
            'core_value_3'           => 'about.values',
            'core_value_4'           => 'about.values',
            'core_value_5'           => 'about.values',
            'about_leadership_title'    => 'about.leadership',
            'about_leadership_subtitle' => 'about.leadership',
            'about_leadership_empty'    => 'about.leadership',
            'advantage_hero_title'    => 'academics',
            'advantage_hero_subtitle' => 'academics',
            'advantage_intro'         => 'academics',
            'learning_levels_title'   => 'academics.levels',
            'learning_levels_subtitle'=> 'academics.levels',
            'eyfs_title'              => 'academics.eyfs',
            'eyfs_focus_label'        => 'academics.eyfs',
            'eyfs_focus_text'         => 'academics.eyfs',
            'eyfs_outcome_label'      => 'academics.eyfs',
            'eyfs_outcome_text'       => 'academics.eyfs',
            'primary_title'           => 'academics.primary',
            'primary_focus_label'     => 'academics.primary',
            'primary_focus_text'      => 'academics.primary',
            'primary_outcome_label'   => 'academics.primary',
            'primary_outcome_text'    => 'academics.primary',
            'subjects_title'          => 'academics.subjects',
            'subjects_subtitle'       => 'academics.subjects',
            'subject_literacy_title'  => 'academics.subjects',
            'subject_literacy_text'   => 'academics.subjects',
            'subject_numeracy_title'  => 'academics.subjects',
            'subject_numeracy_text'   => 'academics.subjects',
            'subject_stem_title'      => 'academics.subjects',
            'subject_stem_text'       => 'academics.subjects',
            'subject_character_title' => 'academics.subjects',
            'subject_character_text'  => 'academics.subjects',
        ];

        foreach ($defaults as $key => $value) {
            FrontendContent::firstOrCreate(
                ['key' => $key],
                [
                    'group' => $groups[$key] ?? null,
                    'value' => $value,
                ]
            );
        }
    }
}
