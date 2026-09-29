@php
    $heroImagesRaw = \App\Services\FrontendLibrary::getJson('hero_slider_images', []);
    if (empty($heroImagesRaw)) {
        $singleHero = \App\Services\FrontendLibrary::get('hero_image', 'frontend/beta_hero.jpg');
        $heroImagesRaw = [$singleHero];
    }
    
    $heroSlides = [];
    foreach ($heroImagesRaw as $img) {
        $heroSlides[] = \App\Services\FrontendLibrary::imageUrl($img);
    }

    $schoolName = \App\Services\FrontendLibrary::getSetting('school_name', 'Cathedral College');
    $schoolMotto = \App\Services\FrontendLibrary::getSetting('school_motto', 'Knowledge, Character and the Fear of God');
    $schoolEst = \App\Services\FrontendLibrary::getSetting('school_established', '1998');
    $heroImageAlt = \App\Services\FrontendLibrary::get('hero_image_alt', $schoolName);
    $heroBadge = \App\Services\FrontendLibrary::get('hero_badge', 'Official Admissions Prospectus 2026/2027');
    $heroTitle = \App\Services\FrontendLibrary::get('hero_title', 'Nurturing Intellectual Depth & Moral Leadership');
    $heroSubtitle = \App\Services\FrontendLibrary::get('hero_subtitle', 'An accredited British-Nigerian secondary institution committed to scholastic rigor, scientific inquiry, and the formation of character.');
    $heroPrimaryCtaText = \App\Services\FrontendLibrary::get('hero_primary_cta_text', 'Apply for Admission');
    $heroPrimaryCtaLink = \App\Services\FrontendLibrary::get('hero_primary_cta_link', '#admissions');
    $heroSecondaryCtaText = \App\Services\FrontendLibrary::get('hero_secondary_cta_text', 'Explore Curriculum');
    $heroSecondaryCtaLink = \App\Services\FrontendLibrary::get('hero_secondary_cta_link', '#academics');
@endphp

<!-- ====== Classic Theme Hero Section (Centered Stately Academy) ====== -->
<section id="home"
         x-data="{
             currentSlide: 0,
             slides: {{ json_encode($heroSlides) }},
             init() {
                 if (this.slides.length > 1) {
                     setInterval(() => {
                         this.currentSlide = (this.currentSlide + 1) % this.slides.length;
                     }, 5000);
                 }
             }
         }"
         class="relative min-h-[92vh] flex flex-col justify-between overflow-hidden bg-ink text-[color:var(--ink-contrast)]">
    
    <!-- Background Slides with Centered Radial Mask Overlay -->
    <div class="absolute inset-0 z-0 overflow-hidden">
        @if(count($heroSlides) > 1)
            @foreach($heroSlides as $idx => $slide)
                <div x-show="currentSlide === {{ $idx }}"
                     x-transition:enter="transition ease-out duration-1000"
                     x-transition:enter-start="opacity-0 scale-105"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-1000"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute inset-0 w-full h-full">
                    <img src="{{ $slide }}"
                         alt="{{ $heroImageAlt }} - Slide {{ $idx + 1 }}"
                         class="w-full h-full object-cover object-center" />
                </div>
            @endforeach
        @else
            <img src="{{ $heroSlides[0] ?? '' }}"
                 alt="{{ $heroImageAlt }}"
                 class="w-full h-full object-cover object-center" />
        @endif

        <!-- Symmetrical Dark Vignette & Color Filter Overlay -->
        <div class="absolute inset-0 bg-ink/80 mix-blend-multiply pointer-events-none"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/60 to-ink/75 pointer-events-none"></div>
    </div>

    <!-- Top Spacer -->
    <div></div>

    <!-- Centered Content -->
    <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center space-y-6">
        
        <!-- Crest Emblem & Motto Badge -->
        <div class="flex flex-col items-center justify-center space-y-3">
            <div class="w-16 h-16 rounded-full border-2 border-accent bg-ink text-accent flex items-center justify-center font-serif text-xl font-bold shadow-xl">
                {{ substr($schoolName, 0, 2) }}
            </div>
            
            @if(!empty($schoolMotto))
                <div class="inline-block px-3 py-1 border border-accent/40 bg-accent/10 text-accent font-serif italic text-[10px] sm:text-xs tracking-wider uppercase text-center max-w-[280px] sm:max-w-xl mx-auto leading-normal break-words">
                    “{{ $schoolMotto }}”
                </div>
            @endif
        </div>

        <!-- Eyebrow Badge -->
        @if(!empty($heroBadge))
            <div class="text-[10px] sm:text-xs font-sans uppercase tracking-[0.15em] sm:tracking-[0.3em] text-accent/90 font-semibold break-words px-2">
                {{ $heroBadge }}
            </div>
        @endif

        <!-- Title -->
        <h1 class="font-serif font-normal text-white tracking-tight text-xl sm:text-3xl md:text-4xl lg:text-5xl leading-snug max-w-4xl mx-auto break-words px-2">
            {{ $heroTitle }}
        </h1>

        <!-- Decorative Divider -->
        <div class="flex items-center justify-center gap-3">
            <span class="w-12 h-[1px] bg-accent/60"></span>
            <span class="text-accent text-xs">◆</span>
            <span class="w-12 h-[1px] bg-accent/60"></span>
        </div>

        <!-- Subtitle -->
        @if(!empty($heroSubtitle))
            <p class="text-sm sm:text-base md:text-lg text-paper/85 max-w-2xl mx-auto font-sans leading-relaxed">
                {{ $heroSubtitle }}
            </p>
        @endif

        <!-- Centered Action Buttons -->
        <div class="pt-4 flex flex-wrap justify-center items-center gap-4">
            @if(!empty($heroPrimaryCtaText))
                <a href="{{ $heroPrimaryCtaLink }}"
                   class="px-8 py-3.5 uppercase text-xs tracking-widest font-sans font-bold bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition border border-accent shadow-lg">
                    {{ $heroPrimaryCtaText }}
                </a>
            @endif

            @if(!empty($heroSecondaryCtaText))
                <a href="{{ $heroSecondaryCtaLink }}"
                   class="px-8 py-3.5 uppercase text-xs tracking-widest font-sans font-semibold border border-white/40 text-white bg-transparent hover:bg-white hover:text-ink transition">
                    {{ $heroSecondaryCtaText }}
                </a>
            @endif
        </div>

    </div>

    <!-- 3-Pill Quick-Action Bar at Bottom -->
    <div class="relative z-10 border-t border-accent/20 bg-ink/90 backdrop-blur-md py-4">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                <a href="#about" class="flex items-center justify-center gap-2 text-xs uppercase tracking-wider text-paper/80 hover:text-accent font-sans transition">
                    <span class="text-accent">🏛️</span>
                    <span>Institutional Heritage</span>
                </a>
                <a href="#academics" class="flex items-center justify-center gap-2 text-xs uppercase tracking-wider text-paper/80 hover:text-accent font-sans transition border-y sm:border-y-0 sm:border-x border-white/10 py-1 sm:py-0">
                    <span class="text-accent">📚</span>
                    <span>Curriculum & Pathways</span>
                </a>
                <a href="#contact" class="flex items-center justify-center gap-2 text-xs uppercase tracking-wider text-paper/80 hover:text-accent font-sans transition">
                    <span class="text-accent">✉️</span>
                    <span>Campus Registry Inquiry</span>
                </a>
            </div>
        </div>
    </div>

</section>
