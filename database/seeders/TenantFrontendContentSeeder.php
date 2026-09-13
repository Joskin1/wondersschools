<?php

namespace Database\Seeders;

use App\Models\FrontendContent;
use App\Models\Setting;
use App\Services\FrontendLibrary;
use Illuminate\Database\Seeder;

class TenantFrontendContentSeeder extends Seeder
{
    /**
     * Seed frontend_contents and settings tables with per-tenant defaults.
     *
     * // TODO: switch to firstOrCreate before first production tenant
     */
    public function run(): void
    {
        $schoolName = tenant('name') ?? 'Apex Crown College';

        // 1. Core Settings
        $settings = [
            'school_name'             => $schoolName,
            'school_short_name'       => 'AC',
            'school_motto'            => 'Excellence, Character & Leadership',
            'school_established'      => '2001',
            'school_phone'            => '+234 800 123 4567',
            'school_email'            => 'admissions@apexcrown.edu.ng',
            'school_address'          => 'Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos State, Nigeria',
            'primary_color'           => '#0B2545',
            'secondary_color'         => '#1E293B',
            'accent_color'            => '#C8A951',
            'student_portal_url'      => '/student/login',
            'staff_portal_url'        => '/teacher/login',
            'admin_portal_url'        => '/admin/login',
            'footer_social_facebook'  => null,
            'footer_social_instagram' => null,
            'footer_social_linkedin'  => null,
            'footer_social_x'         => null,
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // 2. Frontend Contents
        $contents = [
            // Top Announcement Bar & Navigation
            'topbar_badge'               => 'Admissions 2026/2027',
            'topbar_text'                => 'Entrance examination and transfer enrollment now open.',
            'nav_about_label'            => 'About',
            'nav_features_label'         => 'Distinctives',
            'nav_academics_label'        => 'Curriculum',
            'nav_stats_label'            => 'Outcomes',
            'nav_facilities_label'       => 'Campus',
            'nav_news_label'             => 'Bulletin',
            'nav_contact_label'          => 'Contact',
            'nav_portals_label'          => 'Portals',
            'portal_student_label'       => 'Student Portal',
            'portal_staff_label'         => 'Faculty Portal',
            'portal_admin_label'         => 'Administration',
            'portal_student_label_short' => 'Student',
            'portal_staff_label_short'   => 'Faculty',
            'portal_admin_label_short'   => 'Admin',
            'header_cta_text'            => 'Admissions',
            'header_cta_link'            => '#admissions',

            // Hero Section
            'hero_badge'                 => '2026 / 2027 Academic Session',
            'hero_title'                 => 'Nurturing Intellectual Depth & Moral Leadership',
            'hero_subtitle'              => 'An accredited British-Nigerian secondary institution committed to scholastic rigor, scientific inquiry, and the formation of character.',
            'hero_image'                 => 'https://placehold.co/1920x1080/0B2545/FAF8F4?text=Apex+Crown+College+Scholars+Lagos',
            'hero_image_alt'             => 'Apex Crown College Scholars Lagos',
            'hero_primary_cta_text'      => 'Apply for Admission',
            'hero_primary_cta_link'      => '#admissions',
            'hero_secondary_cta_text'    => 'Explore Prospectus',
            'hero_secondary_cta_link'    => '#about',
            'hero_scroll_label'          => 'Scroll to explore prospectus',

            // 01 About Section
            'about_eyebrow'              => 'ABOUT THE COLLEGE',
            'about_heading'              => 'A Tradition of Uncompromising Academic Standard',
            'about_image'                => 'https://placehold.co/800x1000/0B2545/FAF8F4?text=Principal+Portrait',
            'about_image_alt'            => 'Dr. Mrs. Adebisi Balogun Head of School',
            'about_years_badge'          => '25',
            'about_years_label'          => 'Years of Academic Legacy in Lagos',
            'about_body'                 => '<p>Founded in 2001, Apex Crown College synthesizes the rigorous Nigerian National Basic & Senior Secondary Curriculum with Cambridge Assessment International standards. We believe secondary education is not simply an examination preparatory phase, but the crucible where character, intellectual curiosity, and self-governance are forged.</p><p>Our dedicated tutorial masters, modern science laboratories, and immersive pastoral mentorship ensure every student discovers their latent gifts and matures into an articulate, disciplined contributor to national and global society.</p>',
            'about_principal_name'       => 'Dr. (Mrs.) Adebisi Balogun',
            'about_principal_title'      => 'B.Sc, M.Ed, Ph.D. — Principal & Head of School',

            // 02 Distinctives Section
            'features_eyebrow'           => 'DISTINCTIVES',
            'features_heading'           => 'The Pillars of an Apex Crown Education',
            'features_intro'             => 'A deliberate blend of academic depth, moral discipline, and technological literacy structured to cultivate leaders.',
            'features_cta_text'          => 'Review Full Curriculum',
            'features_cta_link'          => '#academics',
            'features_items'             => json_encode([
                [
                    'title' => 'Integrated Dual Curriculum',
                    'desc'  => 'Simultaneous mastery of the Nigerian National Curriculum (WAEC & NECO) alongside British Cambridge Checkpoint and IGCSE examinations.',
                ],
                [
                    'title' => 'Individualized Tutorial Mentorship',
                    'desc'  => 'Strict 1:12 faculty-to-student ratio ensuring individualized attention, customized academic interventions, and dedicated pastoral tutors.',
                ],
                [
                    'title' => 'Applied STEM & Computational Thinking',
                    'desc'  => 'Purpose-built laboratories for physics, chemistry, biology, agricultural science, and dedicated robotics/coding suites.',
                ],
                [
                    'title' => 'Moral Formation & Character Discipline',
                    'desc'  => 'Uncompromising emphasis on integrity, punctuality, self-respect, civic responsibility, and community service.',
                ],
                [
                    'title' => 'Comprehensive Boarding & Pastoral Care',
                    'desc'  => 'Modern, secure air-conditioned dormitories with round-the-clock power, resident housemasters, and multi-course nutritional dining.',
                ],
                [
                    'title' => 'Oratory, Athletics & Cultural Life',
                    'desc'  => 'Weekly parliamentary debating, orchestral music tuition, Model United Nations, and championship track and field athletics.',
                ],
            ]),

            // 03 Outcomes / Stats
            'stats_eyebrow'              => 'EXAMINATION OUTCOMES',
            'stats_heading'              => 'Ten-Year Record of Scholastic Excellence',
            'stats_items'                => json_encode([
                [
                    'value'  => '100%',
                    'label'  => 'WAEC Pass Rate',
                    'detail' => '5+ credits including English & Maths (10-year consecutive record)',
                ],
                [
                    'value'  => '94.8%',
                    'label'  => 'A1 - B3 Distinctions',
                    'detail' => 'Achieved across Mathematics, Further Math, Physics, and Chemistry',
                ],
                [
                    'value'  => '312',
                    'label'  => 'Average JAMB UTME',
                    'detail' => 'With the top candidate achieving 358 in the 2025 UTME session',
                ],
                [
                    'value'  => '98%',
                    'label'  => 'University Placement',
                    'detail' => 'Direct admissions into premier universities across Nigeria, the UK, US, and Canada',
                ],
            ]),
            'stats_destinations_label'   => 'Representative Matriculations:',
            'stats_destinations'         => json_encode([
                'University of Ibadan',
                'University of Lagos',
                'Covenant University',
                'Imperial College London',
                'University of Toronto',
                'University of Manchester',
            ]),

            // 04 Curriculum / Academics
            'academics_eyebrow'          => 'CURRICULUM & PROGRAMMES',
            'academics_heading'          => 'Structured Pathways for Secondary Scholars',
            'academics_intro'            => 'A comprehensive curriculum designed to build foundational mastery in the junior years and deep specialization in the senior years.',
            'academics_tracks'           => json_encode([
                [
                    'code'     => 'JSS 1 — JSS 3',
                    'name'     => 'Junior Secondary School',
                    'ages'     => 'Ages 10 — 13 Years',
                    'certs'    => 'BECE & Cambridge Checkpoint',
                    'desc'     => 'Focuses on foundational intellectual development: computational thinking, language mastery, basic science, and cultural appreciation.',
                    'subjects' => ['General Mathematics', 'English & Literature', 'Basic Science & Tech', 'Coding Basics', 'French & Languages', 'Business Studies'],
                ],
                [
                    'code'     => 'SSS 1 — SSS 3',
                    'name'     => 'Senior Sciences & Technology',
                    'ages'     => 'Ages 13 — 17 Years',
                    'certs'    => 'WAEC, NECO, IGCSE & JAMB',
                    'desc'     => 'Rigorous scientific inquiry for aspiring medical doctors, software architects, agricultural biotechnologists, and structural engineers.',
                    'subjects' => ['Further Mathematics', 'Physics & Chemistry', 'Biology & Agric', 'Technical Drawing', 'Data Processing', 'Weekly Practical Labs'],
                ],
                [
                    'code'     => 'SSS 1 — SSS 3',
                    'name'     => 'Senior Arts & Commercial Studies',
                    'ages'     => 'Ages 13 — 17 Years',
                    'certs'    => 'WAEC, NECO, IGCSE & JAMB',
                    'desc'     => 'For future jurists, economists, chartered accountants, diplomats, and business leaders with intensive essay and analysis training.',
                    'subjects' => ['Literature in English', 'Government & History', 'Financial Accounting', 'Economics & Commerce', 'Visual Arts & Music', 'Debating Society'],
                ],
            ]),

            // 05 Facilities
            'facilities_eyebrow'         => 'CAMPUS INFRASTRUCTURE',
            'facilities_heading'         => 'Purpose-Built Learning & Living Environments',
            'facilities_items'           => json_encode([
                [
                    'title'    => 'Advanced Science Laboratories',
                    'category' => 'ACADEMIC',
                    'desc'     => 'Dedicated biology, chemistry, and physics laboratories fully fitted with modern glassware, fume hoods, and analytical instrumentation.',
                    'image'    => 'https://placehold.co/1000x800/0B2545/FAF8F4?text=Science+Laboratories+Apex+Crown',
                ],
                [
                    'title'    => 'Digital ICT & AI Suites',
                    'category' => 'TECHNOLOGY',
                    'desc'     => 'High-speed gigabit workstations, interactive smartboards, and robotics hardware kits.',
                    'image'    => 'https://placehold.co/600x400/0B2545/FAF8F4?text=Digital+ICT+Suites',
                ],
                [
                    'title'    => 'E-Library & Study Commons',
                    'category' => 'RESEARCH',
                    'desc'     => 'Over 15,000 bound volumes complemented by digital JSTOR and Britannica research terminals.',
                    'image'    => 'https://placehold.co/600x400/0B2545/FAF8F4?text=E-Library+Commons',
                ],
                [
                    'title'    => 'Sports Arena & Athletic Complex',
                    'category' => 'ATHLETICS',
                    'desc'     => 'Standard football pitch, outdoor basketball and tennis courts, and all-weather track.',
                    'image'    => 'https://placehold.co/600x400/0B2545/FAF8F4?text=Sports+Complex',
                ],
                [
                    'title'    => 'Residential Hostels & Dining',
                    'category' => 'RESIDENTIAL',
                    'desc'     => 'Air-conditioned boarding houses with 24/7 power backup, resident house parents, and dining hall.',
                    'image'    => 'https://placehold.co/600x400/0B2545/FAF8F4?text=Boarding+Hostels',
                ],
                [
                    'title'    => 'Acoustic Auditorium & Music Studio',
                    'category' => 'CULTURE',
                    'desc'     => '800-seat theater hall for assemblies, orchestral recitals, and graduation valedictions.',
                    'image'    => 'https://placehold.co/600x400/0B2545/FAF8F4?text=Auditorium+Studio',
                ],
            ]),

            // 06 Bulletin & Announcements
            'news_eyebrow'               => 'BULLETIN & CALENDAR',
            'news_heading'               => 'Recent Announcements & Key Dates',
            'news_articles'              => json_encode([
                [
                    'title'    => '2026/2027 First Batch National Entrance Examination & Scholarship Screening',
                    'category' => 'ADMISSIONS',
                    'date'     => 'Saturday, 18 April 2026',
                    'summary'  => 'Prospective candidates for JSS 1 and transfer classes will sit for Mathematics, English Language, and General Aptitude screening. Top 5 candidates receive merit tuition scholarships.',
                    'image'    => 'https://placehold.co/200x200/0B2545/FAF8F4?text=Exam+Entry',
                ],
                [
                    'title'    => '24th Annual Inter-House Athletics & March-Past Championship',
                    'category' => 'ATHLETICS',
                    'date'     => 'Friday, 27 March 2026',
                    'summary'  => 'Emerald, Ruby, Sapphire, and Topaz houses compete for track, field, and cultural march-past honors. Parents, guardians, and alumni are cordially invited to the Main Sports Arena.',
                    'image'    => 'https://placehold.co/200x200/0B2545/FAF8F4?text=Sports',
                ],
                [
                    'title'    => 'Annual Young Innovators STEM & Robotics Public Exhibition',
                    'category' => 'ACADEMICS',
                    'date'     => 'Wednesday, 13 May 2026',
                    'summary'  => 'Senior secondary scholars present functional solar micro-inverter designs, automated irrigation models, and AI chatbot demonstrators to university visiting professors.',
                    'image'    => 'https://placehold.co/200x200/0B2545/FAF8F4?text=STEM+Expo',
                ],
            ]),

            // 07 Perspectives / Testimonials
            'testimonials_eyebrow'       => 'VOICES OF PARENTS & ALUMNI',
            'testimonials_heading'       => 'Perspectives on an Apex Crown Education',
            'testimonials_intro'         => 'Reflections from parents, guardians, and alumni who have experienced the transformative impact of our community.',
            'testimonials_items'         => json_encode([
                [
                    'quote'  => 'Enrolling our children at Apex Crown College was the most consequential educational choice we made. Beyond their straight A1s in WAEC, the depth of their poise, moral conviction, and critical thinking is extraordinary.',
                    'author' => 'Chief & Dr. (Mrs.) Olumide Adeleke',
                    'role'   => 'Parents of 2024 Valedictorians',
                ],
                [
                    'quote'  => 'The discipline instilled during my boarding years at Apex Crown was decisive. When I entered Medical College at the University of Ibadan, I realized I had already developed the study stamina and leadership habits needed to thrive.',
                    'author' => 'Dr. Favour Chidera Eze',
                    'role'   => 'Medical Practitioner, UCH — Alumna (Class of 2018)',
                ],
                [
                    'quote'  => 'The tutorial masters possess an uncommon dedication. When my son required deeper coaching in Further Mathematics, his tutor organized after-hours clinics until he mastered every calculus theorem.',
                    'author' => 'Alhaji Mansur Danjuma',
                    'role'   => 'Parent of SSS 3 Scholar & PTA Executive',
                ],
            ]),

            // Admissions Call to Action
            'admissions_cta_eyebrow'        => 'ADMISSIONS 2026 / 2027',
            'admissions_cta_heading'        => 'Enroll Your Child in a Tradition of Distinction',
            'admissions_cta_subtitle'       => 'Applications are now being received for JSS 1 and limited transfer vacancies into JSS 2 and SSS 1. Day and Full-Boarding options available.',
            'admissions_cta_steps'          => json_encode([
                ['num' => '01', 'title' => 'Obtain Form', 'desc' => 'Complete the online application or purchase the dossier at the campus Registry.'],
                ['num' => '02', 'title' => 'Entrance Assessment', 'desc' => 'Candidate attends the written examination in Mathematics, English, and Aptitude.'],
                ['num' => '03', 'title' => 'Admission Offer', 'desc' => 'Successful applicants receive formal letters of admission within 5 working days.'],
                ['num' => '04', 'title' => 'Resumption & Induction', 'desc' => 'Scholars check in for the matriculation orientation and academic commencement.'],
            ]),
            'admissions_cta_primary_btn'    => 'Begin Online Application',
            'admissions_cta_primary_link'   => '#contact',
            'admissions_cta_secondary_btn'  => 'Download Prospectus (PDF)',
            'admissions_cta_secondary_link' => '#contact',

            // 08 Campus Visitation & Inquiry Form
            'contact_eyebrow'                => 'CAMPUS VISITATION & INQUIRY',
            'contact_heading'                => 'Schedule a Guided Tour or Speak with Admissions',
            'contact_intro'                  => 'Our Admissions Registry receives families for private consultations and campus walkthroughs by appointment.',
            'contact_address'                => 'Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos State, Nigeria',
            'contact_address_label'          => 'Campus Address',
            'contact_phone_label'            => 'Telephone',
            'contact_additional_phones'      => json_encode(['+234 812 345 6789']),
            'contact_email_label'            => 'Registry Email',
            'contact_additional_emails'      => json_encode(['info@apexcrown.edu.ng']),
            'contact_visiting_hours_label'   => 'Admissions Hours',
            'contact_visiting_hours'         => 'Monday – Friday: 8:00 AM – 4:00 PM | Saturday: 9:00 AM – 1:00 PM',
            'contact_form_title'             => 'Admissions Prospectus Inquiry',
            'contact_form_desc'              => 'Submit your inquiry and our admissions counsellor will respond within one business day.',
            'contact_form_name_label'        => 'Parent / Guardian Name *',
            'contact_form_phone_label'       => 'Telephone Number *',
            'contact_form_email_label'       => 'Email Address *',
            'contact_form_grade_label'       => 'Class Level of Interest *',
            'contact_form_grade_placeholder' => 'Select Candidate Grade',
            'contact_form_classes'           => json_encode([
                ['value' => 'jss1', 'label' => 'Junior Secondary 1 (Entry)'],
                ['value' => 'jss2', 'label' => 'Junior Secondary 2 (Transfer)'],
                ['value' => 'sss1', 'label' => 'Senior Secondary 1 (Sciences)'],
                ['value' => 'sss1-arts', 'label' => 'Senior Secondary 1 (Arts & Commercial)'],
            ]),
            'contact_form_notes_label'       => 'Prospective Scholar Notes / Questions',
            'contact_form_success_title'     => 'Inquiry Received',
            'contact_form_success_desc'      => 'Thank you for inquiring about Apex Crown College. The Admissions Office has received your details and will get in touch shortly.',

            // Footer & Colophon
            'footer_edition_label'           => 'Prospectus Edition',
            'footer_description'             => 'An accredited British-Nigerian secondary school dedicated to academic brilliance, moral character, and global leadership.',
            'footer_accreditations'          => 'Accredited by WAEC, NECO & Cambridge International.',
            'footer_col2_heading'            => 'Prospectus',
            'footer_col3_heading'            => 'Registry & Portals',
            'footer_col4_heading'            => 'Campus Registry',
            'footer_exam_link_label'         => 'Entrance Examination Dates',
            'footer_exam_link_url'           => '#admissions',
            'footer_tuition_link_label'      => 'Tuition & Scholarships',
            'footer_tuition_link_url'        => '#admissions',
            'footer_privacy_label'           => 'Privacy Policy',
            'footer_privacy_link'            => '#about',
            'footer_terms_label'             => 'Terms of Enrollment',
            'footer_terms_link'              => '#about',
            'footer_directions_label'        => 'Campus Directions',
            'footer_directions_link'         => '#contact',
        ];

        foreach ($contents as $key => $value) {
            FrontendContent::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        FrontendLibrary::flush(tenant('id'));
    }
}
