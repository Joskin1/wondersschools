@php
    $heroTitle = \App\Services\FrontendLibrary::get('hero_title');
    $showHero = !empty($heroTitle);

    $aboutHeading = \App\Services\FrontendLibrary::get('about_heading');
    $aboutBody = \App\Services\FrontendLibrary::get('about_body');
    $showAbout = !empty($aboutHeading) || !empty($aboutBody);

    $featuresItems = \App\Services\FrontendLibrary::getJson('features_items', []);
    $showFeatures = !empty($featuresItems) && count($featuresItems) > 0;

    $statsItems = \App\Services\FrontendLibrary::getJson('stats_items', []);
    $statsDestinations = \App\Services\FrontendLibrary::getJson('stats_destinations', []);
    $showResults = (!empty($statsItems) && count($statsItems) > 0) || (!empty($statsDestinations) && count($statsDestinations) > 0);

    $academicsTracks = \App\Services\FrontendLibrary::getJson('academics_tracks', []);
    $showAcademics = !empty($academicsTracks) && count($academicsTracks) > 0;

    $facilitiesItems = \App\Services\FrontendLibrary::getJson('facilities_items', []);
    $showFacilities = !empty($facilitiesItems) && count($facilitiesItems) > 0;

    $newsArticles = \App\Services\FrontendLibrary::getJson('news_articles', []);
    $showNews = !empty($newsArticles) && count($newsArticles) > 0;

    $testimonialsItems = \App\Services\FrontendLibrary::getJson('testimonials_items', []);
    $showTestimonials = !empty($testimonialsItems) && count($testimonialsItems) > 0;

    $admissionsHeading = \App\Services\FrontendLibrary::get('admissions_cta_heading');
    $showAdmissions = !empty($admissionsHeading);

    $contactHeading = \App\Services\FrontendLibrary::get('contact_heading');
    $contactAddress = \App\Services\FrontendLibrary::get('contact_address');
    $showContact = !empty($contactHeading) || !empty($contactAddress);

    $sectionCounter = 1;
@endphp

<div class="theme-editorial">
    {{-- 1. Hero (Cover - not numbered) --}}
    @if($showHero)
        @include('themes.editorial.hero')
    @endif

    {{-- 2. 01 — About / Head of School (Asymmetric 12-col grid) --}}
    @if($showAbout)
        @include('themes.editorial.about', ['sectionNumber' => sprintf('%02d', $sectionCounter++)])
    @endif

    {{-- 3. 02 — Distinctives (Table of Contents editorial rows) --}}
    @if($showFeatures)
        @include('themes.editorial.features', ['sectionNumber' => sprintf('%02d', $sectionCounter++)])
    @endif

    {{-- 4. 03 — Examination Outcomes / Stats (Full-bleed ink band, 6xl gold numerals) --}}
    @if($showResults)
        @include('themes.editorial.results', ['sectionNumber' => sprintf('%02d', $sectionCounter++)])
    @endif

    {{-- 5. 04 — Curriculum & Programmes (Prospectus tracks) --}}
    @if($showAcademics)
        @include('themes.editorial.academics', ['sectionNumber' => sprintf('%02d', $sectionCounter++)])
    @endif

    {{-- 6. 05 — Campus Infrastructure (Uneven 2x2 first, 1x1 rest) --}}
    @if($showFacilities)
        @include('themes.editorial.facilities', ['sectionNumber' => sprintf('%02d', $sectionCounter++)])
    @endif

    {{-- 7. 06 — Bulletin & Announcements (Horizontal rows with left square thumbnail) --}}
    @if($showNews)
        @include('themes.editorial.news', ['sectionNumber' => sprintf('%02d', $sectionCounter++)])
    @endif

    {{-- 8. 07 — Voices / Testimonials (Editorial quote layout) --}}
    @if($showTestimonials)
        @include('themes.editorial.testimonials', ['sectionNumber' => sprintf('%02d', $sectionCounter++)])
    @endif

    {{-- 9. Admissions CTA (Full-bleed ink, centred headline) --}}
    @if($showAdmissions)
        @include('themes.editorial.admissions')
    @endif

    {{-- 10. 08 — Campus Visitation & Inquiry Form --}}
    @if($showContact)
        @include('themes.editorial.contact', ['sectionNumber' => sprintf('%02d', $sectionCounter++)])
    @endif
</div>
