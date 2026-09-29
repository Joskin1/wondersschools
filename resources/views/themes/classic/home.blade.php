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
@endphp

<div class="theme-classic bg-paper text-body">
    {{-- 1. Hero --}}
    @if($showHero)
        @include('themes.classic.hero')
    @endif

    {{-- 2. About Section --}}
    @if($showAbout)
        @include('themes.classic.about')
    @endif

    {{-- 3. Features / Distinctives --}}
    @if($showFeatures)
        @include('themes.classic.features')
    @endif

    {{-- 4. Academic Tracks / Curriculum --}}
    @if($showAcademics)
        @include('themes.classic.academics')
    @endif

    {{-- 5. Examination Results / Stats --}}
    @if($showResults)
        @include('themes.classic.results')
    @endif

    {{-- 6. Facilities & Campus Life --}}
    @if($showFacilities)
        @include('themes.classic.facilities')
    @endif

    {{-- 7. News & Announcements --}}
    @if($showNews)
        @include('themes.classic.news')
    @endif

    {{-- 8. Testimonials --}}
    @if($showTestimonials)
        @include('themes.classic.testimonials')
    @endif

    {{-- 9. Admissions Process --}}
    @if($showAdmissions)
        @include('themes.classic.admissions')
    @endif

    {{-- 10. Contact & Visit Us --}}
    @if($showContact)
        @include('themes.classic.contact')
    @endif
</div>
