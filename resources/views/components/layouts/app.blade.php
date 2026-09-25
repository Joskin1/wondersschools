@php
    $tenantName = function_exists('tenant') && tenant('name') ? tenant('name') : null;
    $tenantId = function_exists('tenant') && tenant('id') ? tenant('id') : null;
    $defaultSchoolName = $tenantName ?? 'Apex Crown College';
    $defaultShortName = $tenantName ? strtoupper(substr($tenantName, 0, 2)) : 'AC';
    $defaultEmail = $tenantId ? "admissions@{$tenantId}.edu.ng" : 'admissions@apexcrown.edu.ng';

    $schoolName = \App\Services\FrontendLibrary::getSetting('school_name', $defaultSchoolName);
    $schoolShortName = \App\Services\FrontendLibrary::getSetting('school_short_name', $defaultShortName);
    $schoolEstablished = \App\Services\FrontendLibrary::getSetting('school_established', '2001');
    $schoolPhone = \App\Services\FrontendLibrary::getSetting('school_phone', '+234 800 123 4567');
    $schoolEmail = \App\Services\FrontendLibrary::getSetting('school_email', $defaultEmail);
    $schoolAddress = \App\Services\FrontendLibrary::getSetting('school_address', 'Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos State, Nigeria');
    $schoolLogo = \App\Services\FrontendLibrary::getSetting('school_logo');

    $primaryColor = \App\Services\FrontendLibrary::getSetting('primary_color', '#0B2545');
    $secondaryColor = \App\Services\FrontendLibrary::getSetting('secondary_color', '#1e293b');
    $accentColor = \App\Services\FrontendLibrary::getSetting('accent_color', '#C8A951');

    $studentPortalUrl = \App\Services\FrontendLibrary::getSetting('student_portal_url', '/student/login');
    $staffPortalUrl = \App\Services\FrontendLibrary::getSetting('staff_portal_url', '/teacher/login');
    $adminPortalUrl = \App\Services\FrontendLibrary::getSetting('admin_portal_url', '/admin/login');

    $topbarBadge = \App\Services\FrontendLibrary::get('topbar_badge', 'Admissions 2026/2027');
    $topbarText = \App\Services\FrontendLibrary::get('topbar_text', 'Entrance examination and transfer enrollment now open.');

    $navAboutLabel = \App\Services\FrontendLibrary::get('nav_about_label', 'About');
    $navFeaturesLabel = \App\Services\FrontendLibrary::get('nav_features_label', 'Distinctives');
    $navAcademicsLabel = \App\Services\FrontendLibrary::get('nav_academics_label', 'Curriculum');
    $navStatsLabel = \App\Services\FrontendLibrary::get('nav_stats_label', 'Outcomes');
    $navFacilitiesLabel = \App\Services\FrontendLibrary::get('nav_facilities_label', 'Campus');
    $navNewsLabel = \App\Services\FrontendLibrary::get('nav_news_label', 'Bulletin');
    $navContactLabel = \App\Services\FrontendLibrary::get('nav_contact_label', 'Contact');

    $navPortalsLabel = \App\Services\FrontendLibrary::get('nav_portals_label', 'Portals');
    $portalStudentLabel = \App\Services\FrontendLibrary::get('portal_student_label', 'Student Portal');
    $portalStaffLabel = \App\Services\FrontendLibrary::get('portal_staff_label', 'Faculty Portal');
    $portalAdminLabel = \App\Services\FrontendLibrary::get('portal_admin_label', 'Administration');

    $portalStudentLabelShort = \App\Services\FrontendLibrary::get('portal_student_label_short', 'Student');
    $portalStaffLabelShort = \App\Services\FrontendLibrary::get('portal_staff_label_short', 'Faculty');
    $portalAdminLabelShort = \App\Services\FrontendLibrary::get('portal_admin_label_short', 'Admin');

    $headerCtaText = \App\Services\FrontendLibrary::get('header_cta_text', 'Admissions');
    $headerCtaLink = \App\Services\FrontendLibrary::get('header_cta_link', '#admissions');

    $footerEditionLabel = \App\Services\FrontendLibrary::get('footer_edition_label', 'Prospectus Edition');
    $footerDescription = \App\Services\FrontendLibrary::get('footer_description', 'An accredited British-Nigerian secondary school dedicated to academic brilliance, moral character, and global leadership.');
    $footerAccreditations = \App\Services\FrontendLibrary::get('footer_accreditations', 'Accredited by WAEC, NECO & Cambridge International.');
    $footerCol2Heading = \App\Services\FrontendLibrary::get('footer_col2_heading', 'Prospectus');
    $footerCol3Heading = \App\Services\FrontendLibrary::get('footer_col3_heading', 'Registry & Portals');
    $footerCol4Heading = \App\Services\FrontendLibrary::get('footer_col4_heading', 'Campus Registry');
    $footerExamLinkLabel = \App\Services\FrontendLibrary::get('footer_exam_link_label', 'Entrance Examination Dates');
    $footerExamLinkUrl = \App\Services\FrontendLibrary::get('footer_exam_link_url', '#admissions');
    $footerTuitionLinkLabel = \App\Services\FrontendLibrary::get('footer_tuition_link_label', 'Tuition & Scholarships');
    $footerTuitionLinkUrl = \App\Services\FrontendLibrary::get('footer_tuition_link_url', '#admissions');
    $footerPrivacyLabel = \App\Services\FrontendLibrary::get('footer_privacy_label', 'Privacy Policy');
    $footerPrivacyLink = \App\Services\FrontendLibrary::get('footer_privacy_link', '#about');
    $footerTermsLabel = \App\Services\FrontendLibrary::get('footer_terms_label', 'Terms of Enrollment');
    $footerTermsLink = \App\Services\FrontendLibrary::get('footer_terms_link', '#about');
    $footerDirectionsLabel = \App\Services\FrontendLibrary::get('footer_directions_label', 'Campus Directions');
    $footerDirectionsLink = \App\Services\FrontendLibrary::get('footer_directions_link', '#contact');

    $socialFacebook = \App\Services\FrontendLibrary::getSetting('footer_social_facebook');
    $socialInstagram = \App\Services\FrontendLibrary::getSetting('footer_social_instagram');
    $socialLinkedin = \App\Services\FrontendLibrary::getSetting('footer_social_linkedin');
    $socialX = \App\Services\FrontendLibrary::getSetting('footer_social_x');

    $faviconUrl = \App\Services\FrontendLibrary::imageUrl($schoolLogo, asset('favicon.ico'));
    $logoUrl = \App\Services\FrontendLibrary::imageUrl($schoolLogo);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? ($schoolName . ' | Admissions Prospectus 2026/2027') }}</title>
    <meta name="description" content="{{ $metaDescription ?? ('Official admissions prospectus of ' . $schoolName . '. A premier British-Nigerian secondary institution in Lagos.') }}">
    
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">

    <!-- Google Fonts: Fraunces & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,400;1,9..144,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
      :root {
        /* Tenant identity — the only three admin-supplied colours */
        --ink:     {{ $primaryColor }};
        --support: {{ $secondaryColor }};
        --accent:  {{ $accentColor }};

        /* Contrast safety */
        --ink-contrast:    {{ \App\Support\Color::contrastOn($primaryColor) }};
        --accent-contrast: {{ \App\Support\Color::contrastOn($accentColor) }};

        /* Derived — never admin-supplied */
        --accent-hover: color-mix(in srgb, var(--accent) 85%, black);
        --ink-hover:    color-mix(in srgb, var(--ink) 85%, black);

        /* Fixed neutrals — shared by every tenant, never admin-supplied */
        --paper: #FAF8F4;
        --rule:  #E5E0D8;
        --body:  #2D3748;

        /* Legacy aliases */
        --color-tenant-primary: {{ $primaryColor }};
        --color-tenant-secondary: {{ $secondaryColor }};
        --color-tenant-accent: {{ $accentColor }};
      }
      body {
        background-color: var(--paper);
        color: var(--body);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        font-size: 1.0625rem;
        line-height: 1.7;
      }
      h1, h2, h3, h4, .font-serif {
        font-family: 'Fraunces', 'Playfair Display', Georgia, serif;
      }
      .font-sans {
        font-family: 'Inter', sans-serif;
      }
      [x-cloak] { display: none !important; }
      
      @media (prefers-reduced-motion: no-preference) {
        .reveal-on-scroll {
          animation: fadeUp 0.4s ease-out forwards;
        }
      }
      @keyframes fadeUp {
        from {
          opacity: 0;
          transform: translateY(16px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
    </style>
</head>

<body class="bg-paper text-body antialiased selection:bg-accent selection:text-ink">

    <!-- ====== Prospectus Header / Navigation ====== -->
    <header x-data="{ scrolled: false, mobileOpen: false, portalsOpen: false }"
            @scroll.window="scrolled = (window.pageYOffset > 30)"
            :class="scrolled ? 'bg-paper/95 backdrop-blur-md shadow-sm border-b border-rule' : 'bg-paper border-b border-rule'"
            class="sticky top-0 z-50 transition-all duration-300">
      
      <!-- Top Academic Session Notice -->
      @if(!empty($topbarBadge) || !empty($topbarText))
        <div class="bg-ink text-paper text-[11px] sm:text-xs py-2 px-4 border-b border-ink/20">
          <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-1">
            <div class="flex items-center gap-2">
              <span class="inline-block w-1.5 h-1.5 rounded-full bg-accent"></span>
              @if(!empty($topbarBadge))
                <span class="tracking-wider uppercase font-semibold text-accent">{{ $topbarBadge }}</span>
              @endif
              @if(!empty($topbarText))
                <span class="text-white/80 hidden sm:inline">&mdash; {{ $topbarText }}</span>
              @endif
            </div>
            <div class="flex items-center gap-4 text-white/90">
              @if(!empty($schoolPhone))
                <a href="tel:{{ $schoolPhone }}" class="hover:text-accent transition">{{ $schoolPhone }}</a>
              @endif
              @if(!empty($schoolEmail))
                <span class="text-white/30 hidden sm:inline">|</span>
                <a href="mailto:{{ $schoolEmail }}" class="hover:text-accent transition hidden sm:inline">{{ $schoolEmail }}</a>
              @endif
            </div>
          </div>
        </div>
      @endif

      <!-- Main Navigation Bar -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between py-4 lg:py-5">
          
          <!-- Crest & School Name -->
          <a href="{{ route('home') }}" class="flex items-center gap-3.5 group">
            @if($logoUrl)
              <img src="{{ $logoUrl }}" alt="{{ $schoolName }}" class="w-10 h-10 object-contain border border-ink p-0.5 bg-paper" />
            @else
              <div class="w-10 h-10 border border-ink bg-ink text-accent flex items-center justify-center font-serif text-lg font-semibold tracking-wider">
                {{ $schoolShortName }}
              </div>
            @endif
            <div>
              <span class="block font-serif text-lg sm:text-xl font-semibold tracking-tight text-ink leading-none">
                {{ $schoolName }}
              </span>
              <span class="block text-[10px] sm:text-[11px] uppercase tracking-[0.2em] text-accent mt-1 font-sans font-medium">
                Lagos &bull; Est. {{ $schoolEstablished }}
              </span>
            </div>
          </a>

          <!-- Desktop Navigation Links -->
          @php
            $isHome = request()->routeIs('home');
            $aboutUrl = $isHome ? '#about' : route('about');
            $featuresUrl = $isHome ? '#features' : url('/#features');
            $academicsUrl = $isHome ? '#academics' : route('academics');
            $statsUrl = $isHome ? '#stats' : url('/#stats');
            $facilitiesUrl = $isHome ? '#facilities' : route('gallery');
            $newsUrl = $isHome ? '#news' : route('news');
            $contactUrl = $isHome ? '#contact' : route('contact');
            $admissionsUrl = $isHome ? '#admissions' : route('admissions');
          @endphp

          <nav class="hidden lg:flex items-center gap-7 text-xs font-sans font-medium tracking-wider uppercase text-ink/80">
            <a href="{{ $aboutUrl }}" class="{{ request()->routeIs('about') ? 'text-accent font-bold border-b-2 border-accent pb-0.5' : 'hover:text-ink hover:underline underline-offset-8 transition' }}">{{ $navAboutLabel }}</a>
            <a href="{{ $featuresUrl }}" class="hover:text-ink hover:underline underline-offset-8 transition">{{ $navFeaturesLabel }}</a>
            <a href="{{ $academicsUrl }}" class="{{ request()->routeIs('academics') ? 'text-accent font-bold border-b-2 border-accent pb-0.5' : 'hover:text-ink hover:underline underline-offset-8 transition' }}">{{ $navAcademicsLabel }}</a>
            <a href="{{ $statsUrl }}" class="hover:text-ink hover:underline underline-offset-8 transition">{{ $navStatsLabel }}</a>
            <a href="{{ $facilitiesUrl }}" class="{{ request()->routeIs('gallery') ? 'text-accent font-bold border-b-2 border-accent pb-0.5' : 'hover:text-ink hover:underline underline-offset-8 transition' }}">{{ $navFacilitiesLabel }}</a>
            <a href="{{ $newsUrl }}" class="{{ request()->routeIs('news*') ? 'text-accent font-bold border-b-2 border-accent pb-0.5' : 'hover:text-ink hover:underline underline-offset-8 transition' }}">{{ $navNewsLabel }}</a>
            <a href="{{ $contactUrl }}" class="{{ request()->routeIs('contact') ? 'text-accent font-bold border-b-2 border-accent pb-0.5' : 'hover:text-ink hover:underline underline-offset-8 transition' }}">{{ $navContactLabel }}</a>
          </nav>

          <!-- Action Buttons -->
          <div class="hidden sm:flex items-center gap-3">
            <!-- Portals Dropdown -->
            <div class="relative" @click.outside="portalsOpen = false">
              <button @click="portalsOpen = !portalsOpen"
                      type="button"
                      class="px-4 py-2.5 text-xs font-sans uppercase tracking-wider font-semibold border border-ink/30 text-ink hover:border-ink transition flex items-center gap-1.5 rounded-none">
                <span>{{ $navPortalsLabel }}</span>
                <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
              </button>
              <div x-show="portalsOpen" x-cloak
                   class="absolute right-0 mt-1 w-48 bg-white border border-rule shadow-md py-1 z-50 rounded-none">
                <a href="{{ $studentPortalUrl }}" class="block px-4 py-2 text-xs font-sans text-ink hover:bg-paper transition">{{ $portalStudentLabel }}</a>
                <a href="{{ $staffPortalUrl }}" class="block px-4 py-2 text-xs font-sans text-ink hover:bg-paper transition">{{ $portalStaffLabel }}</a>
                <a href="{{ $adminPortalUrl }}" class="block px-4 py-2 text-xs font-sans text-ink hover:bg-paper transition">{{ $portalAdminLabel }}</a>
              </div>
            </div>

            <!-- Primary CTA -->
            @if(!empty($headerCtaText))
              <a href="{{ $isHome ? $headerCtaLink : route('admissions') }}"
                 class="px-6 py-2.5 text-xs font-sans uppercase tracking-widest font-semibold bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition rounded-none">
                {{ $headerCtaText }}
              </a>
            @endif
          </div>

          <!-- Mobile Menu Toggle -->
          <button @click="mobileOpen = !mobileOpen"
                  type="button"
                  class="lg:hidden p-2 text-ink focus:outline-none"
                  aria-label="Toggle Menu">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
              <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>

        </div>
      </div>

      <!-- Mobile Drawer -->
      <div x-show="mobileOpen" x-cloak
           class="lg:hidden bg-paper border-b border-rule px-6 py-6 space-y-4">
        @if(!empty($topbarText))
          <div class="text-[11px] text-body/80 border-b border-rule pb-3 flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-accent flex-shrink-0"></span>
            <span>{{ $topbarText }}</span>
          </div>
        @endif
        <nav class="flex flex-col space-y-3 text-xs uppercase tracking-widest text-ink font-semibold">
          <a @click="mobileOpen = false" href="{{ $aboutUrl }}" class="py-1 {{ request()->routeIs('about') ? 'text-accent font-bold' : '' }}">{{ $navAboutLabel }}</a>
          <a @click="mobileOpen = false" href="{{ $featuresUrl }}" class="py-1">{{ $navFeaturesLabel }}</a>
          <a @click="mobileOpen = false" href="{{ $academicsUrl }}" class="py-1 {{ request()->routeIs('academics') ? 'text-accent font-bold' : '' }}">{{ $navAcademicsLabel }}</a>
          <a @click="mobileOpen = false" href="{{ $statsUrl }}" class="py-1">{{ $navStatsLabel }}</a>
          <a @click="mobileOpen = false" href="{{ $facilitiesUrl }}" class="py-1 {{ request()->routeIs('gallery') ? 'text-accent font-bold' : '' }}">{{ $navFacilitiesLabel }}</a>
          <a @click="mobileOpen = false" href="{{ $newsUrl }}" class="py-1 {{ request()->routeIs('news*') ? 'text-accent font-bold' : '' }}">{{ $navNewsLabel }}</a>
          <a @click="mobileOpen = false" href="{{ $contactUrl }}" class="py-1 {{ request()->routeIs('contact') ? 'text-accent font-bold' : '' }}">{{ $navContactLabel }}</a>
        </nav>
        <div class="pt-4 border-t border-rule flex flex-col gap-2">
          <div class="grid grid-cols-3 gap-2 text-center text-[11px] uppercase tracking-wider font-semibold">
            <a href="{{ $studentPortalUrl }}" class="py-2 border border-rule text-ink">{{ $portalStudentLabelShort }}</a>
            <a href="{{ $staffPortalUrl }}" class="py-2 border border-rule text-ink">{{ $portalStaffLabelShort }}</a>
            <a href="{{ $adminPortalUrl }}" class="py-2 border border-rule text-ink">{{ $portalAdminLabelShort }}</a>
          </div>
          @if(!empty($headerCtaText))
            <a @click="mobileOpen = false" href="{{ $isHome ? $headerCtaLink : route('admissions') }}" class="w-full text-center py-3 bg-accent text-[color:var(--accent-contrast)] text-xs uppercase tracking-widest font-bold">
              {{ $headerCtaText }}
            </a>
          @endif
        </div>
      </div>
    </header>

    <!-- Page Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- ====== Prospectus Footer (Ink Background, Hairline Column Borders) ====== -->
    <footer class="bg-ink text-paper pt-20 pb-12 border-t border-accent/30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- 4 Columns with Hairline Rules Between Them -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 border-b border-white/10 pb-16">
          
          <!-- Col 1: Crest & Mission -->
          <div class="lg:pr-8 lg:border-r border-white/10 space-y-4 pb-8 lg:pb-0">
            <div class="flex items-center gap-3">
              @if(!empty($logoUrl))
                <img src="{{ $logoUrl }}" alt="{{ $schoolName }}" class="w-10 h-10 object-contain border border-accent p-0.5 bg-white/10" />
              @else
                <div class="w-10 h-10 border border-accent bg-transparent text-accent flex items-center justify-center font-serif text-lg font-semibold tracking-wider">
                  {{ $schoolShortName }}
                </div>
              @endif
              <div>
                <span class="block font-serif text-base font-semibold tracking-tight text-[color:var(--ink-contrast)] leading-none">
                  {{ $schoolName }}
                </span>
                <span class="block text-[10px] uppercase tracking-[0.2em] text-accent mt-1 font-sans">
                  {{ $footerEditionLabel }}
                </span>
              </div>
            </div>

            <p class="text-xs text-[color:var(--ink-contrast)]/70 font-sans leading-[1.7] max-w-xs">
              {{ $footerDescription }}
            </p>

            @if(!empty($footerAccreditations))
              <p class="text-[11px] text-accent font-sans">
                {{ $footerAccreditations }}
              </p>
            @endif

            @if(!empty($socialFacebook) || !empty($socialInstagram) || !empty($socialLinkedin) || !empty($socialX))
              <div class="pt-2 flex items-center gap-3 text-white/70">
                @if(!empty($socialFacebook))
                  <a href="{{ $socialFacebook }}" target="_blank" rel="noopener noreferrer" class="hover:text-accent transition text-xs font-sans uppercase tracking-wider">Facebook</a>
                @endif
                @if(!empty($socialInstagram))
                  <a href="{{ $socialInstagram }}" target="_blank" rel="noopener noreferrer" class="hover:text-accent transition text-xs font-sans uppercase tracking-wider">Instagram</a>
                @endif
                @if(!empty($socialLinkedin))
                  <a href="{{ $socialLinkedin }}" target="_blank" rel="noopener noreferrer" class="hover:text-accent transition text-xs font-sans uppercase tracking-wider">LinkedIn</a>
                @endif
                @if(!empty($socialX))
                  <a href="{{ $socialX }}" target="_blank" rel="noopener noreferrer" class="hover:text-accent transition text-xs font-sans uppercase tracking-wider">X</a>
                @endif
              </div>
            @endif
          </div>

          <!-- Col 2: Prospectus Chapters -->
          @if(!empty($navAboutLabel) || !empty($navFeaturesLabel) || !empty($navStatsLabel) || !empty($navAcademicsLabel) || !empty($navFacilitiesLabel))
            <div class="lg:px-8 lg:border-r border-white/10 space-y-4 py-8 lg:py-0 border-t md:border-t-0 border-white/10">
              @if(!empty($footerCol2Heading))
                <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent block">
                  {{ $footerCol2Heading }}
                </span>
              @endif
              <ul class="space-y-2.5 text-xs font-sans text-[color:var(--ink-contrast)]/80">
                @if(!empty($navAboutLabel))<li><a href="#about" class="hover:text-accent transition">01 &mdash; {{ $navAboutLabel }}</a></li>@endif
                @if(!empty($navFeaturesLabel))<li><a href="#features" class="hover:text-accent transition">02 &mdash; {{ $navFeaturesLabel }}</a></li>@endif
                @if(!empty($navStatsLabel))<li><a href="#stats" class="hover:text-accent transition">03 &mdash; {{ $navStatsLabel }}</a></li>@endif
                @if(!empty($navAcademicsLabel))<li><a href="#academics" class="hover:text-accent transition">04 &mdash; {{ $navAcademicsLabel }}</a></li>@endif
                @if(!empty($navFacilitiesLabel))<li><a href="#facilities" class="hover:text-accent transition">05 &mdash; {{ $navFacilitiesLabel }}</a></li>@endif
              </ul>
            </div>
          @endif

          <!-- Col 3: Admissions & Registry -->
          @if(!empty($footerExamLinkLabel) || !empty($footerTuitionLinkLabel) || !empty($portalStudentLabel) || !empty($portalStaffLabel) || !empty($portalAdminLabel))
            <div class="lg:px-8 lg:border-r border-white/10 space-y-4 py-8 lg:py-0 border-t md:border-t-0 border-white/10">
              @if(!empty($footerCol3Heading))
                <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent block">
                  {{ $footerCol3Heading }}
                </span>
              @endif
              <ul class="space-y-2.5 text-xs font-sans text-[color:var(--ink-contrast)]/80">
                @if(!empty($footerExamLinkLabel))<li><a href="{{ $footerExamLinkUrl }}" class="hover:text-accent transition">{{ $footerExamLinkLabel }}</a></li>@endif
                @if(!empty($footerTuitionLinkLabel))<li><a href="{{ $footerTuitionLinkUrl }}" class="hover:text-accent transition">{{ $footerTuitionLinkLabel }}</a></li>@endif
                @if(!empty($portalStudentLabel))<li><a href="{{ $studentPortalUrl }}" class="hover:text-accent transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-accent"></span>{{ $portalStudentLabel }}</a></li>@endif
                @if(!empty($portalStaffLabel))<li><a href="{{ $staffPortalUrl }}" class="hover:text-accent transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-accent"></span>{{ $portalStaffLabel }}</a></li>@endif
                @if(!empty($portalAdminLabel))<li><a href="{{ $adminPortalUrl }}" class="hover:text-accent transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-accent"></span>{{ $portalAdminLabel }}</a></li>@endif
              </ul>
            </div>
          @endif

          <!-- Col 4: Dispatch & Location -->
          @if(!empty($schoolAddress) || !empty($schoolPhone) || !empty($schoolEmail))
            <div class="lg:pl-8 space-y-4 pt-8 lg:pt-0 border-t md:border-t-0 border-white/10">
              @if(!empty($footerCol4Heading))
                <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent block">
                  {{ $footerCol4Heading }}
                </span>
              @endif
              @if(!empty($schoolAddress))
                <p class="text-xs text-[color:var(--ink-contrast)]/80 font-sans leading-relaxed">
                  {{ $schoolAddress }}
                </p>
              @endif
              @if(!empty($schoolPhone))
                <p class="text-xs text-[color:var(--ink-contrast)]/80 font-sans">
                  Tel: {{ $schoolPhone }}
                </p>
              @endif
              @if(!empty($schoolEmail))
                <p class="text-xs text-[color:var(--ink-contrast)]/80 font-sans">
                  Email: {{ $schoolEmail }}
                </p>
              @endif
            </div>
          @endif

        </div>

        <!-- Bottom Copyright & Legal Bar -->
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-sans text-[color:var(--ink-contrast)]/60">
          <div>
            &copy; {{ date('Y') }} {{ $schoolName }}. All Rights Reserved.
          </div>
          <div class="flex items-center gap-6">
            @if(!empty($footerPrivacyLabel))<a href="{{ $footerPrivacyLink }}" class="hover:text-[color:var(--ink-contrast)] transition">{{ $footerPrivacyLabel }}</a>@endif
            @if(!empty($footerTermsLabel))<a href="{{ $footerTermsLink }}" class="hover:text-[color:var(--ink-contrast)] transition">{{ $footerTermsLabel }}</a>@endif
            @if(!empty($footerDirectionsLabel))<a href="{{ $footerDirectionsLink }}" class="hover:text-[color:var(--ink-contrast)] transition">{{ $footerDirectionsLabel }}</a>@endif
          </div>
        </div>

      </div>
    </footer>

</body>
</html>
