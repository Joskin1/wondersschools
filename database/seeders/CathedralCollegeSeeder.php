<?php

namespace Database\Seeders;

use App\Models\FrontendContent;
use App\Models\Setting;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Stancl\Tenancy\Database\Models\Domain;

class CathedralCollegeSeeder extends Seeder
{
    /**
     * Seed Cathedral Church of Our Saviour College content and settings.
     */
    public function run(): void
    {
        $schoolName = 'Cathedral Church of Our Saviour College';
        $shortName  = 'CCOSC';
        $motto      = 'Knowledge, Character and the Fear of God';
        $phone      = '+234 803 300 4567';
        $email      = 'admissions@cathedralcollege.edu.ng';
        $address    = 'Cathedral Grounds, Ejinrin Road, Ijebu-Ode, Ogun State, Nigeria';
        $established= '1998';

        // ── 1. Landlord Tenant & Domain Configuration ────────────────────────
        if (\Illuminate\Support\Facades\Schema::hasTable('tenants')) {
            $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : 'chizylite';
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $tenant->update([
                    'name'          => $schoolName,
                    'primary_color' => '#3D2606',
                    'status'        => 'active',
                ]);
            }
        }

        // ── 2. Tenant Settings & Content ─────────────────────────────────────
        if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
            return;
        }

        $settings = [
            'school_name'             => $schoolName,
            'school_short_name'       => $shortName,
            'school_motto'            => $motto,
            'school_established'      => $established,
            'school_phone'            => $phone,
            'school_email'            => $email,
            'school_website'          => 'https://cathedralcollege.edu.ng',
            'school_address'          => $address,
            'school_city'             => 'Ijebu-Ode',
            'school_logo'             => null,
            'fee_schedule_link'       => '#admissions',
            'primary_color'           => '#3D2606', // Deep Majestic Cathedral Gold / Antique Bronze
            'secondary_color'         => '#1A1104', // Dark Warm Gold Neutral
            'accent_color'            => '#D4AF37', // Radiant Metallic Gold
            'layout_style'            => 'editorial',
            'student_portal_url'      => '/student/login',
            'staff_portal_url'        => '/teacher/login',
            'admin_portal_url'        => '/admin/login',
            'footer_social_facebook'  => 'https://facebook.com',
            'footer_social_instagram' => 'https://instagram.com',
            'footer_social_linkedin'  => null,
            'footer_social_x'         => null,
            'seo_title'               => 'Cathedral Church of Our Saviour College | Nursery, Primary & Secondary College in Ijebu-Ode',
            'seo_description'         => 'Cathedral Church of Our Saviour College delivers premier Christian education from Early Years & Primary through Junior & Senior Secondary in Ijebu-Ode, Ogun State. Anchored in academic excellence, godliness, and leadership.',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // ── 3. Frontend Content Sections ─────────────────────────────────────
        $contents = [
            // Topbar & Navigation
            'topbar_badge'               => '2026/2027 Admissions Open',
            'topbar_text'                => 'Enrollment into Crèche, Nursery, Primary, JSS 1 and SSS 1 now ongoing. Entrance forms available.',
            'nav_about_label'            => 'Heritage',
            'nav_features_label'         => 'Distinctives',
            'nav_academics_label'        => 'Our Sections',
            'nav_stats_label'            => 'Outcomes',
            'nav_facilities_label'       => 'Campus',
            'nav_news_label'             => 'Bulletin',
            'nav_contact_label'          => 'Contact',
            'nav_portals_label'          => 'Portals',
            'portal_student_label'       => 'Student & Parent Portal',
            'portal_staff_label'         => 'Staff & Teacher Portal',
            'portal_admin_label'         => 'Admin Portal',
            'portal_student_label_short' => 'Student',
            'portal_staff_label_short'   => 'Faculty',
            'portal_admin_label_short'   => 'Admin',
            'header_cta_text'            => 'Admissions',
            'header_cta_link'            => '#admissions',

            // Hero Section
            'hero_badge'                 => 'Cathedral Church of Our Saviour • Ijebu-Ode',
            'hero_title'                 => 'Nurturing Intellectual Brilliance, Christian Character & Future Leaders',
            'hero_subtitle'              => 'A premier Christian day academy providing a continuous, values-driven academic journey across Nursery, Primary, and Junior & Senior Secondary College in the heart of Ijebu-Ode.',
            'hero_image'                 => '/images/cathedral/graduating_class.jpg',
            'hero_image_alt'             => 'Cathedral Church of Our Saviour College Scholars',
            'hero_slider_images'         => json_encode([
                '/images/cathedral/graduating_class.jpg',
                '/images/cathedral/scholars_uniform.jpg',
            ]),
            'hero_primary_cta_text'      => 'Apply for Admission',
            'hero_primary_cta_link'      => '#admissions',
            'hero_secondary_cta_text'    => 'Explore Our Sections',
            'hero_secondary_cta_link'    => '#academics',
            'hero_scroll_label'          => 'Scroll to explore our prospectus',

            // 01 About Section
            'about_eyebrow'              => 'OUR HERITAGE & FAITH',
            'about_heading'              => 'A Legacy of Christian Godliness & Academic Rigor',
            'about_image'                => '/images/cathedral/scholars_uniform.jpg',
            'about_image_alt'            => 'Cathedral College Scholars in Uniform',
            'about_years_badge'          => '25+',
            'about_years_label'          => 'Years of Educational Heritage in Ijebu-Ode',
            'about_body'                 => '<p>Founded under the divine inspiration and visionary leadership of the <strong>Cathedral Church of Our Saviour (Anglican Communion, Diocese of Ijebu)</strong>, our institution stands as a citadel of learning dedicated to shaping young minds for impactful living.</p><p>We provide a seamless, supportive day-school environment that spans <strong>Early Childhood & Nursery, Basic Primary Education, and Junior & Senior Secondary College</strong>. Our educational philosophy fuses rigorous Nigerian and international academic standards with unwavering Christian moral discipline, ensuring our scholars excel spiritually, intellectually, and socially.</p>',
            'about_principal_name'       => 'Venerable (Dr.) E. O. Adebayo',
            'about_principal_title'      => 'B.Ed, M.Sc, Ph.D. — Principal & Head of School',

            // 02 Distinctives Section
            'features_eyebrow'           => 'DISTINCTIVES',
            'features_heading'           => 'Why Discerning Parents Choose Cathedral College',
            'features_intro'             => 'A purposeful blend of Christian values, sound scholastic standards, modern digital literacy, and dedicated pastoral care.',
            'features_cta_text'          => 'Review Our Curriculum',
            'features_cta_link'          => '#academics',
            'features_items'             => json_encode([
                [
                    'title' => 'Christ-Centered Moral Foundation',
                    'desc'  => 'Daily chapel devotions, biblical mentorship, and character formation that ground students in integrity, discipline, and reverence for God.',
                ],
                [
                    'title' => 'Complete Nursery-to-College Pathway',
                    'desc'  => 'A cohesive 3-tier structure (Nursery, Primary, Secondary) providing smooth academic continuity without changing schools or disrupting learning.',
                ],
                [
                    'title' => 'Science, STEM & Modern Digital ICT',
                    'desc'  => 'Equipped physics, chemistry, biology, and computer laboratories with hands-on practicals, coding exposure, and digital learning tools.',
                ],
                [
                    'title' => 'Proven WAEC, NECO & BECE Distinction Record',
                    'desc'  => 'Consistent track record of 100% pass rates and distinctions in national and regional examinations, backed by dedicated after-school tutorial clinics.',
                ],
                [
                    'title' => 'Holistic Co-Curricular & Sports Culture',
                    'desc'  => 'Vibrant choir and music ensembles, literary and debating societies, JET club, Red Cross, scouting, athletics, and inter-house sports competitions.',
                ],
                [
                    'title' => 'Secure & Nurturing Day Campus',
                    'desc'  => 'A serene, secure learning environment on Ejinrin Road with close parent-teacher communication, modern health bay, and disciplined supervision.',
                ],
            ]),

            // 03 Academic Tracks (Nursery, Primary, Junior & Senior Secondary)
            'academics_eyebrow'          => 'OUR ACADEMIC SECTIONS',
            'academics_heading'          => 'Seamless Learning from Early Childhood to Senior College',
            'academics_intro'            => 'Structured across three distinct developmental tiers, designed to meet each child at their stage of growth and prepare them for lifelong success.',
            'academics_tracks'           => json_encode([
                [
                    'code'     => 'CRÈCHE • NURSERY • KG',
                    'name'     => 'Early Childhood & Nursery Section',
                    'ages'     => 'Ages 18 Months — 5 Years',
                    'certs'    => 'Early Years Foundation & Phonics',
                    'desc'     => 'A warm, stimulating environment focused on early phonics (Jolly Phonics), sensory discovery, numeracy, creative arts, and foundational Christian morals through play and guided discovery.',
                    'subjects' => ['Jolly Phonics & Reading', 'Early Numeracy & Logic', 'Sensory Play & Discovery', 'Rhymes & Creative Arts', 'Social Habits & Etiquette', 'Christian Stories & Music'],
                ],
                [
                    'code'     => 'BASIC 1 — BASIC 6',
                    'name'     => 'Basic Primary Section',
                    'ages'     => 'Ages 5 — 11 Years',
                    'certs'    => 'Primary School Leaving Certificate & Common Entrance',
                    'desc'     => 'A balanced curriculum integrating Nigerian Basic Education with British primary frameworks. Emphasizes mathematical mastery, English eloquence, science, coding, and civic leadership.',
                    'subjects' => ['Mathematics & Quantitative', 'English & Verbal Reasoning', 'Basic Science & Tech', 'Computer Studies & Coding', 'French & Civic Education', 'Agricultural Science'],
                ],
                [
                    'code'     => 'JSS 1 — JSS 3',
                    'name'     => 'Junior Secondary College',
                    'ages'     => 'Ages 11 — 14 Years',
                    'certs'    => 'Basic Education Certificate Examination (BECE)',
                    'desc'     => 'Broad-based academic curriculum developing analytical rigor and technological skills. Equips students for excellent BECE outcomes and prepares them for specialized senior secondary streams.',
                    'subjects' => ['Basic Sciences & ICT', 'Basic Technology', 'Business Studies & French', 'Creative & Cultural Arts', 'National Values & CRS', 'Mathematics & English'],
                ],
                [
                    'code'     => 'SSS 1 — SSS 3',
                    'name'     => 'Senior Secondary College',
                    'ages'     => 'Ages 14 — 17 Years',
                    'certs'    => 'WAEC (WASSCE), NECO (SSCE) & UTME (JAMB)',
                    'desc'     => 'Intensive preparatory college with specialized streams in Pure Science, Commercial & Management, and Arts & Humanities, backed by weekly laboratory practicals and JAMB CBT clinics.',
                    'subjects' => ['Physics, Chemistry & Biology', 'Further Maths & Tech Drawing', 'Financial Accounting & Comm.', 'Literature & Government', 'Data Processing & ICT', 'Economics & Civic Educ.'],
                ],
            ]),

            // 04 Statistics & Outcomes
            'stats_eyebrow'              => 'EXAMINATION OUTCOMES',
            'stats_heading'              => 'A 25-Year Tradition of Proven Scholastic Distinction',
            'stats_items'                => json_encode([
                [
                    'value'  => '100%',
                    'label'  => 'WAEC & NECO Pass Rate',
                    'detail' => '5+ credits including English & Maths across all graduating sets',
                ],
                [
                    'value'  => '95.4%',
                    'label'  => 'A1 - B3 Distinctions',
                    'detail' => 'Achieved in core Sciences, Mathematics, and Commercial subjects',
                ],
                [
                    'value'  => '1:15',
                    'label'  => 'Faculty Mentorship Ratio',
                    'detail' => 'Individualized tutorial attention and close academic monitoring',
                ],
                [
                    'value'  => '3 Tiers',
                    'label'  => 'Integrated Campus',
                    'detail' => 'Nursery, Primary, and College sections on one secure campus',
                ],
            ]),
            'stats_destinations_label'   => 'Representative Higher Institution Placements:',
            'stats_destinations'         => json_encode([
                'University of Ibadan (UI)',
                'University of Lagos (UNILAG)',
                'Olabisi Onabanjo University (OOU)',
                'Federal Univ. of Agriculture, Abeokuta (FUNAAB)',
                'Bowen & Babcock Universities',
                'Covenant University',
            ]),

            // 05 Facilities Section
            'facilities_eyebrow'         => 'CAMPUS INFRASTRUCTURE',
            'facilities_heading'         => 'Purpose-Built Environments for Learning & Spiritual Formation',
            'facilities_items'           => json_encode([
                [
                    'title'    => 'Modern Science Laboratories',
                    'category' => 'ACADEMIC',
                    'desc'     => 'Dedicated physics, chemistry, and biology laboratories fully fitted with apparatus, reagents, and safety gear for empirical scientific investigation.',
                    'image'    => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=1200&q=80',
                ],
                [
                    'title'    => 'Digital ICT Suite & CBT Center',
                    'category' => 'TECHNOLOGY',
                    'desc'     => 'High-speed computer workstations equipped for coding, digital literacy, and computer-based test (CBT) examinations.',
                    'image'    => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800&q=80',
                ],
                [
                    'title'    => 'Cathedral Chapel & Assembly Sanctuary',
                    'category' => 'SPIRITUAL',
                    'desc'     => 'A serene sanctuary for morning devotions, musical presentations, weekly chapel worship, and inspiring leadership assemblies.',
                    'image'    => 'https://images.unsplash.com/photo-1548625361-195feeed9a02?auto=format&fit=crop&w=800&q=80',
                ],
                [
                    'title'    => 'Comprehensive College Library',
                    'category' => 'RESEARCH',
                    'desc'     => 'Extensive collection of curriculum textbooks, classic literature, encyclopedia volumes, and quiet study alcoves.',
                    'image'    => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=800&q=80',
                ],
                [
                    'title'    => 'Early Childhood Learning Hub',
                    'category' => 'EARLY YEARS',
                    'desc'     => 'A colorful, child-safe nursery wing equipped with Montessori-inspired learning aids, interactive toys, and play areas.',
                    'image'    => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=800&q=80',
                ],
                [
                    'title'    => 'Sports Arena & Athletics Complex',
                    'category' => 'ATHLETICS',
                    'desc'     => 'Dedicated pitch and court facilities for football, volleyball, table tennis, and track athletics fostering teamwork and fitness.',
                    'image'    => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=800&q=80',
                ],
            ]),

            // 06 News & Bulletin
            'news_eyebrow'               => 'COLLEGE BULLETIN',
            'news_heading'               => 'Latest Announcements & Key Dates',
            'news_articles'              => json_encode([
                [
                    'title'    => '2026/2027 Academic Session Admissions & Entrance Screening',
                    'category' => 'ADMISSIONS',
                    'date'     => 'Ongoing Registration',
                    'summary'  => 'Applications are open for Nursery, Primary, JSS 1, and SSS 1 entry. Entrance screening dates and application forms are available online and at the Registry.',
                    'image'    => 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=400&q=80',
                ],
                [
                    'title'    => 'Cathedral College Celebrates 100% Distinctions in WAEC & BECE',
                    'category' => 'ACADEMICS',
                    'date'     => 'Academic Release',
                    'summary'  => 'Our graduating scholars recorded stellar performances across all science, commercial, and arts subjects in the recent WAEC and BECE examinations.',
                    'image'    => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=400&q=80',
                ],
                [
                    'title'    => 'Annual Cathedral Thanksgiving Service & Inter-House Sports Festival',
                    'category' => 'EVENTS',
                    'date'     => 'Upcoming Calendar',
                    'summary'  => 'Join us for a joyous celebration of divine grace, musical recitals, and competitive athletic display with our vibrant school community.',
                    'image'    => 'https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?auto=format&fit=crop&w=400&q=80',
                ],
            ]),

            // 07 Testimonials
            'testimonials_eyebrow'       => 'VOICES OF OUR COMMUNITY',
            'testimonials_heading'       => 'Perspectives from Our Parents & Alumni',
            'testimonials_intro'         => 'Reflections from families who have experienced the moral and academic impact of Cathedral Church of Our Saviour College.',
            'testimonials_items'         => json_encode([
                [
                    'quote'  => 'Enrolling my children at Cathedral Church of Our Saviour College was the best decision we made. Beyond their excellent WAEC results, the moral discipline, Christian integrity, and confidence instilled in them are truly exceptional.',
                    'author' => 'Chief (Mrs.) T. Ogundipe',
                    'role'   => 'Parent of SSS 3 Graduate',
                ],
                [
                    'quote'  => 'The transition from the Primary school into the College section was seamless. The teachers are deeply invested in each student, and the STEM laboratories gave my son the solid foundation he needed for his university engineering studies.',
                    'author' => 'Mr. O. Adesanya',
                    'role'   => 'PTA Executive Member & Parent',
                ],
                [
                    'quote'  => 'The spiritual grounding, debate culture, and academic rigor at Cathedral College prepared me to compete and excel at the highest university level. I am proud to be an alumnus of this citadel of excellence.',
                    'author' => 'Dr. Babatunde Oshinowo',
                    'role'   => 'Medical Practitioner & Alumnus (Class of 2014)',
                ],
            ]),

            // 08 Admissions CTA & Process
            'admissions_cta_eyebrow'     => 'ADMISSIONS 2026 / 2027',
            'admissions_cta_heading'     => 'Begin Your Child’s Journey of Excellence & Godliness',
            'admissions_cta_subtitle'    => 'Admissions are now open for Early Childhood/Nursery, Basic Primary, JSS 1, SSS 1, and intermediate transfer classes. Day school options with secure bus routes across Ijebu-Ode.',
            'admissions_cta_primary_btn' => 'Apply for Admission',
            'admissions_cta_primary_link'=> '#contact',
            'admissions_cta_secondary_btn'=>'Download Prospectus',
            'admissions_cta_secondary_link'=>'#contact',
            'admissions_cta_steps'       => json_encode([
                [
                    'num'   => '01',
                    'title' => 'Obtain Application Form',
                    'desc'  => 'Pick up an admission package from the Cathedral College Registry on Ejinrin Road, Ijebu-Ode, or complete the online inquiry form below.',
                ],
                [
                    'num'   => '02',
                    'title' => 'Entrance Assessment',
                    'desc'  => 'Prospective pupils and students sit for a standard entrance and placement assessment testing core competencies.',
                ],
                [
                    'num'   => '03',
                    'title' => 'Interview & Offer Letter',
                    'desc'  => 'Successful candidates attend a brief interview with their parents, followed by formal issuance of the Admission Letter.',
                ],
                [
                    'num'   => '04',
                    'title' => 'Resumption & Orientation',
                    'desc'  => 'Scholars complete enrollment and participate in our welcoming induction and academic commencement.',
                ],
            ]),

            // 09 Contact Section
            'contact_eyebrow'            => 'CAMPUS VISITATION & INQUIRY',
            'contact_heading'            => 'Visit Our Campus or Speak with Our Admissions Desk',
            'contact_intro'              => 'Our Admissions Registry welcomes prospective parents for consultations and guided walkthroughs of our Nursery, Primary, and College facilities.',
            'contact_address_label'      => 'Campus Location',
            'contact_address'            => $address,
            'contact_phone_label'        => 'Telephone Lines',
            'contact_email_label'        => 'Official Email',
            'contact_visiting_hours_label'=>'Admissions Desk Hours',
            'contact_visiting_hours'     => 'Monday – Friday: 8:00 AM – 4:00 PM | Saturday: 9:00 AM – 1:00 PM',
            'contact_additional_phones'  => json_encode([
                '+234 802 223 8899',
                '+234 805 554 1122',
            ]),
            'contact_additional_emails'  => json_encode([
                'principal@cathedralcollege.edu.ng',
                'info@cathedralcollege.edu.ng',
            ]),
            'contact_form_title'         => 'Admissions Prospectus & Enrollment Inquiry',
            'contact_form_desc'          => 'Submit your inquiry and our admissions counsellor will respond within one business day.',
            'contact_form_name_label'    => 'Parent / Guardian Full Name *',
            'contact_form_phone_label'   => 'Active Telephone Number *',
            'contact_form_email_label'   => 'Email Address *',
            'contact_form_grade_label'   => 'Section / Grade Level of Interest *',
            'contact_form_grade_placeholder' => 'Select Candidate Grade Level',
            'contact_form_classes'       => json_encode([
                ['value' => 'nursery',   'label' => 'Crèche, Kindergarten & Nursery'],
                ['value' => 'primary',   'label' => 'Basic Primary (Grades 1 – 6)'],
                ['value' => 'jss1',      'label' => 'Junior Secondary 1 (JSS 1 Entry)'],
                ['value' => 'jss2_3',    'label' => 'Junior Secondary 2 – 3 (Transfer)'],
                ['value' => 'sss1_sci',  'label' => 'Senior Secondary 1 (Pure Sciences)'],
                ['value' => 'sss1_comm', 'label' => 'Senior Secondary 1 (Commercial & Business)'],
                ['value' => 'sss1_arts', 'label' => 'Senior Secondary 1 (Arts & Humanities)'],
                ['value' => 'sss2_trans','label' => 'Senior Secondary 2 (Transfer Candidate)'],
            ]),
            'contact_form_notes_label'   => 'Candidate Notes / Specific Inquiries',
            'contact_form_success_title' => 'Inquiry Successfully Received',
            'contact_form_success_desc'  => 'Thank you for reaching out to Cathedral Church of Our Saviour College. Our Admissions Desk will contact you shortly.',

            // 10 Footer
            'footer_description'         => 'Cathedral Church of Our Saviour College is an accredited Christian day institution in Ijebu-Ode, Ogun State, providing exceptional education across Nursery, Primary, and College sections.',
            'footer_accreditations'      => 'Approved by Ogun State Ministry of Education • Accredited by WAEC & NECO.',
            'footer_edition_label'       => '2026/2027 Official Prospectus',
            'footer_col2_heading'        => 'Our Sections',
            'footer_col3_heading'        => 'Portals & Registry',
            'footer_col4_heading'        => 'Cathedral Campus',
        ];

        foreach ($contents as $key => $value) {
            FrontendContent::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Flush cache so changes take effect immediately
        \Illuminate\Support\Facades\Cache::flush();
    }
}
