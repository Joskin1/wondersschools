@php
    $academicsEyebrow = \App\Services\FrontendLibrary::get('academics_eyebrow', 'ACADEMIC DIVISIONS');
    $academicsHeading = \App\Services\FrontendLibrary::get('academics_heading', 'Curricular Pathways & Qualifications');
    $academicsIntro = \App\Services\FrontendLibrary::get('academics_intro', 'Structured into distinct progressive stages ensuring unbroken academic continuity from early childhood through college graduation.');
    $academicsTracks = \App\Services\FrontendLibrary::getJson('academics_tracks', [
        [
            'stage' => 'Early Years (Crèche & Nursery)',
            'age'   => 'Ages 1 - 5 Years',
            'desc'  => 'Play-based sensory learning, phonics foundation, numeracy discovery, and motor skill development in a safe, loving atmosphere.',
            'curriculum' => ['Early Years Foundation Stage (EYFS)', 'Phonics & Speech Development', 'Creative Arts & Music'],
        ],
        [
            'stage' => 'Primary School (Years 1 - 6)',
            'age'   => 'Ages 6 - 11 Years',
            'desc'  => 'Core literacy, mathematics, introductory sciences, computational thinking, and moral character building.',
            'curriculum' => ['National Primary Curriculum', 'Cambridge Primary Checkpoint', 'Digital Literacy & Coding'],
        ],
        [
            'stage' => 'College (Junior & Senior Secondary)',
            'age'   => 'Ages 11 - 17 Years',
            'desc'  => 'Rigorous secondary school education leading to WAEC, NECO, and Cambridge IGCSE qualifications with dedicated STEM and Arts tracks.',
            'curriculum' => ['BECE / Junior WAEC', 'Senior WAEC & NECO SSCE', 'Cambridge IGCSE & JAMB Prep'],
        ],
    ]);
@endphp

<!-- ====== Classic Theme Academics Section ====== -->
<section id="academics" class="py-16 sm:py-24 bg-paper relative">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-3 mb-12 sm:mb-16">
            <div class="text-xs font-sans uppercase tracking-[0.25em] text-accent font-bold">
                {{ $academicsEyebrow }}
            </div>
            <h2 class="font-serif font-normal text-ink text-2xl sm:text-3xl lg:text-4xl leading-tight">
                {{ $academicsHeading }}
            </h2>
            <div class="flex items-center justify-center gap-3 pt-1">
                <span class="w-10 h-[1px] bg-accent/40"></span>
                <span class="text-accent text-xs">◆</span>
                <span class="w-10 h-[1px] bg-accent/40"></span>
            </div>
        </div>

        <!-- 3 Structured Division Columns -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($academicsTracks as $track)
                @php
                    $curriculumList = $track['curriculum'] ?? [];
                    if (is_string($curriculumList)) {
                        $curriculumList = explode(',', $curriculumList);
                    }
                @endphp
                <div class="border border-rule bg-white p-8 flex flex-col justify-between shadow-sm">
                    <div>
                        <div class="text-xs font-serif uppercase tracking-widest text-accent font-bold mb-2">
                            {{ $track['age'] ?? 'All Ages' }}
                        </div>
                        <h3 class="font-serif font-semibold text-xl text-ink mb-4 pb-3 border-b border-rule">
                            {{ $track['stage'] ?? '' }}
                        </h3>
                        <p class="font-sans text-xs sm:text-sm text-body/80 leading-relaxed mb-6">
                            {{ $track['desc'] ?? '' }}
                        </p>

                        @if(!empty($curriculumList))
                            <div class="space-y-2 pt-2 border-t border-rule/60">
                                <div class="text-[10px] uppercase font-sans tracking-wider text-slate-400 font-bold">Curriculum Syllabus</div>
                                @foreach($curriculumList as $item)
                                    <div class="text-xs font-serif text-ink flex items-start gap-2">
                                        <span class="text-accent">▪</span>
                                        <span>{{ trim($item) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="pt-6 mt-6 border-t border-rule">
                        <a href="#admissions" class="block w-full py-2.5 text-center text-xs uppercase tracking-widest font-sans font-bold border border-ink text-ink hover:bg-ink hover:text-white transition">
                            Enroll in {{ strtok($track['stage'] ?? 'Division', '(') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>
