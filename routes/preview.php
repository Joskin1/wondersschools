<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Preview Routes (Ivy League / Oxbridge Prospectus Aesthetic)
|--------------------------------------------------------------------------
|
| Gated behind App::environment('local') so it can NEVER run in production.
| Provides realistic multi-tenant school prospectus data for preview.
|
*/

if (App::environment('local')) {
    Route::get('/preview/school-landing', function () {
        $school = [
            'name' => 'Apex Crown College',
            'motto' => 'Excellence, Character & Leadership',
            'location' => 'Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos',
            'established' => '2001',
            'contact_phone' => '+234 800 123 4567',
            'contact_email' => 'admissions@apexcrown.edu.ng',
            
            // 1. Hero
            'hero' => [
                'badge' => '2026 / 2027 Academic Session',
                'title' => 'Nurturing Intellectual Depth & Moral Leadership',
                'subtitle' => 'An accredited British-Nigerian secondary institution committed to scholastic rigor, scientific inquiry, and the formation of character.',
                'image' => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1920&q=80',
                'primary_cta_text' => 'Apply for Admission',
                'primary_cta_link' => '#admissions',
                'secondary_cta_text' => 'Explore Prospectus',
                'secondary_cta_link' => '#about',
            ],

            // 2. About / Head of School
            'about' => [
                'number' => '01',
                'eyebrow' => 'ABOUT THE COLLEGE',
                'heading' => 'A Tradition of Uncompromising Academic Standard',
                'paragraphs' => [
                    'Founded in 2001, Apex Crown College synthesizes the rigorous Nigerian National Basic & Senior Secondary Curriculum with Cambridge Assessment International standards. We believe secondary education is not simply an examination preparatory phase, but the crucible where character, intellectual curiosity, and self-governance are forged.',
                    'Our dedicated tutorial masters, modern science laboratories, and immersive pastoral mentorship ensure every student discovers their latent gifts and matures into an articulate, disciplined contributor to national and global society.',
                ],
                'principal_name' => 'Dr. (Mrs.) Adebisi Balogun',
                'principal_title' => 'B.Sc, M.Ed, Ph.D. — Principal & Head of School',
                'image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=800&q=80',
                'years_badge' => '25',
                'years_label' => 'Years of Academic Legacy in Lagos',
            ],

            // 3. Why Choose Us / Distinctions (Table of Contents / Editorial list)
            'features' => [
                'number' => '02',
                'eyebrow' => 'DISTINCTIVES',
                'heading' => 'The Pillars of an Apex Crown Education',
                'intro' => 'A deliberate blend of academic depth, moral discipline, and technological literacy structured to cultivate leaders.',
                'items' => [
                    [
                        'num' => '01',
                        'title' => 'Integrated Dual Curriculum',
                        'desc' => 'Simultaneous mastery of the Nigerian National Curriculum (WAEC & NECO) alongside British Cambridge Checkpoint and IGCSE examinations.'
                    ],
                    [
                        'num' => '02',
                        'title' => 'Individualized Tutorial Mentorship',
                        'desc' => 'Strict 1:12 faculty-to-student ratio ensuring individualized attention, customized academic interventions, and dedicated pastoral tutors.'
                    ],
                    [
                        'num' => '03',
                        'title' => 'Applied STEM & Computational Thinking',
                        'desc' => 'Purpose-built laboratories for physics, chemistry, biology, agricultural science, and dedicated robotics/coding suites.'
                    ],
                    [
                        'num' => '04',
                        'title' => 'Moral Formation & Character Discipline',
                        'desc' => 'Uncompromising emphasis on integrity, punctuality, self-respect, civic responsibility, and community service.'
                    ],
                    [
                        'num' => '05',
                        'title' => 'Comprehensive Boarding & Pastoral Care',
                        'desc' => 'Modern, secure air-conditioned dormitories with round-the-clock power, resident housemasters, and multi-course nutritional dining.'
                    ],
                    [
                        'num' => '06',
                        'title' => 'Oratory, Athletics & Cultural Life',
                        'desc' => 'Weekly parliamentary debating, orchestral music tuition, Model United Nations, and championship track and field athletics.'
                    ],
                ]
            ],

            // 4. Examination Track Record / Stats
            'stats' => [
                'number' => '03',
                'eyebrow' => 'EXAMINATION OUTCOMES',
                'heading' => 'Ten-Year Record of Scholastic Excellence',
                'items' => [
                    [
                        'value' => '100%',
                        'label' => 'WAEC Pass Rate',
                        'detail' => '5+ credits including English & Maths (10-year consecutive record)'
                    ],
                    [
                        'value' => '94.8%',
                        'label' => 'A1 - B3 Distinctions',
                        'detail' => 'Achieved across Mathematics, Further Math, Physics, and Chemistry'
                    ],
                    [
                        'value' => '312',
                        'label' => 'Average JAMB UTME',
                        'detail' => 'With the top candidate achieving 358 in the 2025 UTME session'
                    ],
                    [
                        'value' => '98%',
                        'label' => 'University Placement',
                        'detail' => 'Direct admissions into premier universities across Nigeria, the UK, US, and Canada'
                    ]
                ],
                'destinations' => [
                    'University of Ibadan',
                    'University of Lagos',
                    'Covenant University',
                    'Imperial College London',
                    'University of Toronto',
                    'University of Manchester'
                ]
            ],

            // 5. Academic Tracks / Curriculum Prospectus
            'academics' => [
                'number' => '04',
                'eyebrow' => 'CURRICULUM & PROGRAMMES',
                'heading' => 'Structured Pathways for Secondary Scholars',
                'intro' => 'A comprehensive curriculum designed to build foundational mastery in the junior years and deep specialization in the senior years.',
                'tracks' => [
                    [
                        'code' => 'JSS 1 — JSS 3',
                        'name' => 'Junior Secondary School',
                        'ages' => 'Ages 10 — 13 Years',
                        'certs' => 'BECE & Cambridge Checkpoint',
                        'desc' => 'Focuses on foundational intellectual development: computational thinking, language mastery, basic science, and cultural appreciation.',
                        'subjects' => ['General Mathematics', 'English & Literature', 'Basic Science & Tech', 'Coding Basics', 'French & Languages', 'Business Studies']
                    ],
                    [
                        'code' => 'SSS 1 — SSS 3',
                        'name' => 'Senior Sciences & Technology',
                        'ages' => 'Ages 13 — 17 Years',
                        'certs' => 'WAEC, NECO, IGCSE & JAMB',
                        'desc' => 'Rigorous scientific inquiry for aspiring medical doctors, software architects, agricultural biotechnologists, and structural engineers.',
                        'subjects' => ['Further Mathematics', 'Physics & Chemistry', 'Biology & Agric', 'Technical Drawing', 'Data Processing', 'Weekly Practical Labs']
                    ],
                    [
                        'code' => 'SSS 1 — SSS 3',
                        'name' => 'Senior Arts & Commercial Studies',
                        'ages' => 'Ages 13 — 17 Years',
                        'certs' => 'WAEC, NECO, IGCSE & JAMB',
                        'desc' => 'For future jurists, economists, chartered accountants, diplomats, and business leaders with intensive essay and analysis training.',
                        'subjects' => ['Literature in English', 'Government & History', 'Financial Accounting', 'Economics & Commerce', 'Visual Arts & Music', 'Debating Society']
                    ]
                ]
            ],

            // 6. Facilities Gallery (Uneven Grid: 2x2 first, 1x1 rest)
            'facilities' => [
                'number' => '05',
                'eyebrow' => 'CAMPUS INFRASTRUCTURE',
                'heading' => 'Purpose-Built Learning & Living Environments',
                'items' => [
                    [
                        'title' => 'Advanced Science Laboratories',
                        'desc' => 'Dedicated biology, chemistry, and physics laboratories fully fitted with modern glassware, fume hoods, and analytical instrumentation.',
                        'category' => 'ACADEMIC',
                        'image' => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?auto=format&fit=crop&w=1200&q=80',
                        'span' => 'col-span-12 md:col-span-8 md:row-span-2',
                    ],
                    [
                        'title' => 'Digital ICT & AI Suites',
                        'desc' => 'High-speed gigabit workstations, interactive smartboards, and robotics hardware kits.',
                        'category' => 'TECHNOLOGY',
                        'image' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=800&q=80',
                        'span' => 'col-span-12 md:col-span-4',
                    ],
                    [
                        'title' => 'E-Library & Study Commons',
                        'desc' => 'Over 15,000 bound volumes complemented by digital JSTOR and Britannica research terminals.',
                        'category' => 'RESEARCH',
                        'image' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=800&q=80',
                        'span' => 'col-span-12 md:col-span-4',
                    ],
                    [
                        'title' => 'Sports Arena & Athletic Complex',
                        'desc' => 'Standard football pitch, outdoor basketball and tennis courts, and all-weather track.',
                        'category' => 'ATHLETICS',
                        'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80',
                        'span' => 'col-span-12 md:col-span-4',
                    ],
                    [
                        'title' => 'Residential Hostels & Dining',
                        'desc' => 'Air-conditioned boarding houses with 24/7 power backup, resident house parents, and dining hall.',
                        'category' => 'RESIDENTIAL',
                        'image' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=800&q=80',
                        'span' => 'col-span-12 md:col-span-4',
                    ],
                    [
                        'title' => 'Acoustic Auditorium & Music Studio',
                        'desc' => '800-seat theater hall for assemblies, orchestral recitals, and graduation valedictions.',
                        'category' => 'CULTURE',
                        'image' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=800&q=80',
                        'span' => 'col-span-12 md:col-span-4',
                    ],
                ]
            ],

            // 7. News & Events (Horizontal Rows with Small Square Thumbnails)
            'news' => [
                'number' => '06',
                'eyebrow' => 'BULLETIN & CALENDAR',
                'heading' => 'Recent Announcements & Key Dates',
                'articles' => [
                    [
                        'title' => '2026/2027 First Batch National Entrance Examination & Scholarship Screening',
                        'category' => 'ADMISSIONS',
                        'date' => 'Saturday, 18 April 2026',
                        'summary' => 'Prospective candidates for JSS 1 and transfer classes will sit for Mathematics, English Language, and General Aptitude screening. Top 5 candidates receive merit tuition scholarships.',
                        'image' => 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=400&q=80'
                    ],
                    [
                        'title' => '24th Annual Inter-House Athletics & March-Past Championship',
                        'category' => 'ATHLETICS',
                        'date' => 'Friday, 27 March 2026',
                        'summary' => 'Emerald, Ruby, Sapphire, and Topaz houses compete for track, field, and cultural march-past honors. Parents, guardians, and alumni are cordially invited to the Main Sports Arena.',
                        'image' => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=400&q=80'
                    ],
                    [
                        'title' => 'Annual Young Innovators STEM & Robotics Public Exhibition',
                        'category' => 'ACADEMICS',
                        'date' => 'Wednesday, 13 May 2026',
                        'summary' => 'Senior secondary scholars present functional solar micro-inverter designs, automated irrigation models, and AI chatbot demonstrators to university visiting professors.',
                        'image' => 'https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?auto=format&fit=crop&w=400&q=80'
                    ]
                ]
            ],

            // 8. Testimonials (Editorial Quote Layout)
            'testimonials' => [
                'number' => '07',
                'eyebrow' => 'VOICES OF PARENTS & ALUMNI',
                'heading' => 'Perspectives on an Apex Crown Education',
                'items' => [
                    [
                        'quote' => 'Enrolling our children at Apex Crown College was the most consequential educational choice we made. Beyond their straight A1s in WAEC, the depth of their poise, moral conviction, and critical thinking is extraordinary.',
                        'author' => 'Chief & Dr. (Mrs.) Olumide Adeleke',
                        'role' => 'Parents of 2024 Valedictorians'
                    ],
                    [
                        'quote' => 'The discipline instilled during my boarding years at Apex Crown was decisive. When I entered Medical College at the University of Ibadan, I realized I had already developed the study stamina and leadership habits needed to thrive.',
                        'author' => 'Dr. Favour Chidera Eze',
                        'role' => 'Medical Practitioner, UCH — Alumna (Class of 2018)'
                    ],
                    [
                        'quote' => 'The tutorial masters possess an uncommon dedication. When my son required deeper coaching in Further Mathematics, his tutor organized after-hours clinics until he mastered every calculus theorem.',
                        'author' => 'Alhaji Mansur Danjuma',
                        'role' => 'Parent of SSS 3 Scholar & PTA Executive'
                    ]
                ]
            ],

            // 9. Admissions CTA (Centred for Emphasis, Full-Bleed Ink Band)
            'admissions_cta' => [
                'eyebrow' => 'ADMISSIONS 2026 / 2027',
                'heading' => 'Enroll Your Child in a Tradition of Distinction',
                'subtitle' => 'Applications are now being received for JSS 1 and limited transfer vacancies into JSS 2 and SSS 1. Day and Full-Boarding options available.',
                'steps' => [
                    ['num' => '01', 'title' => 'Obtain Form', 'desc' => 'Complete the online application or purchase the dossier at the campus Registry.'],
                    ['num' => '02', 'title' => 'Entrance Assessment', 'desc' => 'Candidate attends the written examination in Mathematics, English, and Aptitude.'],
                    ['num' => '03', 'title' => 'Admission Offer', 'desc' => 'Successful applicants receive formal letters of admission within 5 working days.'],
                    ['num' => '04', 'title' => 'Resumption & Induction', 'desc' => 'Scholars check in for the matriculation orientation and academic commencement.'],
                ],
                'primary_btn' => 'Begin Online Application',
                'secondary_btn' => 'Download Prospectus (PDF)'
            ],

            // 10. Contact & Visiting
            'contact' => [
                'number' => '08',
                'eyebrow' => 'CAMPUS VISITATION & INQUIRY',
                'heading' => 'Schedule a Guided Tour or Speak with Admissions',
                'address' => 'Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos State, Nigeria',
                'phones' => ['+234 800 123 4567', '+234 812 345 6789'],
                'emails' => ['admissions@apexcrown.edu.ng', 'info@apexcrown.edu.ng'],
                'visiting_hours' => 'Monday – Friday: 8:00 AM – 4:00 PM | Saturday: 9:00 AM – 1:00 PM',
            ],

            // 11. Footer
            'footer' => [
                'description' => 'Apex Crown College is an accredited co-educational secondary school in Lagos, Nigeria, dedicated to academic brilliance, moral character, and global leadership.',
                'accreditations' => 'Accredited by WAEC, NECO, Cambridge International & British Council.',
                'copyright' => 'Apex Crown College. All Rights Reserved.'
            ]
        ];

        return view('preview.school-landing', compact('school'));
    })->name('preview.school-landing');
}
