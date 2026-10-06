@php
    $academicsEyebrow = \App\Services\FrontendLibrary::get('academics_eyebrow', 'ACADEMIC PATHWAYS');
    $academicsHeading = \App\Services\FrontendLibrary::get('academics_heading', 'Comprehensive Education Across All Stages');
    $academicsIntro = \App\Services\FrontendLibrary::get('academics_intro', 'Structured into distinct progressive stages ensuring unbroken academic continuity from early childhood through college graduation.');
    $academicsTracks = \App\Services\FrontendLibrary::getJson('academics_tracks', [
        [
            'stage' => 'Early Years (Crèche & Nursery)',
            'age'   => 'Ages 1 - 5 Years',
            'desc'  => 'Play-based sensory learning, phonics foundation, numeracy discovery, and motor skill development in a safe, loving atmosphere.',
            'curriculum' => ['Early Years Foundation Stage (EYFS)', 'Phonics & Speech Development', 'Creative Arts & Music'],
            'popular' => false,
        ],
        [
            'stage' => 'Primary School (Years 1 - 6)',
            'age'   => 'Ages 6 - 11 Years',
            'desc'  => 'Core literacy, mathematics, introductory sciences, computational thinking, and moral character building.',
            'curriculum' => ['National Primary Curriculum', 'Cambridge Primary Checkpoint', 'Digital Literacy & Coding'],
            'popular' => true,
        ],
        [
            'stage' => 'College (Junior & Senior Secondary)',
            'age'   => 'Ages 11 - 17 Years',
            'desc'  => 'Rigorous secondary school education leading to WAEC, NECO, and Cambridge IGCSE qualifications with dedicated STEM and Arts tracks.',
            'curriculum' => ['BECE / Junior WAEC', 'Senior WAEC & NECO SSCE', 'Cambridge IGCSE & JAMB Prep'],
            'popular' => false,
        ],
    ]);
@endphp

<!-- ====== Modern Theme Academics Section ====== -->
<section id="academics" class="py-16 sm:py-24 bg-slate-50 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-12 sm:mb-16">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-accent/15 text-accent text-xs font-sans font-bold uppercase tracking-wider">
                <span>{{ $academicsEyebrow }}</span>
            </div>
            <h2 class="font-sans font-extrabold text-ink text-2xl sm:text-3xl lg:text-4xl tracking-tight leading-tight">
                {{ $academicsHeading }}
            </h2>
            @if(!empty($academicsIntro))
                <p class="text-sm sm:text-base text-slate-600 font-sans leading-relaxed">
                    {{ $academicsIntro }}
                </p>
            @endif
        </div>

        <!-- 3-Column Modern Division Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
            @foreach($academicsTracks as $track)
                @php
                    $isPopular = !empty($track['popular']);
                    $curriculumList = $track['curriculum'] ?? [];
                    if (is_string($curriculumList)) {
                        $curriculumList = explode(',', $curriculumList);
                    }
                @endphp
                <div class="rounded-3xl bg-white p-8 border {{ $isPopular ? 'border-accent shadow-xl ring-2 ring-accent/20 relative' : 'border-slate-100 shadow-sm hover:shadow-lg' }} flex flex-col justify-between transition-all duration-300">
                    
                    @if($isPopular)
                        <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full bg-accent text-[color:var(--accent-contrast)] text-[11px] font-sans font-extrabold uppercase tracking-wider shadow-sm">
                            Core Division
                        </div>
                    @endif

                    <div>
                        <!-- Stage & Age -->
                        <div class="mb-4">
                            <span class="inline-block px-3 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-sans font-semibold">
                                {{ $track['age'] ?? 'All Ages' }}
                            </span>
                        </div>

                        <h3 class="font-sans font-bold text-xl text-ink mb-3">
                            {{ $track['stage'] ?? '' }}
                        </h3>

                        <p class="text-sm text-slate-600 font-sans leading-relaxed mb-6">
                            {{ $track['desc'] ?? '' }}
                        </p>

                        <!-- Curriculum Points -->
                        @if(!empty($curriculumList))
                            <div class="pt-4 border-t border-slate-100 space-y-2.5 mb-8">
                                <div class="text-xs font-sans font-bold text-slate-400 uppercase tracking-wider">Key Highlights</div>
                                @foreach($curriculumList as $item)
                                    <div class="flex items-center gap-2.5 text-xs sm:text-sm text-slate-700 font-sans">
                                        <span class="text-accent font-bold">✓</span>
                                        <span>{{ trim($item) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Action Link -->
                    <div class="pt-4">
                        <a href="#admissions" class="w-full py-3 rounded-xl font-sans font-bold text-xs sm:text-sm text-center block {{ $isPopular ? 'bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover shadow-md' : 'bg-slate-100 text-slate-800 hover:bg-slate-200' }} transition-all">
                            Enroll in {{ strtok($track['stage'] ?? 'Division', '(') }}
                        </a>
                    </div>

                </div>
            @endforeach
        </div>

    </div>
</section>
