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

    $schoolName = \App\Services\FrontendLibrary::getSetting('school_name', 'Our School');
    $schoolEst = \App\Services\FrontendLibrary::getSetting('school_established', '1998');
    $heroImageAlt = \App\Services\FrontendLibrary::get('hero_image_alt', $schoolName);
    $heroBadge = \App\Services\FrontendLibrary::get('hero_badge', 'Admissions Open 2026 / 2027');
    $heroTitle = \App\Services\FrontendLibrary::get('hero_title', 'Empowering Tomorrow’s Leaders with Character & Scholastic Rigour');
    $heroSubtitle = \App\Services\FrontendLibrary::get('hero_subtitle', 'An accredited British-Nigerian secondary institution committed to scholastic rigor, scientific inquiry, and the formation of character.');
    $heroPrimaryCtaText = \App\Services\FrontendLibrary::get('hero_primary_cta_text', 'Apply for Admission');
    $heroPrimaryCtaLink = \App\Services\FrontendLibrary::get('hero_primary_cta_link', '#admissions');
    $heroSecondaryCtaText = \App\Services\FrontendLibrary::get('hero_secondary_cta_text', 'Explore Campus');
    $heroSecondaryCtaLink = \App\Services\FrontendLibrary::get('hero_secondary_cta_link', '#about');
    $heroLocation = \App\Services\FrontendLibrary::getSetting('school_address', 'Ejinrin Road, Ijebu-Ode, Ogun State, Nigeria');
@endphp

<!-- ====== Modern Theme Hero Section ====== -->
<section id="home" class="relative overflow-hidden bg-gradient-to-b from-paper via-white to-slate-50 pt-6 pb-16 sm:pt-10 sm:pb-24">
    <!-- Ambient subtle background glow -->
    <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-accent/10 blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-ink/5 blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-center">
            
            <!-- Left Column: Copy & Actions (7 cols) -->
            <div class="lg:col-span-7 space-y-6 text-left w-full min-w-0">
                
                <!-- Pill Badge -->
                @if(!empty($heroBadge))
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent/15 border border-accent/30 text-accent font-sans font-semibold text-xs sm:text-sm tracking-wide shadow-sm max-w-full">
                        <span class="w-2 h-2 rounded-full bg-accent animate-ping shrink-0"></span>
                        <span class="truncate">{{ $heroBadge }}</span>
                    </div>
                @endif

                <!-- Main Heading -->
                <h1 class="font-sans font-extrabold text-ink tracking-tight text-lg sm:text-2xl md:text-4xl lg:text-5xl leading-snug break-words">
                    {{ $heroTitle }}
                </h1>

                <!-- Subtitle -->
                @if(!empty($heroSubtitle))
                    <p class="text-base sm:text-lg text-slate-600 font-sans max-w-2xl leading-relaxed">
                        {{ $heroSubtitle }}
                    </p>
                @endif

                <!-- Action Buttons -->
                <div class="pt-2 flex flex-wrap items-center gap-3 sm:gap-4">
                    @if(!empty($heroPrimaryCtaText))
                        <a href="{{ $heroPrimaryCtaLink }}"
                           class="inline-flex items-center justify-center px-7 py-3.5 sm:px-8 sm:py-4 rounded-full font-sans font-bold text-sm tracking-wide bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover shadow-lg hover:shadow-xl hover:-translate-y-0.5 transition-all">
                            <span>{{ $heroPrimaryCtaText }}</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                    @endif

                    @if(!empty($heroSecondaryCtaText))
                        <a href="{{ $heroSecondaryCtaLink }}"
                           class="inline-flex items-center justify-center px-7 py-3.5 sm:px-8 sm:py-4 rounded-full font-sans font-semibold text-sm tracking-wide bg-white text-ink border border-slate-200 hover:border-accent hover:text-accent shadow-sm hover:shadow transition-all">
                            {{ $heroSecondaryCtaText }}
                        </a>
                    @endif
                </div>

                <!-- Trust Chips -->
                <div class="pt-4 border-t border-slate-200/70 flex flex-wrap items-center gap-4 sm:gap-6 text-xs text-slate-500 font-sans font-medium">
                    <div class="flex items-center gap-1.5">
                        <span class="text-accent text-sm">✓</span>
                        <span>Accredited Curriculum</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-accent text-sm">✓</span>
                        <span>Est. {{ $schoolEst }}</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-accent text-sm">✓</span>
                        <span>Day & Boarding Options</span>
                    </div>
                </div>

            </div>

            <!-- Right Column: Interactive Modern Card & Image Showcase (5 cols) -->
            <div class="lg:col-span-5 relative"
                 x-data="{
                     currentSlide: 0,
                     slides: {{ json_encode($heroSlides) }},
                     init() {
                         if (this.slides.length > 1) {
                             setInterval(() => {
                                 this.currentSlide = (this.currentSlide + 1) % this.slides.length;
                             }, 4500);
                         }
                     }
                 }">
                
                <!-- Main Framed Image -->
                <div class="relative rounded-3xl overflow-hidden shadow-2xl bg-slate-900 w-full h-[380px] sm:h-[440px] lg:h-[500px] border-4 border-white">
                    @if(count($heroSlides) > 1)
                        @foreach($heroSlides as $idx => $slide)
                            <div x-show="currentSlide === {{ $idx }}"
                                 x-transition:enter="transition ease-out duration-700"
                                 x-transition:enter-start="opacity-0 scale-105"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-700"
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

                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/10 pointer-events-none"></div>

                    <!-- Slide dots indicator -->
                    @if(count($heroSlides) > 1)
                        <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-1.5 z-20">
                            @foreach($heroSlides as $idx => $slide)
                                <button @click="currentSlide = {{ $idx }}"
                                        class="h-1.5 rounded-full transition-all duration-300"
                                        :class="currentSlide === {{ $idx }} ? 'w-6 bg-accent' : 'w-2 bg-white/60'"
                                        aria-label="Slide {{ $idx + 1 }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Floating Glass Card 1: Top-Right -->
                <div class="absolute -top-4 -right-4 sm:-right-6 bg-white/95 backdrop-blur-md rounded-2xl p-3.5 sm:p-4 shadow-xl border border-slate-100 flex items-center gap-3 animate-fade-in z-20">
                    <div class="w-10 h-10 rounded-xl bg-accent/15 text-accent flex items-center justify-center font-bold text-lg">
                        🏆
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 font-sans font-medium">Academic Rating</div>
                        <div class="text-sm font-sans font-extrabold text-ink">98%+ Distinction</div>
                    </div>
                </div>

                <!-- Floating Glass Card 2: Bottom-Left -->
                <div class="absolute -bottom-4 -left-4 sm:-left-6 bg-ink/95 text-white backdrop-blur-md rounded-2xl p-3.5 sm:p-4 shadow-xl border border-white/10 flex items-center gap-3 z-20">
                    <div class="w-10 h-10 rounded-xl bg-accent text-ink flex items-center justify-center font-bold text-lg">
                        🎓
                    </div>
                    <div>
                        <div class="text-xs text-paper/70 font-sans">Excellence In Learning</div>
                        <div class="text-sm font-sans font-bold text-accent">Nursery • Primary • College</div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</section>
