# Wonders Website Rebuild — Content Inventory & Audit (Phase 1A)

This document establishes the definitive content inventory for the public school website redesign. Every piece of admin-editable content, system setting, navigation label, portal moniker, form label, and dynamic list is mapped to its database storage key, Filament form field type, validation rules, default preview values, and 390px mobile viewport behaviors.

---

## 1. Content Inventory Table

| Row # | View / Location | Field / Concept Name | Database Key / Column | Storage Table | Filament Field Type | Default Value (From Preview) | Validation / Max Length | 390px Mobile Viewport & Design Note |
|---|---|---|---|---|---|---|---|---|
| **Global School Settings** |
| 1 | Layout Head & Brand | School Official Name | `school_name` | `settings` | `TextInput` | `Apex Crown College` | `required\|string\|max:100` | Header brand lockup, page title tag, and footer copyright. |
| 2 | Layout Nav & Crest | School Short Name / Monogram | `school_short_name` | `settings` | `TextInput` | `AC` | `nullable\|string\|max:5` | Rendered in square monogram crest fallback when no logo image is uploaded. |
| 3 | Layout Head / Meta | School Motto | `school_motto` | `settings` | `TextInput` | `Excellence, Character & Leadership` | `nullable\|string\|max:150` | Displayed in page metadata and meta description tag. |
| 4 | Layout Nav & Crest | Established Year | `school_established` | `settings` | `TextInput` | `2001` | `nullable\|string\|max:10` | "Lagos • Est. 2001" in header crest lockup. |
| 5 | Layout Header & Footer | Primary Contact Phone | `school_phone` | `settings` | `TextInput` | `+234 800 123 4567` | `required\|string\|max:30` | Top announcement bar, mobile drawer, and footer registry. |
| 6 | Layout Header & Footer | Primary Registry Email | `school_email` | `settings` | `TextInput` | `admissions@apexcrown.edu.ng` | `required\|email\|max:100` | Top announcement bar and footer registry. |
| 7 | Layout Head & Nav | School Crest / Logo | `school_logo` | `settings` | `FileUpload` | `null` | `nullable\|image\|max:2048` | Rendered in header and footer with alt text fallback. |
| 8 | Layout CSS Tokens | Primary Brand Color (Ink) | `primary_color` | `settings` | `ColorPicker` | `#0B2545` | `required\|hex_color` | Maps to `--ink`. Contrasts with `--paper` and `--ink-contrast`. |
| 9 | Layout CSS Tokens | Secondary Brand Color (Support) | `secondary_color` | `settings` | `ColorPicker` | `#1E293B` | `required\|hex_color` | Maps to `--support` for muted UI elements. |
| 10 | Layout CSS Tokens | Accent Brand Color (Gold) | `accent_color` | `settings` | `ColorPicker` | `#C8A951` | `required\|hex_color` | Maps to `--accent` for CTAs, eyebrows, and stat numerals. |
| 11 | Layout Nav & Portals | Student Portal URL | `student_portal_url` | `settings` | `TextInput` | `/student/login` | `nullable\|string\|max:255` | Portals dropdown and mobile drawer portal grid. |
| 12 | Layout Nav & Portals | Faculty Portal URL | `staff_portal_url` | `settings` | `TextInput` | `/teacher/login` | `nullable\|string\|max:255` | Portals dropdown and mobile drawer portal grid. |
| 13 | Layout Nav & Portals | Admin Portal URL | `admin_portal_url` | `settings` | `TextInput` | `/admin/login` | `nullable\|string\|max:255` | Portals dropdown and mobile drawer portal grid. |
| 14 | Footer Social | Facebook Page URL | `footer_social_facebook` | `settings` | `TextInput` | `null` | `nullable\|url\|max:255` | Rendered in footer social icon links when present. |
| 15 | Footer Social | Instagram Profile URL | `footer_social_instagram` | `settings` | `TextInput` | `null` | `nullable\|url\|max:255` | Rendered in footer social icon links when present. |
| 16 | Footer Social | LinkedIn Page URL | `footer_social_linkedin` | `settings` | `TextInput` | `null` | `nullable\|url\|max:255` | Rendered in footer social icon links when present. |
| 17 | Footer Social | X (Twitter) Profile URL | `footer_social_x` | `settings` | `TextInput` | `null` | `nullable\|url\|max:255` | Rendered in footer social icon links when present. |
| **Top Announcement Bar, Navigation & Portals** |
| 18 | `layouts/app.blade.php` | Top Bar Academic Session Badge | `topbar_badge` | `frontend_contents` | `TextInput` | `Admissions 2026/2027` | `nullable\|string\|max:50` | Rendered with glowing accent bullet in top announcement strip. |
| 19 | `layouts/app.blade.php` | Top Bar Announcement Text | `topbar_text` | `frontend_contents` | `TextInput` | `Entrance examination and transfer enrollment now open.` | `nullable\|string\|max:150` | Hidden on mobile header (`sm:inline`), surfaced in mobile menu drawer. |
| 20 | `layouts/app.blade.php` | Nav Label: About | `nav_about_label` | `frontend_contents` | `TextInput` | `About` | `required\|string\|max:30` | Used in desktop nav and mobile drawer. |
| 21 | `layouts/app.blade.php` | Nav Label: Distinctives | `nav_features_label` | `frontend_contents` | `TextInput` | `Distinctives` | `required\|string\|max:30` | Used in desktop nav and mobile drawer. |
| 22 | `layouts/app.blade.php` | Nav Label: Curriculum | `nav_academics_label` | `frontend_contents` | `TextInput` | `Curriculum` | `required\|string\|max:30` | Used in desktop nav and mobile drawer. |
| 23 | `layouts/app.blade.php` | Nav Label: Outcomes | `nav_stats_label` | `frontend_contents` | `TextInput` | `Outcomes` | `required\|string\|max:30` | Used in desktop nav and mobile drawer. |
| 24 | `layouts/app.blade.php` | Nav Label: Campus | `nav_facilities_label` | `frontend_contents` | `TextInput` | `Campus` | `required\|string\|max:30` | Used in desktop nav and mobile drawer. |
| 25 | `layouts/app.blade.php` | Nav Label: Bulletin | `nav_news_label` | `frontend_contents` | `TextInput` | `Bulletin` | `required\|string\|max:30` | Used in desktop nav and mobile drawer. |
| 26 | `layouts/app.blade.php` | Nav Label: Contact | `nav_contact_label` | `frontend_contents` | `TextInput` | `Contact` | `required\|string\|max:30` | Used in desktop nav and mobile drawer. |
| 27 | `layouts/app.blade.php` | Nav Portals Dropdown Trigger Label | `nav_portals_label` | `frontend_contents` | `TextInput` | `Portals` | `required\|string\|max:20` | Desktop header portals dropdown button label. |
| 28 | `layouts/app.blade.php` | Student Portal Full Label | `portal_student_label` | `frontend_contents` | `TextInput` | `Student Portal` | `required\|string\|max:40` | Desktop portals dropdown and footer link. |
| 29 | `layouts/app.blade.php` | Faculty Portal Full Label | `portal_staff_label` | `frontend_contents` | `TextInput` | `Faculty Portal` | `required\|string\|max:40` | Desktop portals dropdown and footer link. |
| 30 | `layouts/app.blade.php` | Admin Portal Full Label | `portal_admin_label` | `frontend_contents` | `TextInput` | `Administration` | `required\|string\|max:40` | Desktop portals dropdown and footer link. |
| 31 | `layouts/app.blade.php` | Student Portal Short Label (Mobile) | `portal_student_label_short` | `frontend_contents` | `TextInput` | `Student` | `required\|string\|max:10` | 3-column mobile drawer button grid (max 10 chars). |
| 32 | `layouts/app.blade.php` | Faculty Portal Short Label (Mobile) | `portal_staff_label_short` | `frontend_contents` | `TextInput` | `Faculty` | `required\|string\|max:10` | 3-column mobile drawer button grid (max 10 chars). |
| 33 | `layouts/app.blade.php` | Admin Portal Short Label (Mobile) | `portal_admin_label_short` | `frontend_contents` | `TextInput` | `Admin` | `required\|string\|max:10` | 3-column mobile drawer button grid (max 10 chars). |
| 34 | `layouts/app.blade.php` | Header Admissions CTA Text | `header_cta_text` | `frontend_contents` | `TextInput` | `Admissions` | `nullable\|string\|max:30` | Desktop header rectangular CTA button. |
| 35 | `layouts/app.blade.php` | Header Admissions CTA Link | `header_cta_link` | `frontend_contents` | `TextInput` | `#admissions` | `nullable\|string\|max:100` | Smooth scroll anchor target. |
| **Hero Section (partials/home/hero.blade.php)** |
| 36 | `partials/home/hero.blade.php` | Hero Academic Badge / Eyebrow | `hero_badge` | `frontend_contents` | `TextInput` | `2026 / 2027 Academic Session` | `nullable\|string\|max:60` | Gold hairline + uppercase tracking-[0.25em] text. |
| 37 | `partials/home/hero.blade.php` | Hero Main Heading | `hero_title` | `frontend_contents` | `TextInput` | `Nurturing Intellectual Depth & Moral Leadership` | `required\|string\|max:120` | Responsive clamp serif title (2.75rem to 5rem). |
| 38 | `partials/home/hero.blade.php` | Hero Editorial Subtitle | `hero_subtitle` | `frontend_contents` | `Textarea` | `An accredited British-Nigerian secondary institution committed to scholastic rigor, scientific inquiry, and the formation of character.` | `nullable\|string\|max:250` | Strictly constrained to max 62ch width for editorial readability. |
| 39 | `partials/home/hero.blade.php` | Hero Background Photograph | `hero_image` | `frontend_contents` | `FileUpload` | `https://placehold.co/1920x1080/0B2545/FAF8F4?text=Apex+Crown+College+Scholars+Lagos` | `nullable\|image\|max:4096` | Full-bleed cover image with left-to-right Ink gradient overlay. |
| 40 | `partials/home/hero.blade.php` | Hero Background Image Alt Text | `hero_image_alt` | `frontend_contents` | `TextInput` | `Apex Crown College Scholars Lagos` | `nullable\|string\|max:100` | Accessibility label on background photograph. |
| 41 | `partials/home/hero.blade.php` | Hero Primary CTA Text | `hero_primary_cta_text` | `frontend_contents` | `TextInput` | `Apply for Admission` | `nullable\|string\|max:40` | Solid gold background with contrast text, full width on 390px mobile. |
| 42 | `partials/home/hero.blade.php` | Hero Primary CTA Link | `hero_primary_cta_link` | `frontend_contents` | `TextInput` | `#admissions` | `nullable\|string\|max:100` | Anchor or route link. |
| 43 | `partials/home/hero.blade.php` | Hero Secondary CTA Text | `hero_secondary_cta_text` | `frontend_contents` | `TextInput` | `Explore Prospectus` | `nullable\|string\|max:40` | Outlined button, full width stacked below primary on 390px mobile. |
| 44 | `partials/home/hero.blade.php` | Hero Secondary CTA Link | `hero_secondary_cta_link` | `frontend_contents` | `TextInput` | `#about` | `nullable\|string\|max:100` | Anchor or route link. |
| 45 | `partials/home/hero.blade.php` | Hero Scroll Prompt Text | `hero_scroll_label` | `frontend_contents` | `TextInput` | `Scroll to explore prospectus` | `nullable\|string\|max:50` | Bottom scroll indicator with bouncing dot. |
| **01 — About Section (partials/home/about.blade.php)** |
| 46 | `partials/home/about.blade.php` | About Eyebrow Label | `about_eyebrow` | `frontend_contents` | `TextInput` | `ABOUT THE COLLEGE` | `nullable\|string\|max:50` | Prefixed by dynamic section number "01 — " in Blade. |
| 47 | `partials/home/about.blade.php` | About Main Heading | `about_heading` | `frontend_contents` | `TextInput` | `A Tradition of Uncompromising Academic Standard` | `required\|string\|max:120` | Editorial serif heading. |
| 48 | `partials/home/about.blade.php` | About Portrait Photograph | `about_image` | `frontend_contents` | `FileUpload` | `https://placehold.co/800x1000/0B2545/FAF8F4?text=Principal+Portrait` | `nullable\|image\|max:4096` | 440px height on mobile, 540px on desktop. |
| 49 | `partials/home/about.blade.php` | About Portrait Alt Text | `about_image_alt` | `frontend_contents` | `TextInput` | `Dr. Mrs. Adebisi Balogun Head of School` | `nullable\|string\|max:100` | Accessibility alt attribute. |
| 50 | `partials/home/about.blade.php` | Years of Legacy Numeral | `about_years_badge` | `frontend_contents` | `TextInput` | `25` | `nullable\|string\|max:10` | Rendered with "+" in floating badge overlay. |
| 51 | `partials/home/about.blade.php` | Years of Legacy Subtitle | `about_years_label` | `frontend_contents` | `TextInput` | `Years of Academic Legacy in Lagos` | `nullable\|string\|max:60` | Gold badge caption block. |
| 52 | `partials/home/about.blade.php` | About Prose Narrative | `about_body` | `frontend_contents` | `RichEditor` | `<p>Founded in 2001, Apex Crown College synthesizes the rigorous Nigerian National Basic & Senior Secondary Curriculum with Cambridge Assessment International standards...</p>` | `required\|string` | Single RichEditor with bold, italic, links, and paragraphs (replaces split fields). |
| 53 | `partials/home/about.blade.php` | Principal / Head Full Name | `about_principal_name` | `frontend_contents` | `TextInput` | `Dr. (Mrs.) Adebisi Balogun` | `nullable\|string\|max:80` | Serif italic signature block. |
| 54 | `partials/home/about.blade.php` | Principal Official Title | `about_principal_title` | `frontend_contents` | `TextInput` | `B.Sc, M.Ed, Ph.D. — Principal & Head of School` | `nullable\|string\|max:100` | Uppercase gold subtitle. |
| **02 — Distinctives Section (partials/home/features.blade.php)** |
| 55 | `partials/home/features.blade.php` | Distinctives Eyebrow | `features_eyebrow` | `frontend_contents` | `TextInput` | `DISTINCTIVES` | `nullable\|string\|max:50` | Prefixed by dynamic section number "02 — " in Blade. |
| 56 | `partials/home/features.blade.php` | Distinctives Main Heading | `features_heading` | `frontend_contents` | `TextInput` | `The Pillars of an Apex Crown Education` | `required\|string\|max:120` | Left column serif title. |
| 57 | `partials/home/features.blade.php` | Distinctives Intro Text | `features_intro` | `frontend_contents` | `Textarea` | `A deliberate blend of academic depth, moral discipline, and technological literacy structured to cultivate leaders.` | `nullable\|string\|max:250` | Left column lead paragraph. |
| 58 | `partials/home/features.blade.php` | Curriculum Link CTA Text | `features_cta_text` | `frontend_contents` | `TextInput` | `Review Full Curriculum` | `nullable\|string\|max:40` | Inline arrow link below intro. |
| 59 | `partials/home/features.blade.php` | Curriculum Link CTA Target | `features_cta_link` | `frontend_contents` | `TextInput` | `#academics` | `nullable\|string\|max:100` | Smooth scroll anchor. |
| 60 | `partials/home/features.blade.php` | Distinctive Pillars Repeater | `features_items` | `frontend_contents` | `Repeater` | JSON array of 6 items (see rows 61–72) | `array\|max:8` | Table of contents row layout with gold serif numerals. |
| 61 | `partials/home/features.blade.php` | Item 1 Title | `features_items[0].title` | `frontend_contents` | `TextInput` | `Integrated Dual Curriculum` | `required\|string\|max:80` | Repeater item 1 heading. |
| 62 | `partials/home/features.blade.php` | Item 1 Description | `features_items[0].desc` | `frontend_contents` | `Textarea` | `Simultaneous mastery of the Nigerian National Curriculum (WAEC & NECO) alongside British Cambridge Checkpoint and IGCSE examinations.` | `required\|string\|max:250` | Repeater item 1 text. |
| 63 | `partials/home/features.blade.php` | Item 2 Title | `features_items[1].title` | `frontend_contents` | `TextInput` | `Individualized Tutorial Mentorship` | `required\|string\|max:80` | Repeater item 2 heading. |
| 64 | `partials/home/features.blade.php` | Item 2 Description | `features_items[1].desc` | `frontend_contents` | `Textarea` | `Strict 1:12 faculty-to-student ratio ensuring individualized attention, customized academic interventions, and dedicated pastoral tutors.` | `required\|string\|max:250` | Repeater item 2 text. |
| 65 | `partials/home/features.blade.php` | Item 3 Title | `features_items[2].title` | `frontend_contents` | `TextInput` | `Applied STEM & Computational Thinking` | `required\|string\|max:80` | Repeater item 3 heading. |
| 66 | `partials/home/features.blade.php` | Item 3 Description | `features_items[2].desc` | `frontend_contents` | `Textarea` | `Purpose-built laboratories for physics, chemistry, biology, agricultural science, and dedicated robotics/coding suites.` | `required\|string\|max:250` | Repeater item 3 text. |
| 67 | `partials/home/features.blade.php` | Item 4 Title | `features_items[3].title` | `frontend_contents` | `TextInput` | `Moral Formation & Character Discipline` | `required\|string\|max:80` | Repeater item 4 heading. |
| 68 | `partials/home/features.blade.php` | Item 4 Description | `features_items[3].desc` | `frontend_contents` | `Textarea` | `Uncompromising emphasis on integrity, punctuality, self-respect, civic responsibility, and community service.` | `required\|string\|max:250` | Repeater item 4 text. |
| 69 | `partials/home/features.blade.php` | Item 5 Title | `features_items[4].title` | `frontend_contents` | `TextInput` | `Comprehensive Boarding & Pastoral Care` | `required\|string\|max:80` | Repeater item 5 heading. |
| 70 | `partials/home/features.blade.php` | Item 5 Description | `features_items[4].desc` | `frontend_contents` | `Textarea` | `Modern, secure air-conditioned dormitories with round-the-clock power, resident housemasters, and multi-course nutritional dining.` | `required\|string\|max:250` | Repeater item 5 text. |
| 71 | `partials/home/features.blade.php` | Item 6 Title | `features_items[5].title` | `frontend_contents` | `TextInput` | `Oratory, Athletics & Cultural Life` | `required\|string\|max:80` | Repeater item 6 heading. |
| 72 | `partials/home/features.blade.php` | Item 6 Description | `features_items[5].desc` | `frontend_contents` | `Textarea` | `Weekly parliamentary debating, orchestral music tuition, Model United Nations, and championship track and field athletics.` | `required\|string\|max:250` | Repeater item 6 text. |
| **03 — Examination Outcomes / Stats (partials/home/results.blade.php)** |
| 73 | `partials/home/results.blade.php` | Stats Eyebrow | `stats_eyebrow` | `frontend_contents` | `TextInput` | `EXAMINATION OUTCOMES` | `nullable\|string\|max:50` | Prefixed by dynamic section number "03 — " in Blade. |
| 74 | `partials/home/results.blade.php` | Stats Main Heading | `stats_heading` | `frontend_contents` | `TextInput` | `Ten-Year Record of Scholastic Excellence` | `required\|string\|max:120` | Full-bleed Ink band heading. |
| 75 | `partials/home/results.blade.php` | Statistics Items Repeater | `stats_items` | `frontend_contents` | `Repeater` | JSON array of 4 stats (see rows 76–87) | `array\|min:1\|max:4` | Divided by vertical hairlines on desktop, 1-col on mobile. |
| 76 | `partials/home/results.blade.php` | Stat 1 Value | `stats_items[0].value` | `frontend_contents` | `TextInput` | `100%` | `required\|string\|max:15` | Serif text-5xl/6xl gold numeral. |
| 77 | `partials/home/results.blade.php` | Stat 1 Label | `stats_items[0].label` | `frontend_contents` | `TextInput` | `WAEC Pass Rate` | `required\|string\|max:40` | Uppercase tracking-[0.15em] title. |
| 78 | `partials/home/results.blade.php` | Stat 1 Detail | `stats_items[0].detail` | `frontend_contents` | `Textarea` | `5+ credits including English & Maths (10-year consecutive record)` | `nullable\|string\|max:150` | Muted white narrative snippet. |
| 79 | `partials/home/results.blade.php` | Stat 2 Value | `stats_items[1].value` | `frontend_contents` | `TextInput` | `94.8%` | `required\|string\|max:15` | Serif text-5xl/6xl gold numeral. |
| 80 | `partials/home/results.blade.php` | Stat 2 Label | `stats_items[1].label` | `frontend_contents` | `TextInput` | `A1 - B3 Distinctions` | `required\|string\|max:40` | Uppercase tracking-[0.15em] title. |
| 81 | `partials/home/results.blade.php` | Stat 2 Detail | `stats_items[1].detail` | `frontend_contents` | `Textarea` | `Achieved across Mathematics, Further Math, Physics, and Chemistry` | `nullable\|string\|max:150` | Muted white narrative snippet. |
| 82 | `partials/home/results.blade.php` | Stat 3 Value | `stats_items[2].value` | `frontend_contents` | `TextInput` | `312` | `required\|string\|max:15` | Serif text-5xl/6xl gold numeral. |
| 83 | `partials/home/results.blade.php` | Stat 3 Label | `stats_items[2].label` | `frontend_contents` | `TextInput` | `Average JAMB UTME` | `required\|string\|max:40` | Uppercase tracking-[0.15em] title. |
| 84 | `partials/home/results.blade.php` | Stat 3 Detail | `stats_items[2].detail` | `frontend_contents` | `Textarea` | `With the top candidate achieving 358 in the 2025 UTME session` | `nullable\|string\|max:150` | Muted white narrative snippet. |
| 85 | `partials/home/results.blade.php` | Stat 4 Value | `stats_items[3].value` | `frontend_contents` | `TextInput` | `98%` | `required\|string\|max:15` | Serif text-5xl/6xl gold numeral. |
| 86 | `partials/home/results.blade.php` | Stat 4 Label | `stats_items[3].label` | `frontend_contents` | `TextInput` | `University Placement` | `required\|string\|max:40` | Uppercase tracking-[0.15em] title. |
| 87 | `partials/home/results.blade.php` | Stat 4 Detail | `stats_items[3].detail` | `frontend_contents` | `Textarea` | `Direct admissions into premier universities across Nigeria, the UK, US, and Canada` | `nullable\|string\|max:150` | Muted white narrative snippet. |
| 88 | `partials/home/results.blade.php` | University Destinations Label | `stats_destinations_label` | `frontend_contents` | `TextInput` | `Representative Matriculations:` | `nullable\|string\|max:50` | Footnote title in gold uppercase text. |
| 89 | `partials/home/results.blade.php` | University Destinations List | `stats_destinations` | `frontend_contents` | `TagsInput` | `["University of Ibadan", "University of Lagos", "Covenant University", "Imperial College London", "University of Toronto", "University of Manchester"]` | `nullable\|array` | Bulleted list of universities. |
| **04 — Curriculum & Programmes (partials/home/academics.blade.php)** |
| 90 | `partials/home/academics.blade.php` | Curriculum Eyebrow | `academics_eyebrow` | `frontend_contents` | `TextInput` | `CURRICULUM & PROGRAMMES` | `nullable\|string\|max:50` | Prefixed by dynamic section number "04 — " in Blade. |
| 91 | `partials/home/academics.blade.php` | Curriculum Main Heading | `academics_heading` | `frontend_contents` | `TextInput` | `Structured Pathways for Secondary Scholars` | `required\|string\|max:120` | Section title in Fraunces serif. |
| 92 | `partials/home/academics.blade.php` | Curriculum Intro Text | `academics_intro` | `frontend_contents` | `Textarea` | `A comprehensive curriculum designed to build foundational mastery in the junior years and deep specialization in the senior years.` | `nullable\|string\|max:250` | Section lead narrative. |
| 93 | `partials/home/academics.blade.php` | Academic Tracks Repeater | `academics_tracks` | `frontend_contents` | `Repeater` | JSON array of 3 tracks (see rows 94–111) | `array\|min:1\|max:4` | 3-column card grid with hairline borders. |
| 94 | `partials/home/academics.blade.php` | Track 1 Code & Grade | `academics_tracks[0].code` | `frontend_contents` | `TextInput` | `JSS 1 — JSS 3` | `required\|string\|max:40` | E.g. "JSS 1 — JSS 3". |
| 95 | `partials/home/academics.blade.php` | Track 1 Name | `academics_tracks[0].name` | `frontend_contents` | `TextInput` | `Junior Secondary School` | `required\|string\|max:80` | Track headline. |
| 96 | `partials/home/academics.blade.php` | Track 1 Age Bracket | `academics_tracks[0].ages` | `frontend_contents` | `TextInput` | `Ages 10 — 13 Years` | `nullable\|string\|max:40` | E.g. "Ages 10 — 13 Years". |
| 97 | `partials/home/academics.blade.php` | Track 1 Qualifications | `academics_tracks[0].certs` | `frontend_contents` | `TextInput` | `BECE & Cambridge Checkpoint` | `nullable\|string\|max:100` | Examination targets. |
| 98 | `partials/home/academics.blade.php` | Track 1 Narrative | `academics_tracks[0].desc` | `frontend_contents` | `Textarea` | `Focuses on foundational intellectual development: computational thinking, language mastery, basic science, and cultural appreciation.` | `required\|string\|max:250` | Track description. |
| 99 | `partials/home/academics.blade.php` | Track 1 Subjects List | `academics_tracks[0].subjects` | `frontend_contents` | `TagsInput` | `["General Mathematics", "English & Literature", "Basic Science & Tech", "Coding Basics", "French & Languages", "Business Studies"]` | `required\|array` | Disciplines with gold square bullet dots. |
| 100 | `partials/home/academics.blade.php` | Track 2 Code & Grade | `academics_tracks[1].code` | `frontend_contents` | `TextInput` | `SSS 1 — SSS 3` | `required\|string\|max:40` | E.g. "SSS 1 — SSS 3". |
| 101 | `partials/home/academics.blade.php` | Track 2 Name | `academics_tracks[1].name` | `frontend_contents` | `TextInput` | `Senior Sciences & Technology` | `required\|string\|max:80` | Track headline. |
| 102 | `partials/home/academics.blade.php` | Track 2 Age Bracket | `academics_tracks[1].ages` | `frontend_contents` | `TextInput` | `Ages 13 — 17 Years` | `nullable\|string\|max:40` | E.g. "Ages 13 — 17 Years". |
| 103 | `partials/home/academics.blade.php` | Track 2 Qualifications | `academics_tracks[1].certs` | `frontend_contents` | `TextInput` | `WAEC, NECO, IGCSE & JAMB` | `nullable\|string\|max:100` | Examination targets. |
| 104 | `partials/home/academics.blade.php` | Track 2 Narrative | `academics_tracks[1].desc` | `frontend_contents` | `Textarea` | `Rigorous scientific inquiry for aspiring medical doctors, software architects, agricultural biotechnologists, and structural engineers.` | `required\|string\|max:250` | Track description. |
| 105 | `partials/home/academics.blade.php` | Track 2 Subjects List | `academics_tracks[1].subjects` | `frontend_contents` | `TagsInput` | `["Further Mathematics", "Physics & Chemistry", "Biology & Agric", "Technical Drawing", "Data Processing", "Weekly Practical Labs"]` | `required\|array` | Disciplines with gold square bullet dots. |
| 106 | `partials/home/academics.blade.php` | Track 3 Code & Grade | `academics_tracks[2].code` | `frontend_contents` | `TextInput` | `SSS 1 — SSS 3` | `required\|string\|max:40` | E.g. "SSS 1 — SSS 3". |
| 107 | `partials/home/academics.blade.php` | Track 3 Name | `academics_tracks[2].name` | `frontend_contents` | `TextInput` | `Senior Arts & Commercial Studies` | `required\|string\|max:80` | Track headline. |
| 108 | `partials/home/academics.blade.php` | Track 3 Age Bracket | `academics_tracks[2].ages` | `frontend_contents` | `TextInput` | `Ages 13 — 17 Years` | `nullable\|string\|max:40` | E.g. "Ages 13 — 17 Years". |
| 109 | `partials/home/academics.blade.php` | Track 3 Qualifications | `academics_tracks[2].certs` | `frontend_contents` | `TextInput` | `WAEC, NECO, IGCSE & JAMB` | `nullable\|string\|max:100` | Examination targets. |
| 110 | `partials/home/academics.blade.php` | Track 3 Narrative | `academics_tracks[2].desc` | `frontend_contents` | `Textarea` | `For future jurists, economists, chartered accountants, diplomats, and business leaders with intensive essay and analysis training.` | `required\|string\|max:250` | Track description. |
| 111 | `partials/home/academics.blade.php` | Track 3 Subjects List | `academics_tracks[2].subjects` | `frontend_contents` | `TagsInput` | `["Literature in English", "Government & History", "Financial Accounting", "Economics & Commerce", "Visual Arts & Music", "Debating Society"]` | `required\|array` | Disciplines with gold square bullet dots. |
| **05 — Campus Infrastructure (partials/home/facilities.blade.php)** |
| 112 | `partials/home/facilities.blade.php` | Facilities Eyebrow | `facilities_eyebrow` | `frontend_contents` | `TextInput` | `CAMPUS INFRASTRUCTURE` | `nullable\|string\|max:50` | Prefixed by dynamic section number "05 — " in Blade. |
| 113 | `partials/home/facilities.blade.php` | Facilities Main Heading | `facilities_heading` | `frontend_contents` | `TextInput` | `Purpose-Built Learning & Living Environments` | `required\|string\|max:120` | Section title. |
| 114 | `partials/home/facilities.blade.php` | Facilities Repeater List | `facilities_items` | `frontend_contents` | `Repeater` | JSON array of 6 facility items (see rows 115–138) | `array\|min:1\|max:8` | Note: First item is styled 2x2 in Blade, rest 1x1. `span` is eliminated from admin. |
| 115 | `partials/home/facilities.blade.php` | Facility 1 Title | `facilities_items[0].title` | `frontend_contents` | `TextInput` | `Advanced Science Laboratories` | `required\|string\|max:80` | Facility modal and card heading. |
| 116 | `partials/home/facilities.blade.php` | Facility 1 Category | `facilities_items[0].category` | `frontend_contents` | `TextInput` | `ACADEMIC` | `required\|string\|max:30` | Uppercase gold badge. |
| 117 | `partials/home/facilities.blade.php` | Facility 1 Description | `facilities_items[0].desc` | `frontend_contents` | `Textarea` | `Dedicated biology, chemistry, and physics laboratories fully fitted with modern glassware, fume hoods, and analytical instrumentation.` | `required\|string\|max:300` | Lightbox and card description. |
| 118 | `partials/home/facilities.blade.php` | Facility 1 Image | `facilities_items[0].image` | `frontend_contents` | `FileUpload` | `https://placehold.co/1000x800/0B2545/FAF8F4?text=Science+Laboratories+Apex+Crown` | `required\|image\|max:4096` | Upload with alt text. |
| 119 | `partials/home/facilities.blade.php` | Facility 2 Title | `facilities_items[1].title` | `frontend_contents` | `TextInput` | `Digital ICT & AI Suites` | `required\|string\|max:80` | Facility modal and card heading. |
| 120 | `partials/home/facilities.blade.php` | Facility 2 Category | `facilities_items[1].category` | `frontend_contents` | `TextInput` | `TECHNOLOGY` | `required\|string\|max:30` | Uppercase gold badge. |
| 121 | `partials/home/facilities.blade.php` | Facility 2 Description | `facilities_items[1].desc` | `frontend_contents` | `Textarea` | `High-speed gigabit workstations, interactive smartboards, and robotics hardware kits.` | `required\|string\|max:300` | Lightbox and card description. |
| 122 | `partials/home/facilities.blade.php` | Facility 2 Image | `facilities_items[1].image` | `frontend_contents` | `FileUpload` | `https://placehold.co/600x400/0B2545/FAF8F4?text=Digital+ICT+Suites` | `required\|image\|max:4096` | Upload with alt text. |
| 123 | `partials/home/facilities.blade.php` | Facility 3 Title | `facilities_items[2].title` | `frontend_contents` | `TextInput` | `E-Library & Study Commons` | `required\|string\|max:80` | Facility modal and card heading. |
| 124 | `partials/home/facilities.blade.php` | Facility 3 Category | `facilities_items[2].category` | `frontend_contents` | `TextInput` | `RESEARCH` | `required\|string\|max:30` | Uppercase gold badge. |
| 125 | `partials/home/facilities.blade.php` | Facility 3 Description | `facilities_items[2].desc` | `frontend_contents` | `Textarea` | `Over 15,000 bound volumes complemented by digital JSTOR and Britannica research terminals.` | `required\|string\|max:300` | Lightbox and card description. |
| 126 | `partials/home/facilities.blade.php` | Facility 3 Image | `facilities_items[2].image` | `frontend_contents` | `FileUpload` | `https://placehold.co/600x400/0B2545/FAF8F4?text=E-Library+Commons` | `required\|image\|max:4096` | Upload with alt text. |
| 127 | `partials/home/facilities.blade.php` | Facility 4 Title | `facilities_items[3].title` | `frontend_contents` | `TextInput` | `Sports Arena & Athletic Complex` | `required\|string\|max:80` | Facility modal and card heading. |
| 128 | `partials/home/facilities.blade.php` | Facility 4 Category | `facilities_items[3].category` | `frontend_contents` | `TextInput` | `ATHLETICS` | `required\|string\|max:30` | Uppercase gold badge. |
| 129 | `partials/home/facilities.blade.php` | Facility 4 Description | `facilities_items[3].desc` | `frontend_contents` | `Textarea` | `Standard football pitch, outdoor basketball and tennis courts, and all-weather track.` | `required\|string\|max:300` | Lightbox and card description. |
| 130 | `partials/home/facilities.blade.php` | Facility 4 Image | `facilities_items[3].image` | `frontend_contents` | `FileUpload` | `https://placehold.co/600x400/0B2545/FAF8F4?text=Sports+Complex` | `required\|image\|max:4096` | Upload with alt text. |
| 131 | `partials/home/facilities.blade.php` | Facility 5 Title | `facilities_items[4].title` | `frontend_contents` | `TextInput` | `Residential Hostels & Dining` | `required\|string\|max:80` | Facility modal and card heading. |
| 132 | `partials/home/facilities.blade.php` | Facility 5 Category | `facilities_items[4].category` | `frontend_contents` | `TextInput` | `RESIDENTIAL` | `required\|string\|max:30` | Uppercase gold badge. |
| 133 | `partials/home/facilities.blade.php` | Facility 5 Description | `facilities_items[4].desc` | `frontend_contents` | `Textarea` | `Air-conditioned boarding houses with 24/7 power backup, resident house parents, and dining hall.` | `required\|string\|max:300` | Lightbox and card description. |
| 134 | `partials/home/facilities.blade.php` | Facility 5 Image | `facilities_items[4].image` | `frontend_contents` | `FileUpload` | `https://placehold.co/600x400/0B2545/FAF8F4?text=Boarding+Hostels` | `required\|image\|max:4096` | Upload with alt text. |
| 135 | `partials/home/facilities.blade.php` | Facility 6 Title | `facilities_items[5].title` | `frontend_contents` | `TextInput` | `Acoustic Auditorium & Music Studio` | `required\|string\|max:80` | Facility modal and card heading. |
| 136 | `partials/home/facilities.blade.php` | Facility 6 Category | `facilities_items[5].category` | `frontend_contents` | `TextInput` | `CULTURE` | `required\|string\|max:30` | Uppercase gold badge. |
| 137 | `partials/home/facilities.blade.php` | Facility 6 Description | `facilities_items[5].desc` | `frontend_contents` | `Textarea` | `800-seat theater hall for assemblies, orchestral recitals, and graduation valedictions.` | `required\|string\|max:300` | Lightbox and card description. |
| 138 | `partials/home/facilities.blade.php` | Facility 6 Image | `facilities_items[5].image` | `frontend_contents` | `FileUpload` | `https://placehold.co/600x400/0B2545/FAF8F4?text=Auditorium+Studio` | `required\|image\|max:4096` | Upload with alt text. |
| **06 — Bulletin & Announcements (partials/home/news.blade.php)** |
| 139 | `partials/home/news.blade.php` | News Eyebrow | `news_eyebrow` | `frontend_contents` | `TextInput` | `BULLETIN & CALENDAR` | `nullable\|string\|max:50` | Prefixed by dynamic section number "06 — " in Blade. |
| 140 | `partials/home/news.blade.php` | News Main Heading | `news_heading` | `frontend_contents` | `TextInput` | `Recent Announcements & Key Dates` | `required\|string\|max:120` | Section title. |
| 141 | `partials/home/news.blade.php` | News Articles List | `news_articles` | `frontend_contents` (or `Post` model) | `Repeater` | JSON array of 3 articles (see rows 142–156) | `array\|max:6` | Horizontal editorial rows with left square thumbnail on mobile and desktop. |
| 142 | `partials/home/news.blade.php` | Article 1 Title | `news_articles[0].title` | `frontend_contents` | `TextInput` | `2026/2027 First Batch National Entrance Examination & Scholarship Screening` | `required\|string\|max:150` | Headline. |
| 143 | `partials/home/news.blade.php` | Article 1 Category | `news_articles[0].category` | `frontend_contents` | `TextInput` | `ADMISSIONS` | `required\|string\|max:30` | Uppercase badge. |
| 144 | `partials/home/news.blade.php` | Article 1 Date | `news_articles[0].date` | `frontend_contents` | `TextInput` | `Saturday, 18 April 2026` | `required\|string\|max:50` | Formatted date string. |
| 145 | `partials/home/news.blade.php` | Article 1 Summary | `news_articles[0].summary` | `frontend_contents` | `Textarea` | `Prospective candidates for JSS 1 and transfer classes will sit for Mathematics, English Language, and General Aptitude screening. Top 5 candidates receive merit tuition scholarships.` | `required\|string\|max:300` | Right column summary text. |
| 146 | `partials/home/news.blade.php` | Article 1 Thumbnail | `news_articles[0].image` | `frontend_contents` | `FileUpload` | `https://placehold.co/200x200/0B2545/FAF8F4?text=Exam+Entry` | `required\|image\|max:2048` | Left square 24x24/28x28 thumbnail. |
| 147 | `partials/home/news.blade.php` | Article 2 Title | `news_articles[1].title` | `frontend_contents` | `TextInput` | `24th Annual Inter-House Athletics & March-Past Championship` | `required\|string\|max:150` | Headline. |
| 148 | `partials/home/news.blade.php` | Article 2 Category | `news_articles[1].category` | `frontend_contents` | `TextInput` | `ATHLETICS` | `required\|string\|max:30` | Uppercase badge. |
| 149 | `partials/home/news.blade.php` | Article 2 Date | `news_articles[1].date` | `frontend_contents` | `TextInput` | `Friday, 27 March 2026` | `required\|string\|max:50` | Formatted date string. |
| 150 | `partials/home/news.blade.php` | Article 2 Summary | `news_articles[1].summary` | `frontend_contents` | `Textarea` | `Emerald, Ruby, Sapphire, and Topaz houses compete for track, field, and cultural march-past honors. Parents, guardians, and alumni are cordially invited to the Main Sports Arena.` | `required\|string\|max:300` | Right column summary text. |
| 151 | `partials/home/news.blade.php` | Article 2 Thumbnail | `news_articles[1].image` | `frontend_contents` | `FileUpload` | `https://placehold.co/200x200/0B2545/FAF8F4?text=Sports` | `required\|image\|max:2048` | Left square 24x24/28x28 thumbnail. |
| 152 | `partials/home/news.blade.php` | Article 3 Title | `news_articles[2].title` | `frontend_contents` | `TextInput` | `Annual Young Innovators STEM & Robotics Public Exhibition` | `required\|string\|max:150` | Headline. |
| 153 | `partials/home/news.blade.php` | Article 3 Category | `news_articles[2].category` | `frontend_contents` | `TextInput` | `ACADEMICS` | `required\|string\|max:30` | Uppercase badge. |
| 154 | `partials/home/news.blade.php` | Article 3 Date | `news_articles[2].date` | `frontend_contents` | `TextInput` | `Wednesday, 13 May 2026` | `required\|string\|max:50` | Formatted date string. |
| 155 | `partials/home/news.blade.php` | Article 3 Summary | `news_articles[2].summary` | `frontend_contents` | `Textarea` | `Senior secondary scholars present functional solar micro-inverter designs, automated irrigation models, and AI chatbot demonstrators to university visiting professors.` | `required\|string\|max:300` | Right column summary text. |
| 156 | `partials/home/news.blade.php` | Article 3 Thumbnail | `news_articles[2].image` | `frontend_contents` | `FileUpload` | `https://placehold.co/200x200/0B2545/FAF8F4?text=STEM+Expo` | `required\|image\|max:2048` | Left square 24x24/28x28 thumbnail. |
| **07 — Voices & Testimonials (partials/home/testimonials.blade.php)** |
| 157 | `partials/home/testimonials.blade.php` | Testimonials Eyebrow | `testimonials_eyebrow` | `frontend_contents` | `TextInput` | `VOICES OF PARENTS & ALUMNI` | `nullable\|string\|max:50` | Prefixed by dynamic section number "07 — " in Blade. |
| 158 | `partials/home/testimonials.blade.php` | Testimonials Main Heading | `testimonials_heading` | `frontend_contents` | `TextInput` | `Perspectives on an Apex Crown Education` | `required\|string\|max:120` | Section title in Fraunces serif. |
| 159 | `partials/home/testimonials.blade.php` | Testimonials Intro Text | `testimonials_intro` | `frontend_contents` | `Textarea` | `Reflections from parents, guardians, and alumni who have experienced the transformative impact of our community.` | `nullable\|string\|max:250` | Left column narrative. |
| 160 | `partials/home/testimonials.blade.php` | Testimonials Quotes Repeater | `testimonials_items` | `frontend_contents` | `Repeater` | JSON array of 3 quotes (see rows 161–169) | `array\|min:1\|max:6` | 12-column asymmetric quote display with pagination controls. |
| 161 | `partials/home/testimonials.blade.php` | Quote 1 Content | `testimonials_items[0].quote` | `frontend_contents` | `Textarea` | `Enrolling our children at Apex Crown College was the most consequential educational choice we made. Beyond their straight A1s in WAEC, the depth of their poise, moral conviction, and critical thinking is extraordinary.` | `required\|string\|max:400` | Serif italic quote text. |
| 162 | `partials/home/testimonials.blade.php` | Quote 1 Author | `testimonials_items[0].author` | `frontend_contents` | `TextInput` | `Chief & Dr. (Mrs.) Olumide Adeleke` | `required\|string\|max:80` | Author name in serif. |
| 163 | `partials/home/testimonials.blade.php` | Quote 1 Author Role | `testimonials_items[0].role` | `frontend_contents` | `TextInput` | `Parents of 2024 Valedictorians` | `required\|string\|max:80` | Uppercase gold subtitle. |
| 164 | `partials/home/testimonials.blade.php` | Quote 2 Content | `testimonials_items[0].quote` | `frontend_contents` | `Textarea` | `The discipline instilled during my boarding years at Apex Crown was decisive. When I entered Medical College at the University of Ibadan, I realized I had already developed the study stamina and leadership habits needed to thrive.` | `required\|string\|max:400` | Serif italic quote text. |
| 165 | `partials/home/testimonials.blade.php` | Quote 2 Author | `testimonials_items[1].author` | `frontend_contents` | `TextInput` | `Dr. Favour Chidera Eze` | `required\|string\|max:80` | Author name in serif. |
| 166 | `partials/home/testimonials.blade.php` | Quote 2 Author Role | `testimonials_items[1].role` | `frontend_contents` | `TextInput` | `Medical Practitioner, UCH — Alumna (Class of 2018)` | `required\|string\|max:80` | Uppercase gold subtitle. |
| 167 | `partials/home/testimonials.blade.php` | Quote 3 Content | `testimonials_items[2].quote` | `frontend_contents` | `Textarea` | `The tutorial masters possess an uncommon dedication. When my son required deeper coaching in Further Mathematics, his tutor organized after-hours clinics until he mastered every calculus theorem.` | `required\|string\|max:400` | Serif italic quote text. |
| 168 | `partials/home/testimonials.blade.php` | Quote 3 Author | `testimonials_items[2].author` | `frontend_contents` | `TextInput` | `Alhaji Mansur Danjuma` | `required\|string\|max:80` | Author name in serif. |
| 169 | `partials/home/testimonials.blade.php` | Quote 3 Author Role | `testimonials_items[2].role` | `frontend_contents` | `TextInput` | `Parent of SSS 3 Scholar & PTA Executive` | `required\|string\|max:80` | Uppercase gold subtitle. |
| **Admissions Call to Action (partials/home/admissions.blade.php)** |
| 170 | `partials/home/admissions.blade.php` | Admissions Eyebrow | `admissions_cta_eyebrow` | `frontend_contents` | `TextInput` | `ADMISSIONS 2026 / 2027` | `nullable\|string\|max:50` | Flanked by twin gold hairlines. |
| 171 | `partials/home/admissions.blade.php` | Admissions Centred Heading | `admissions_cta_heading` | `frontend_contents` | `TextInput` | `Enroll Your Child in a Tradition of Distinction` | `required\|string\|max:120` | Centred serif heading on Ink background. |
| 172 | `partials/home/admissions.blade.php` | Admissions Subtitle | `admissions_cta_subtitle` | `frontend_contents` | `Textarea` | `Applications are now being received for JSS 1 and limited transfer vacancies into JSS 2 and SSS 1. Day and Full-Boarding options available.` | `nullable\|string\|max:250` | Centred narrative text <= 62ch. |
| 173 | `partials/home/admissions.blade.php` | Application Steps Repeater | `admissions_cta_steps` | `frontend_contents` | `Repeater` | JSON array of 4 steps (see rows 174–185) | `array\|min:1\|max:4` | 4-step horizontal process on desktop, 1-col on mobile. |
| 174 | `partials/home/admissions.blade.php` | Step 1 Numeral | `admissions_cta_steps[0].num` | `frontend_contents` | `TextInput` | `01` | `required\|string\|max:10` | Gold serif number. |
| 175 | `partials/home/admissions.blade.php` | Step 1 Title | `admissions_cta_steps[0].title` | `frontend_contents` | `TextInput` | `Obtain Form` | `required\|string\|max:60` | Step heading. |
| 176 | `partials/home/admissions.blade.php` | Step 1 Description | `admissions_cta_steps[0].desc` | `frontend_contents` | `Textarea` | `Complete the online application or purchase the dossier at the campus Registry.` | `required\|string\|max:150` | Step instruction. |
| 177 | `partials/home/admissions.blade.php` | Step 2 Numeral | `admissions_cta_steps[1].num` | `frontend_contents` | `TextInput` | `02` | `required\|string\|max:10` | Gold serif number. |
| 178 | `partials/home/admissions.blade.php` | Step 2 Title | `admissions_cta_steps[1].title` | `frontend_contents` | `TextInput` | `Entrance Assessment` | `required\|string\|max:60` | Step heading. |
| 179 | `partials/home/admissions.blade.php` | Step 2 Description | `admissions_cta_steps[1].desc` | `frontend_contents` | `Textarea` | `Candidate attends the written examination in Mathematics, English, and Aptitude.` | `required\|string\|max:150` | Step instruction. |
| 180 | `partials/home/admissions.blade.php` | Step 3 Numeral | `admissions_cta_steps[2].num` | `frontend_contents` | `TextInput` | `03` | `required\|string\|max:10` | Gold serif number. |
| 181 | `partials/home/admissions.blade.php` | Step 3 Title | `admissions_cta_steps[2].title` | `frontend_contents` | `TextInput` | `Admission Offer` | `required\|string\|max:60` | Step heading. |
| 182 | `partials/home/admissions.blade.php` | Step 3 Description | `admissions_cta_steps[2].desc` | `frontend_contents` | `Textarea` | `Successful applicants receive formal letters of admission within 5 working days.` | `required\|string\|max:150` | Step instruction. |
| 183 | `partials/home/admissions.blade.php` | Step 4 Numeral | `admissions_cta_steps[3].num` | `frontend_contents` | `TextInput` | `04` | `required\|string\|max:10` | Gold serif number. |
| 184 | `partials/home/admissions.blade.php` | Step 4 Title | `admissions_cta_steps[3].title` | `frontend_contents` | `TextInput` | `Resumption & Induction` | `required\|string\|max:60` | Step heading. |
| 185 | `partials/home/admissions.blade.php` | Step 4 Description | `admissions_cta_steps[3].desc` | `frontend_contents` | `Textarea` | `Scholars check in for the matriculation orientation and academic commencement.` | `required\|string\|max:150` | Step instruction. |
| 186 | `partials/home/admissions.blade.php` | Admissions Primary Action Button | `admissions_cta_primary_btn` | `frontend_contents` | `TextInput` | `Begin Online Application` | `nullable\|string\|max:40` | Solid gold button. |
| 187 | `partials/home/admissions.blade.php` | Admissions Primary Action Link | `admissions_cta_primary_link` | `frontend_contents` | `TextInput` | `#contact` | `nullable\|string\|max:100` | Target anchor or route. |
| 188 | `partials/home/admissions.blade.php` | Admissions Secondary Action Button | `admissions_cta_secondary_btn` | `frontend_contents` | `TextInput` | `Download Prospectus (PDF)` | `nullable\|string\|max:40` | Outlined white button. |
| 189 | `partials/home/admissions.blade.php` | Admissions Secondary Action Link | `admissions_cta_secondary_link` | `frontend_contents` | `TextInput` | `#contact` | `nullable\|string\|max:100` | Target anchor or route. |
| **08 — Campus Visitation & Inquiry Form (partials/home/contact.blade.php)** |
| 190 | `partials/home/contact.blade.php` | Contact Eyebrow | `contact_eyebrow` | `frontend_contents` | `TextInput` | `CAMPUS VISITATION & INQUIRY` | `nullable\|string\|max:50` | Prefixed by dynamic section number "08 — " in Blade. |
| 191 | `partials/home/contact.blade.php` | Contact Main Heading | `contact_heading` | `frontend_contents` | `TextInput` | `Schedule a Guided Tour or Speak with Admissions` | `required\|string\|max:120` | Section title in Fraunces serif. |
| 192 | `partials/home/contact.blade.php` | Contact Intro Text | `contact_intro` | `frontend_contents` | `Textarea` | `Our Admissions Registry receives families for private consultations and campus walkthroughs by appointment.` | `nullable\|string\|max:250` | Left column lead text. |
| 193 | `partials/home/contact.blade.php` | Campus Address Block | `contact_address` | `frontend_contents` (or `settings.school_address`) | `Textarea` | `Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos State, Nigeria` | `required\|string\|max:250` | Left visitation details block. |
| 194 | `partials/home/contact.blade.php` | Campus Address Header Label | `contact_address_label` | `frontend_contents` | `TextInput` | `Campus Address` | `required\|string\|max:40` | Upper gold label for address. |
| 195 | `partials/home/contact.blade.php` | Contact Telephone Header Label | `contact_phone_label` | `frontend_contents` | `TextInput` | `Telephone` | `required\|string\|max:40` | Upper gold label for phone lines. |
| 196 | `partials/home/contact.blade.php` | Additional Telephone Lines | `contact_additional_phones` | `frontend_contents` | `TagsInput` | `["+234 812 345 6789"]` | `nullable\|array` | Rendered alongside primary `school_phone` setting. |
| 197 | `partials/home/contact.blade.php` | Contact Registry Email Header Label | `contact_email_label` | `frontend_contents` | `TextInput` | `Registry Email` | `required\|string\|max:40` | Upper gold label for email desk. |
| 198 | `partials/home/contact.blade.php` | Additional Email Desks | `contact_additional_emails` | `frontend_contents` | `TagsInput` | `["info@apexcrown.edu.ng"]` | `nullable\|array` | Rendered alongside primary `school_email` setting. |
| 199 | `partials/home/contact.blade.php` | Visiting Hours Header Label | `contact_visiting_hours_label` | `frontend_contents` | `TextInput` | `Admissions Hours` | `required\|string\|max:40` | Upper gold label for visiting schedule. |
| 200 | `partials/home/contact.blade.php` | Visiting Hours Narrative | `contact_visiting_hours` | `frontend_contents` | `TextInput` | `Monday – Friday: 8:00 AM – 4:00 PM \| Saturday: 9:00 AM – 1:00 PM` | `required\|string\|max:120` | Admissions office schedule. |
| 201 | `partials/home/contact.blade.php` | Inquiry Form Title | `contact_form_title` | `frontend_contents` | `TextInput` | `Admissions Prospectus Inquiry` | `required\|string\|max:80` | White card inquiry heading. |
| 202 | `partials/home/contact.blade.php` | Inquiry Form Description | `contact_form_desc` | `frontend_contents` | `Textarea` | `Submit your inquiry and our admissions counsellor will respond within one business day.` | `nullable\|string\|max:200` | Inquiry subtitle. |
| 203 | `partials/home/contact.blade.php` | Form Field: Parent Name Label | `contact_form_name_label` | `frontend_contents` | `TextInput` | `Parent / Guardian Name *` | `required\|string\|max:60` | Input label for parent name. |
| 204 | `partials/home/contact.blade.php` | Form Field: Phone Label | `contact_form_phone_label` | `frontend_contents` | `TextInput` | `Telephone Number *` | `required\|string\|max:60` | Input label for telephone number. |
| 205 | `partials/home/contact.blade.php` | Form Field: Email Label | `contact_form_email_label` | `frontend_contents` | `TextInput` | `Email Address *` | `required\|string\|max:60` | Input label for email address. |
| 206 | `partials/home/contact.blade.php` | Form Field: Grade Selection Label | `contact_form_grade_label` | `frontend_contents` | `TextInput` | `Class Level of Interest *` | `required\|string\|max:60` | Select dropdown label. |
| 207 | `partials/home/contact.blade.php` | Form Field: Grade Select Placeholder | `contact_form_grade_placeholder` | `frontend_contents` | `TextInput` | `Select Candidate Grade` | `required\|string\|max:60` | Default placeholder option text (`value=""`). |
| 208 | `partials/home/contact.blade.php` | Candidate Grade Options Repeater | `contact_form_classes` | `frontend_contents` | `Repeater` | JSON array of `{value, label}` pairs (see rows 209–212) | `required\|array` | Stored as repeater storing both database submission value and parent display label. |
| 209 | `partials/home/contact.blade.php` | Grade Option 1 | `contact_form_classes[0]` | `frontend_contents` | `TextInput` (Pair) | `value: "jss1", label: "Junior Secondary 1 (Entry)"` | `required` | Option choice 1. |
| 210 | `partials/home/contact.blade.php` | Grade Option 2 | `contact_form_classes[1]` | `frontend_contents` | `TextInput` (Pair) | `value: "jss2", label: "Junior Secondary 2 (Transfer)"` | `required` | Option choice 2. |
| 211 | `partials/home/contact.blade.php` | Grade Option 3 | `contact_form_classes[2]` | `frontend_contents` | `TextInput` (Pair) | `value: "sss1", label: "Senior Secondary 1 (Sciences)"` | `required` | Option choice 3. |
| 212 | `partials/home/contact.blade.php` | Grade Option 4 | `contact_form_classes[3]` | `frontend_contents` | `TextInput` (Pair) | `value: "sss1-arts", label: "Senior Secondary 1 (Arts & Commercial)"` | `required` | Option choice 4. |
| 213 | `partials/home/contact.blade.php` | Form Field: Notes Label | `contact_form_notes_label` | `frontend_contents` | `TextInput` | `Prospective Scholar Notes / Questions` | `nullable\|string\|max:80` | Textarea label for inquiries. |
| 214 | `partials/home/contact.blade.php` | Inquiry Confirmation Title | `contact_form_success_title` | `frontend_contents` | `TextInput` | `Inquiry Received` | `nullable\|string\|max:60` | Alpine.js confirmation state heading. |
| 215 | `partials/home/contact.blade.php` | Inquiry Confirmation Message | `contact_form_success_desc` | `frontend_contents` | `Textarea` | `Thank you for inquiring about Apex Crown College. The Admissions Office has received your details and will get in touch shortly.` | `nullable\|string\|max:250` | Confirmation body text. |
| **Footer & Colophon (components/layouts/app.blade.php)** |
| 216 | `layouts/app.blade.php` | Footer Edition Badge | `footer_edition_label` | `frontend_contents` | `TextInput` | `Prospectus Edition` | `nullable\|string\|max:40` | Gold badge under footer crest. |
| 217 | `layouts/app.blade.php` | Footer Mission Summary | `footer_description` | `frontend_contents` | `Textarea` | `An accredited British-Nigerian secondary school dedicated to academic brilliance, moral character, and global leadership.` | `required\|string\|max:250` | Left column mission blurb. |
| 218 | `layouts/app.blade.php` | Footer Accreditations Text | `footer_accreditations` | `frontend_contents` | `TextInput` | `Accredited by WAEC, NECO & Cambridge International.` | `nullable\|string\|max:150` | Gold footnote. |
| 219 | `layouts/app.blade.php` | Footer Col 2 Title | `footer_col2_heading` | `frontend_contents` | `TextInput` | `Prospectus` | `nullable\|string\|max:40` | Col 2 chapter index heading. |
| 220 | `layouts/app.blade.php` | Footer Col 3 Title | `footer_col3_heading` | `frontend_contents` | `TextInput` | `Registry & Portals` | `nullable\|string\|max:40` | Col 3 portals heading. |
| 221 | `layouts/app.blade.php` | Footer Col 4 Title | `footer_col4_heading` | `frontend_contents` | `TextInput` | `Campus Registry` | `nullable\|string\|max:40` | Col 4 contact heading. |
| 222 | `layouts/app.blade.php` | Footer Link: Exam Dates Label | `footer_exam_link_label` | `frontend_contents` | `TextInput` | `Entrance Examination Dates` | `required\|string\|max:50` | Quick link in Col 3. |
| 223 | `layouts/app.blade.php` | Footer Link: Exam Dates URL | `footer_exam_link_url` | `frontend_contents` | `TextInput` | `#admissions` | `required\|string\|max:100` | Target URL/anchor. |
| 224 | `layouts/app.blade.php` | Footer Link: Tuition & Scholarships Label | `footer_tuition_link_label` | `frontend_contents` | `TextInput` | `Tuition & Scholarships` | `required\|string\|max:50` | Quick link in Col 3. |
| 225 | `layouts/app.blade.php` | Footer Link: Tuition & Scholarships URL | `footer_tuition_link_url` | `frontend_contents` | `TextInput` | `#admissions` | `required\|string\|max:100` | Target URL/anchor. |
| 226 | `layouts/app.blade.php` | Footer Privacy Policy Text | `footer_privacy_label` | `frontend_contents` | `TextInput` | `Privacy Policy` | `nullable\|string\|max:40` | Bottom legal bar link. |
| 227 | `layouts/app.blade.php` | Footer Privacy Policy Link | `footer_privacy_link` | `frontend_contents` | `TextInput` | `#about` | `nullable\|string\|max:100` | Target URL. |
| 228 | `layouts/app.blade.php` | Footer Terms of Enrollment Text | `footer_terms_label` | `frontend_contents` | `TextInput` | `Terms of Enrollment` | `nullable\|string\|max:40` | Bottom legal bar link. |
| 229 | `layouts/app.blade.php` | Footer Terms of Enrollment Link | `footer_terms_link` | `frontend_contents` | `TextInput` | `#about` | `nullable\|string\|max:100` | Target URL. |
| 230 | `layouts/app.blade.php` | Footer Campus Directions Text | `footer_directions_label` | `frontend_contents` | `TextInput` | `Campus Directions` | `nullable\|string\|max:40` | Bottom legal bar link. |
| 231 | `layouts/app.blade.php` | Footer Campus Directions Link | `footer_directions_link` | `frontend_contents` | `TextInput` | `#contact` | `nullable\|string\|max:100` | Target URL. |

---

## 2. Grep Verification

### 2.1 Raw Unedited Command Output
```text
$ grep -rn ">[A-Z]" resources/views/partials resources/views/components/site resources/views/components/layouts
resources/views/partials/home/hero.blade.php:67:      <span>Scroll to explore prospectus</span>
resources/views/partials/home/features.blade.php:31:            <span>Review Full Curriculum</span>
resources/views/partials/home/contact.blade.php:34:            <span class="text-[10px] uppercase tracking-widest text-accent font-bold block mb-1">Campus Address</span>
resources/views/partials/home/contact.blade.php:40:            <span class="text-[10px] uppercase tracking-widest text-accent font-bold block mb-1">Telephone</span>
resources/views/partials/home/contact.blade.php:50:            <span class="text-[10px] uppercase tracking-widest text-accent font-bold block mb-1">Registry Email</span>
resources/views/partials/home/contact.blade.php:60:            <span class="text-[10px] uppercase tracking-widest text-accent font-bold block mb-1">Admissions Hours</span>
resources/views/partials/home/contact.blade.php:82:                  <label class="block uppercase tracking-wider text-ink font-bold mb-2">Parent / Guardian Name *</label>
resources/views/partials/home/contact.blade.php:87:                  <label class="block uppercase tracking-wider text-ink font-bold mb-2">Telephone Number *</label>
resources/views/partials/home/contact.blade.php:95:                  <label class="block uppercase tracking-wider text-ink font-bold mb-2">Email Address *</label>
resources/views/partials/home/contact.blade.php:100:                  <label class="block uppercase tracking-wider text-ink font-bold mb-2">Class Level of Interest *</label>
resources/views/partials/home/contact.blade.php:102:                    <option value="">Select Candidate Grade</option>
resources/views/partials/home/contact.blade.php:103:                    <option value="jss1">Junior Secondary 1 (Entry)</option>
resources/views/partials/home/contact.blade.php:104:                    <option value="jss2">Junior Secondary 2 (Transfer)</option>
resources/views/partials/home/contact.blade.php:105:                    <option value="sss1">Senior Secondary 1 (Sciences)</option>
resources/views/partials/home/contact.blade.php:106:                    <option value="sss1-arts">Senior Secondary 1 (Arts &amp; Commercial)</option>
resources/views/partials/home/contact.blade.php:112:                <label class="block uppercase tracking-wider text-ink font-bold mb-2">Prospective Scholar Notes / Questions</label>
resources/views/partials/home/contact.blade.php:129:            <h4 class="font-serif text-xl font-semibold text-ink">Inquiry Received</h4>
resources/views/partials/home/results.blade.php:47:        <span class="text-accent uppercase tracking-widest font-semibold">Representative Matriculations:</span>
resources/views/components/layouts/app.blade.php:102:            <span class="tracking-wider uppercase font-semibold text-accent">Admissions 2026/2027</span>
resources/views/components/layouts/app.blade.php:146:            <a href="#about" class="hover:text-ink hover:underline underline-offset-8 transition">About</a>
resources/views/components/layouts/app.blade.php:147:            <a href="#features" class="hover:text-ink hover:underline underline-offset-8 transition">Distinctives</a>
resources/views/components/layouts/app.blade.php:148:            <a href="#academics" class="hover:text-ink hover:underline underline-offset-8 transition">Curriculum</a>
resources/views/components/layouts/app.blade.php:149:            <a href="#stats" class="hover:text-ink hover:underline underline-offset-8 transition">Outcomes</a>
resources/views/components/layouts/app.blade.php:150:            <a href="#facilities" class="hover:text-ink hover:underline underline-offset-8 transition">Campus</a>
resources/views/components/layouts/app.blade.php:151:            <a href="#news" class="hover:text-ink hover:underline underline-offset-8 transition">Bulletin</a>
resources/views/components/layouts/app.blade.php:152:            <a href="#contact" class="hover:text-ink hover:underline underline-offset-8 transition">Contact</a>
resources/views/components/layouts/app.blade.php:162:                <span>Portals</span>
resources/views/components/layouts/app.blade.php:167:                <a href="{{ \App\Services\FrontendLibrary::get('student_portal_url', '/student/login') }}" class="block px-4 py-2 text-xs font-sans text-ink hover:bg-paper transition">Student Portal</a>
resources/views/components/layouts/app.blade.php:168:                <a href="{{ \App\Services\FrontendLibrary::get('staff_portal_url', '/teacher/login') }}" class="block px-4 py-2 text-xs font-sans text-ink hover:bg-paper transition">Faculty Portal</a>
resources/views/components/layouts/app.blade.php:169:                <a href="/admin/login" class="block px-4 py-2 text-xs font-sans text-ink hover:bg-paper transition">Administration</a>
resources/views/components/layouts/app.blade.php:198:          <a @click="mobileOpen = false" href="#about" class="py-1">About</a>
resources/views/components/layouts/app.blade.php:199:          <a @click="mobileOpen = false" href="#features" class="py-1">Distinctives</a>
resources/views/components/layouts/app.blade.php:200:          <a @click="mobileOpen = false" href="#academics" class="py-1">Curriculum</a>
resources/views/components/layouts/app.blade.php:201:          <a @click="mobileOpen = false" href="#stats" class="py-1">Outcomes</a>
resources/views/components/layouts/app.blade.php:202:          <a @click="mobileOpen = false" href="#facilities" class="py-1">Campus</a>
resources/views/components/layouts/app.blade.php:203:          <a @click="mobileOpen = false" href="#news" class="py-1">Bulletin</a>
resources/views/components/layouts/app.blade.php:204:          <a @click="mobileOpen = false" href="#contact" class="py-1">Contact</a>
resources/views/components/layouts/app.blade.php:208:            <a href="{{ \App\Services\FrontendLibrary::get('student_portal_url', '/student/login') }}" class="py-2 border border-rule text-ink">Student</a>
resources/views/components/layouts/app.blade.php:209:            <a href="{{ \App\Services\FrontendLibrary::get('staff_portal_url', '/teacher/login') }}" class="py-2 border border-rule text-ink">Faculty</a>
resources/views/components/layouts/app.blade.php:210:            <a href="/admin/login" class="py-2 border border-rule text-ink">Admin</a>
resources/views/components/layouts/app.blade.php:280:              <li><a href="#admissions" class="hover:text-accent transition">Entrance Examination Dates</a></li>
resources/views/components/layouts/app.blade.php:281:              <li><a href="#admissions" class="hover:text-accent transition">Tuition &amp; Scholarships</a></li>
resources/views/components/layouts/app.blade.php:282:              <li><a href="{{ \App\Services\FrontendLibrary::get('student_portal_url', '/student/login') }}" class="hover:text-accent transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-accent"></span>Student Portal</a></li>
resources/views/components/layouts/app.blade.php:283:              <li><a href="{{ \App\Services\FrontendLibrary::get('staff_portal_url', '/teacher/login') }}" class="hover:text-accent transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-accent"></span>Faculty Portal</a></li>
resources/views/components/layouts/app.blade.php:284:              <li><a href="/admin/login" class="hover:text-accent transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-accent"></span>Administration</a></li>
resources/views/components/layouts/app.blade.php:312:            <a href="#about" class="hover:text-[color:var(--ink-contrast)] transition">Privacy Policy</a>
resources/views/components/layouts/app.blade.php:313:            <a href="#about" class="hover:text-[color:var(--ink-contrast)] transition">Terms of Enrollment</a>
resources/views/components/layouts/app.blade.php:314:            <a href="#contact" class="hover:text-[color:var(--ink-contrast)] transition">Campus Directions</a>
```

### 2.2 Line Count
```text
$ grep -rn ">[A-Z]" resources/views/partials resources/views/components/site resources/views/components/layouts | wc -l
48
```
*(Note: Reduced from 49 to 48 after deleting the dead component `resources/views/components/site/hero.blade.php` which duplicated `partials/home/hero.blade.php`).*

### 2.3 Reconciliation Mapping (All 48 Lines)

| Grep Line # | File & Line | Grep Match Snippet | Classification | Target Content Key | Inventory Row # |
|---|---|---|---|---|---|
| 1 | `partials/home/hero.blade.php:67` | `<span>Scroll to explore prospectus</span>` | Content Key | `hero_scroll_label` | Row 45 |
| 2 | `partials/home/features.blade.php:31` | `<span>Review Full Curriculum</span>` | Content Key | `features_cta_text` | Row 58 |
| 3 | `partials/home/contact.blade.php:34` | `<span>Campus Address</span>` | Content Key | `contact_address_label` | Row 194 |
| 4 | `partials/home/contact.blade.php:40` | `<span>Telephone</span>` | Content Key | `contact_phone_label` | Row 195 |
| 5 | `partials/home/contact.blade.php:50` | `<span>Registry Email</span>` | Content Key | `contact_email_label` | Row 197 |
| 6 | `partials/home/contact.blade.php:60` | `<span>Admissions Hours</span>` | Content Key | `contact_visiting_hours_label` | Row 199 |
| 7 | `partials/home/contact.blade.php:82` | `<label>Parent / Guardian Name *</label>` | Content Key | `contact_form_name_label` | Row 203 |
| 8 | `partials/home/contact.blade.php:87` | `<label>Telephone Number *</label>` | Content Key | `contact_form_phone_label` | Row 204 |
| 9 | `partials/home/contact.blade.php:95` | `<label>Email Address *</label>` | Content Key | `contact_form_email_label` | Row 205 |
| 10 | `partials/home/contact.blade.php:100` | `<label>Class Level of Interest *</label>` | Content Key | `contact_form_grade_label` | Row 206 |
| 11 | `partials/home/contact.blade.php:102` | `<option value="">Select Candidate Grade</option>` | Content Key | `contact_form_grade_placeholder` | Row 207 |
| 12 | `partials/home/contact.blade.php:103` | `<option value="jss1">Junior Secondary 1 (Entry)</option>` | Content Key | `contact_form_classes[0].label` | Row 209 |
| 13 | `partials/home/contact.blade.php:104` | `<option value="jss2">Junior Secondary 2 (Transfer)</option>` | Content Key | `contact_form_classes[1].label` | Row 210 |
| 14 | `partials/home/contact.blade.php:105` | `<option value="sss1">Senior Secondary 1 (Sciences)</option>` | Content Key | `contact_form_classes[2].label` | Row 211 |
| 15 | `partials/home/contact.blade.php:106` | `<option value="sss1-arts">Senior Secondary 1 (Arts & Commercial)</option>` | Content Key | `contact_form_classes[3].label` | Row 212 |
| 16 | `partials/home/contact.blade.php:112` | `<label>Prospective Scholar Notes / Questions</label>` | Content Key | `contact_form_notes_label` | Row 213 |
| 17 | `partials/home/contact.blade.php:129` | `<h4>Inquiry Received</h4>` | Content Key | `contact_form_success_title` | Row 214 |
| 18 | `partials/home/results.blade.php:47` | `<span>Representative Matriculations:</span>` | Content Key | `stats_destinations_label` | Row 88 |
| 19 | `components/layouts/app.blade.php:102` | `<span>Admissions 2026/2027</span>` | Content Key | `topbar_badge` | Row 18 |
| 20 | `components/layouts/app.blade.php:146` | `<a href="#about">About</a>` | Content Key | `nav_about_label` (Desktop) | Row 20 |
| 21 | `components/layouts/app.blade.php:147` | `<a href="#features">Distinctives</a>` | Content Key | `nav_features_label` (Desktop) | Row 21 |
| 22 | `components/layouts/app.blade.php:148` | `<a href="#academics">Curriculum</a>` | Content Key | `nav_academics_label` (Desktop) | Row 22 |
| 23 | `components/layouts/app.blade.php:149` | `<a href="#stats">Outcomes</a>` | Content Key | `nav_stats_label` (Desktop) | Row 23 |
| 24 | `components/layouts/app.blade.php:150` | `<a href="#facilities">Campus</a>` | Content Key | `nav_facilities_label` (Desktop) | Row 24 |
| 25 | `components/layouts/app.blade.php:151` | `<a href="#news">Bulletin</a>` | Content Key | `nav_news_label` (Desktop) | Row 25 |
| 26 | `components/layouts/app.blade.php:152` | `<a href="#contact">Contact</a>` | Content Key | `nav_contact_label` (Desktop) | Row 26 |
| 27 | `components/layouts/app.blade.php:162` | `<span>Portals</span>` | Content Key | `nav_portals_label` | Row 27 |
| 28 | `components/layouts/app.blade.php:167` | `<a>Student Portal</a>` | Content Key | `portal_student_label` (Desktop) | Row 28 |
| 29 | `components/layouts/app.blade.php:168` | `<a>Faculty Portal</a>` | Content Key | `portal_staff_label` (Desktop) | Row 29 |
| 30 | `components/layouts/app.blade.php:169` | `<a>Administration</a>` | Content Key | `portal_admin_label` (Desktop) | Row 30 |
| 31 | `components/layouts/app.blade.php:198` | `<a href="#about">About</a>` | Content Key | `nav_about_label` (Mobile Drawer) | Row 20 |
| 32 | `components/layouts/app.blade.php:199` | `<a href="#features">Distinctives</a>` | Content Key | `nav_features_label` (Mobile Drawer) | Row 21 |
| 33 | `components/layouts/app.blade.php:200` | `<a href="#academics">Curriculum</a>` | Content Key | `nav_academics_label` (Mobile Drawer) | Row 22 |
| 34 | `components/layouts/app.blade.php:201` | `<a href="#stats">Outcomes</a>` | Content Key | `nav_stats_label` (Mobile Drawer) | Row 23 |
| 35 | `components/layouts/app.blade.php:202` | `<a href="#facilities">Campus</a>` | Content Key | `nav_facilities_label` (Mobile Drawer) | Row 24 |
| 36 | `components/layouts/app.blade.php:203` | `<a href="#news">Bulletin</a>` | Content Key | `nav_news_label` (Mobile Drawer) | Row 25 |
| 37 | `components/layouts/app.blade.php:204` | `<a href="#contact">Contact</a>` | Content Key | `nav_contact_label` (Mobile Drawer) | Row 26 |
| 38 | `components/layouts/app.blade.php:208` | `<a>Student</a>` | Content Key | `portal_student_label_short` (Mobile) | Row 31 |
| 39 | `components/layouts/app.blade.php:209` | `<a>Faculty</a>` | Content Key | `portal_staff_label_short` (Mobile) | Row 32 |
| 40 | `components/layouts/app.blade.php:210` | `<a>Admin</a>` | Content Key | `portal_admin_label_short` (Mobile) | Row 33 |
| 41 | `components/layouts/app.blade.php:280` | `<a>Entrance Examination Dates</a>` | Content Key | `footer_exam_link_label` | Row 222 |
| 42 | `components/layouts/app.blade.php:281` | `<a>Tuition & Scholarships</a>` | Content Key | `footer_tuition_link_label` | Row 224 |
| 43 | `components/layouts/app.blade.php:282` | `<a>Student Portal</a>` | Content Key | `portal_student_label` (Footer) | Row 28 |
| 44 | `components/layouts/app.blade.php:283` | `<a>Faculty Portal</a>` | Content Key | `portal_staff_label` (Footer) | Row 29 |
| 45 | `components/layouts/app.blade.php:284` | `<a>Administration</a>` | Content Key | `portal_admin_label` (Footer) | Row 30 |
| 46 | `components/layouts/app.blade.php:312` | `<a>Privacy Policy</a>` | Content Key | `footer_privacy_label` | Row 226 |
| 47 | `components/layouts/app.blade.php:313` | `<a>Terms of Enrollment</a>` | Content Key | `footer_terms_label` | Row 228 |
| 48 | `components/layouts/app.blade.php:314` | `<a>Campus Directions</a>` | Content Key | `footer_directions_label` | Row 230 |

---

## 3. Orphaned Keys (From Previous Design)

The previous website architecture stored keys under generic and outdated namespaces that are no longer used by the approved Ivy League Prospectus layout. These keys will be safely cleaned up and migrated during Phase 1B:

| Legacy Key | Previous Purpose | Modern Replacement / Status | Migration Strategy |
|---|---|---|---|
| `hero_tagline` | Small text above old hero | `hero_badge` | Replaced by `hero_badge`. |
| `hero_heading_highlight` | Split heading span | `hero_title` | Unified into single `hero_title`. |
| `hero_description` | Old hero body text | `hero_subtitle` | Replaced by `hero_subtitle`. |
| `hero_cta_primary` / `hero_cta_secondary` | Unlinked CTA labels | `hero_primary_cta_text` & `hero_secondary_cta_text` | Migrated with explicit link pairs. |
| `about_intro_welcome` | Generic welcome line | `about_eyebrow` | Replaced by `about_eyebrow`. |
| `about_intro_heading` | Old intro title | `about_heading` | Replaced by `about_heading`. |
| `about_intro_text` / `about_intro_mission` | Split about paragraphs | `about_body` | Unified into single `RichEditor` body. |
| `about_intro_read_more` | Hardcoded button label | Eliminated | Design uses seamless editorial flow. |
| `pillar_1_label` – `pillar_4_image` | 4 hardcoded square cards | `features_items` Repeater | Replaced by dynamic `features_items` Repeater. |
| `trust_1` – `trust_4` | Generic 4-pill trust bar | Eliminated | Replaced by 03 Examination Outcomes ink band. |
| `why_us_heading` / `why_us_subheading` | Old features title | `features_heading` / `features_intro` | Replaced by `features_heading` / `features_intro`. |
| `feature_1_title` – `feature_4_icon` | 4 hardcoded SVG feature cards | `features_items` Repeater | Replaced by dynamic `features_items` Repeater. |
| `bento_1_title` – `bento_3_description` | 3 legacy bento cards | Eliminated | Replaced by editorial table of contents layout. |
| `stat_1_value` – `stat_4_label` | Flat 4-stat fields without details | `stats_items` Repeater | Upgraded to Repeater with `value`, `label`, and `detail`. |
| `news_view_all_label` / `news_badge_label` | Hardcoded UI labels | Eliminated | Replaced by horizontal editorial rows. |
| `leadership_heading` / `leadership_subheading` | Old team grid section | `about_principal_name` & `about_principal_title` | Integrated into 01 About Head of School block. |
| `cta_heading` / `cta_description` / `cta_enrol` | Old final CTA banner | `admissions_cta_*` | Replaced by 4-step admissions protocol band. |
| `cta_whatsapp` | Old WhatsApp link button | `contact_additional_phones` | Integrated into contact registry phone desk. |

---

## 4. Mobile Viewport (390px) Constraints & Layout Fit

### 4.1 Mobile Navigation Drawer Fit Analysis
At `390px` width (standard modern iPhone viewport):
- **7 Navigation Links:** Rendered in a single column with `py-1` and `space-y-3` (`text-xs uppercase tracking-widest font-semibold text-ink`), consuming ~180px vertical height.
- **Portals Grid:** A 3-column button grid (`Student`, `Faculty`, `Admin`) with hairline borders and `py-2`, consuming 36px height. Buttons use abbreviated labels (`portal_student_label_short`, `portal_staff_label_short`, `portal_admin_label_short`) with a strict 10-character limit to fit side-by-side across 390px.
- **Primary CTA Button:** Full-width gold button (`py-3 text-xs uppercase tracking-widest font-bold`), consuming 44px height.
- **Top Bar Notice in Mobile Drawer:** Placed directly at the top of the mobile drawer as an editorial notice with gold bullet point (`text-[11px] text-body/80 border-b border-rule pb-3`), consuming ~32px.
- **Total Drawer Height:** ~330px total height, fitting comfortably within typical 667px–844px mobile screen heights without requiring scrolling.

### 4.2 Top Bar Handling
- On desktop (`lg`+), the top announcement bar displays the session badge (`Admissions 2026/2027`), announcement text (`Entrance examination and transfer enrollment now open.`), primary phone, and registry email in a sleek horizontal strip.
- On mobile (`<sm`), the secondary announcement text is hidden (`hidden sm:inline`) in the top bar to prevent awkward wrapping, and is instead surfaced prominently inside the mobile slide-out drawer so scholars on mobile never miss key admission updates.

---

## 5. Performance Architecture & Cache Invalidation

To guarantee sub-10ms response times and zero redundant queries on public pages:

1. **Static Request Memoization:** `FrontendLibrary` maintains private static in-memory arrays `$contents` and `$settings` for the lifecycle of each HTTP request. Multiple calls to `FrontendLibrary::get()` or `FrontendLibrary::getSetting()` resolve from memory in O(1) time without database queries.
2. **Tenant-Scoped Persistent Cache:** All contents and settings are cached indefinitely using `Cache::rememberForever("tenant_{$tenantId}_frontend_contents", ...)` and `Cache::rememberForever("tenant_{$tenantId}_settings", ...)`.
3. **Automated Cache Invalidation:** `FrontendContentObserver` and `SettingObserver` listen for `saved`, `updated`, and `deleted` model events to immediately purge the tenant's cache key upon any admin change.
4. **Target Query Count:** Exactly **2 queries** for website content on `/` (1 for `frontend_contents`, 1 for `settings`) on cold cache, and **0 database queries** on warm cache.
5. **Scope Enforcement:** Strictly touches website content tables only (`settings`, `frontend_contents`, `posts`, `gallery_images`, `staff`, `inquiries`, `contact_submissions`). Operational tables (`students`, `guardians`, `grades`, `fees`, `invoices`, `tenant_users`) are never queried or modified by the frontend library.
