<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? (($school['name'] ?? \App\Services\FrontendLibrary::getSetting('school_name', 'Apex Crown College')) . ' | Admissions Prospectus 2026/2027') }}</title>
    <meta name="description" content="{{ $metaDescription ?? ('Official admissions prospectus of ' . ($school['name'] ?? \App\Services\FrontendLibrary::getSetting('school_name', 'Apex Crown College')) . '. A premier British-Nigerian secondary institution in Lagos.') }}">
    
    @php
        $schoolLogo = \App\Services\FrontendLibrary::getSetting('school_logo');
        $faviconUrl = $schoolLogo 
            ? \Illuminate\Support\Facades\Storage::disk(config('filesystems.upload_disk', 'public'))->url($schoolLogo) 
            : asset('favicon.ico');
    @endphp
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">

    <!-- Google Fonts: Fraunces & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;0,9..144,700;1,9..144,400;1,9..144,600&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
      :root {
        @php
          $primaryColor = \App\Services\FrontendLibrary::getSetting('primary_color', '#0B2545');
          $secondaryColor = \App\Services\FrontendLibrary::getSetting('secondary_color', '#1e293b');
          $accentColor = \App\Services\FrontendLibrary::getSetting('accent_color', '#C8A951');
        @endphp
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
      <div class="bg-ink text-paper text-[11px] sm:text-xs py-2 px-4 border-b border-ink/20">
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-1">
          <div class="flex items-center gap-2">
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-accent"></span>
            <span class="tracking-wider uppercase font-semibold text-accent">Admissions 2026/2027</span>
            <span class="text-white/80 hidden sm:inline">&mdash; Entrance examination and transfer enrollment now open.</span>
          </div>
          <div class="flex items-center gap-4 text-white/90">
            @if(!empty($school['contact_phone'] ?? '+234 800 123 4567'))
              <a href="tel:{{ $school['contact_phone'] ?? '+234 800 123 4567' }}" class="hover:text-accent transition">{{ $school['contact_phone'] ?? '+234 800 123 4567' }}</a>
            @endif
            @if(!empty($school['contact_email'] ?? 'admissions@apexcrown.edu.ng'))
              <span class="text-white/30 hidden sm:inline">|</span>
              <a href="mailto:{{ $school['contact_email'] ?? 'admissions@apexcrown.edu.ng' }}" class="hover:text-accent transition hidden sm:inline">{{ $school['contact_email'] ?? 'admissions@apexcrown.edu.ng' }}</a>
            @endif
          </div>
        </div>
      </div>

      <!-- Main Navigation Bar -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between py-4 lg:py-5">
          
          <!-- Crest & School Name -->
          <a href="{{ route('home') }}" class="flex items-center gap-3.5 group">
            @php
              $logoUrl = $schoolLogo ? \Illuminate\Support\Facades\Storage::disk(config('filesystems.upload_disk', 'public'))->url($schoolLogo) : null;
              $shortName = $school['short_name'] ?? 'AC';
            @endphp
            @if($logoUrl)
              <img src="{{ $logoUrl }}" alt="{{ $school['name'] ?? 'Apex Crown College' }}" class="w-10 h-10 object-contain border border-ink p-0.5 bg-paper" />
            @else
              <div class="w-10 h-10 border border-ink bg-ink text-accent flex items-center justify-center font-serif text-lg font-semibold tracking-wider">
                {{ $shortName }}
              </div>
            @endif
            <div>
              <span class="block font-serif text-lg sm:text-xl font-semibold tracking-tight text-ink leading-none">
                {{ $school['name'] ?? 'Apex Crown College' }}
              </span>
              <span class="block text-[10px] sm:text-[11px] uppercase tracking-[0.2em] text-accent mt-1 font-sans font-medium">
                Lagos &bull; Est. {{ $school['established'] ?? '2001' }}
              </span>
            </div>
          </a>

          <!-- Desktop Navigation Links -->
          <nav class="hidden lg:flex items-center gap-8 text-xs font-sans font-medium tracking-wider uppercase text-ink/80">
            <a href="#about" class="hover:text-ink hover:underline underline-offset-8 transition">About</a>
            <a href="#features" class="hover:text-ink hover:underline underline-offset-8 transition">Distinctives</a>
            <a href="#academics" class="hover:text-ink hover:underline underline-offset-8 transition">Curriculum</a>
            <a href="#stats" class="hover:text-ink hover:underline underline-offset-8 transition">Outcomes</a>
            <a href="#facilities" class="hover:text-ink hover:underline underline-offset-8 transition">Campus</a>
            <a href="#news" class="hover:text-ink hover:underline underline-offset-8 transition">Bulletin</a>
            <a href="#contact" class="hover:text-ink hover:underline underline-offset-8 transition">Contact</a>
          </nav>

          <!-- Action Buttons -->
          <div class="hidden sm:flex items-center gap-3">
            <!-- Portals Dropdown -->
            <div class="relative" @click.outside="portalsOpen = false">
              <button @click="portalsOpen = !portalsOpen"
                      type="button"
                      class="px-4 py-2.5 text-xs font-sans uppercase tracking-wider font-semibold border border-ink/30 text-ink hover:border-ink transition flex items-center gap-1.5 rounded-none">
                <span>Portals</span>
                <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
              </button>
              <div x-show="portalsOpen" x-cloak
                   class="absolute right-0 mt-1 w-48 bg-white border border-rule shadow-md py-1 z-50 rounded-none">
                <a href="{{ \App\Services\FrontendLibrary::get('student_portal_url', '/student/login') }}" class="block px-4 py-2 text-xs font-sans text-ink hover:bg-paper transition">Student Portal</a>
                <a href="{{ \App\Services\FrontendLibrary::get('staff_portal_url', '/teacher/login') }}" class="block px-4 py-2 text-xs font-sans text-ink hover:bg-paper transition">Faculty Portal</a>
                <a href="/admin/login" class="block px-4 py-2 text-xs font-sans text-ink hover:bg-paper transition">Administration</a>
              </div>
            </div>

            <!-- Primary CTA -->
            <a href="#admissions"
               class="px-6 py-2.5 text-xs font-sans uppercase tracking-widest font-semibold bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition rounded-none">
              Admissions
            </a>
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
        <nav class="flex flex-col space-y-3 text-xs uppercase tracking-widest text-ink font-semibold">
          <a @click="mobileOpen = false" href="#about" class="py-1">About</a>
          <a @click="mobileOpen = false" href="#features" class="py-1">Distinctives</a>
          <a @click="mobileOpen = false" href="#academics" class="py-1">Curriculum</a>
          <a @click="mobileOpen = false" href="#stats" class="py-1">Outcomes</a>
          <a @click="mobileOpen = false" href="#facilities" class="py-1">Campus</a>
          <a @click="mobileOpen = false" href="#news" class="py-1">Bulletin</a>
          <a @click="mobileOpen = false" href="#contact" class="py-1">Contact</a>
        </nav>
        <div class="pt-4 border-t border-rule flex flex-col gap-2">
          <div class="grid grid-cols-3 gap-2 text-center text-[11px] uppercase tracking-wider font-semibold">
            <a href="{{ \App\Services\FrontendLibrary::get('student_portal_url', '/student/login') }}" class="py-2 border border-rule text-ink">Student</a>
            <a href="{{ \App\Services\FrontendLibrary::get('staff_portal_url', '/teacher/login') }}" class="py-2 border border-rule text-ink">Faculty</a>
            <a href="/admin/login" class="py-2 border border-rule text-ink">Admin</a>
          </div>
          <a @click="mobileOpen = false" href="#admissions" class="w-full text-center py-3 bg-accent text-[color:var(--accent-contrast)] text-xs uppercase tracking-widest font-bold">
            Apply for Admission
          </a>
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
                <img src="{{ $logoUrl }}" alt="{{ $school['name'] ?? 'Apex Crown College' }}" class="w-10 h-10 object-contain border border-accent p-0.5 bg-white/10" />
              @else
                <div class="w-10 h-10 border border-accent bg-transparent text-accent flex items-center justify-center font-serif text-lg font-semibold tracking-wider">
                  {{ $school['short_name'] ?? 'AC' }}
                </div>
              @endif
              <div>
                <span class="block font-serif text-base font-semibold tracking-tight text-[color:var(--ink-contrast)] leading-none">
                  {{ $school['name'] ?? 'Apex Crown College' }}
                </span>
                <span class="block text-[10px] uppercase tracking-[0.2em] text-accent mt-1 font-sans">
                  Prospectus Edition
                </span>
              </div>
            </div>

            <p class="text-xs text-[color:var(--ink-contrast)]/70 font-sans leading-[1.7] max-w-xs">
              {{ $school['footer']['description'] ?? 'An accredited British-Nigerian secondary school dedicated to academic brilliance, moral character, and global leadership.' }}
            </p>

            <p class="text-[11px] text-accent font-sans">
              {{ $school['footer']['accreditations'] ?? 'Accredited by WAEC, NECO & Cambridge International.' }}
            </p>
          </div>

          <!-- Col 2: Prospectus Chapters -->
          <div class="lg:px-8 lg:border-r border-white/10 space-y-4 py-8 lg:py-0 border-t md:border-t-0 border-white/10">
            <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent block">
              Prospectus
            </span>
            <ul class="space-y-2.5 text-xs font-sans text-[color:var(--ink-contrast)]/80">
              <li><a href="#about" class="hover:text-accent transition">01 &mdash; About the College</a></li>
              <li><a href="#features" class="hover:text-accent transition">02 &mdash; Distinctives &amp; Mentorship</a></li>
              <li><a href="#stats" class="hover:text-accent transition">03 &mdash; Examination Outcomes</a></li>
              <li><a href="#academics" class="hover:text-accent transition">04 &mdash; Curriculum Tracks</a></li>
              <li><a href="#facilities" class="hover:text-accent transition">05 &mdash; Campus Infrastructure</a></li>
            </ul>
          </div>

          <!-- Col 3: Admissions & Registry -->
          <div class="lg:px-8 lg:border-r border-white/10 space-y-4 py-8 lg:py-0 border-t md:border-t-0 border-white/10">
            <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent block">
              Registry &amp; Portals
            </span>
            <ul class="space-y-2.5 text-xs font-sans text-[color:var(--ink-contrast)]/80">
              <li><a href="#admissions" class="hover:text-accent transition">Entrance Examination Dates</a></li>
              <li><a href="#admissions" class="hover:text-accent transition">Tuition &amp; Scholarships</a></li>
              <li><a href="{{ \App\Services\FrontendLibrary::get('student_portal_url', '/student/login') }}" class="hover:text-accent transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-accent"></span>Student Portal</a></li>
              <li><a href="{{ \App\Services\FrontendLibrary::get('staff_portal_url', '/teacher/login') }}" class="hover:text-accent transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-accent"></span>Faculty Portal</a></li>
              <li><a href="/admin/login" class="hover:text-accent transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-accent"></span>Administration</a></li>
            </ul>
          </div>

          <!-- Col 4: Dispatch & Location -->
          <div class="lg:pl-8 space-y-4 pt-8 lg:pt-0 border-t md:border-t-0 border-white/10">
            <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent block">
              Campus Registry
            </span>
            <p class="text-xs text-[color:var(--ink-contrast)]/80 font-sans leading-relaxed">
              {{ $school['location'] ?? 'Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos State, Nigeria' }}
            </p>
            <p class="text-xs text-[color:var(--ink-contrast)]/80 font-sans">
              Tel: {{ $school['contact_phone'] ?? '+234 800 123 4567' }}
            </p>
            <p class="text-xs text-[color:var(--ink-contrast)]/80 font-sans">
              Email: {{ $school['contact_email'] ?? 'admissions@apexcrown.edu.ng' }}
            </p>
          </div>

        </div>

        <!-- Bottom Copyright & Legal Bar -->
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-sans text-[color:var(--ink-contrast)]/60">
          <div>
            &copy; {{ date('Y') }} {{ $school['name'] ?? 'Apex Crown College' }}. All Rights Reserved.
          </div>
          <div class="flex items-center gap-6">
            <a href="#about" class="hover:text-[color:var(--ink-contrast)] transition">Privacy Policy</a>
            <a href="#about" class="hover:text-[color:var(--ink-contrast)] transition">Terms of Enrollment</a>
            <a href="#contact" class="hover:text-[color:var(--ink-contrast)] transition">Campus Directions</a>
          </div>
        </div>

      </div>
    </footer>

</body>
</html>
