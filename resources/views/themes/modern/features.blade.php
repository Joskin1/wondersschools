@php
    $defaultSchoolName = \App\Services\FrontendLibrary::getSetting('school_name', 'Our School');
    $featuresEyebrow = \App\Services\FrontendLibrary::get('features_eyebrow', 'WHY CHOOSE US');
    $featuresHeading = \App\Services\FrontendLibrary::get('features_heading', "The Pillars of a {$defaultSchoolName} Education");
    $featuresIntro = \App\Services\FrontendLibrary::get('features_intro', 'A deliberate blend of academic depth, moral discipline, and technological literacy structured to cultivate leaders.');
    $featuresCtaText = \App\Services\FrontendLibrary::get('features_cta_text', 'Review Full Curriculum');
    $featuresCtaLink = \App\Services\FrontendLibrary::get('features_cta_link', '#academics');
    $featuresItems = \App\Services\FrontendLibrary::getJson('features_items', [
        [
            'title' => 'Integrated Dual Curriculum',
            'desc'  => 'Simultaneous mastery of the Nigerian National Curriculum alongside British Cambridge standards.',
        ],
        [
            'title' => 'Moral Discipline & Mentorship',
            'desc'  => 'A structured pastoral care framework embedding the fear of God, accountability, and strong civic values.',
        ],
        [
            'title' => 'STEM & Digital Innovation',
            'desc'  => 'Dedicated computer suites, coding clubs, and practical sciences fostering 21st-century problem-solving.',
        ],
        [
            'title' => 'Championship Sports & Arts',
            'desc'  => 'A vibrant co-curricular ecosystem including football, athletics, music academy, and debate societies.',
        ],
        [
            'title' => 'Experienced Faculty',
            'desc'  => 'Certified educators with postgraduate qualifications and decades of cumulative teaching excellence.',
        ],
        [
            'title' => 'Secure & Nurturing Campus',
            'desc'  => 'A serene, secure learning environment with close parent-teacher communication and modern health bay.',
        ],
    ]);

    $icons = ['📚', '🛡️', '🔬', '🏆', '👨‍🏫', '🏫'];
@endphp

<!-- ====== Modern Theme Features / Distinctives ====== -->
<section id="features" class="py-16 sm:py-24 bg-slate-50 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-12 sm:mb-16">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-accent/15 text-accent text-xs font-sans font-bold uppercase tracking-wider">
                <span>{{ $featuresEyebrow }}</span>
            </div>
            <h2 class="font-sans font-extrabold text-ink text-2xl sm:text-3xl lg:text-4xl tracking-tight leading-tight">
                {{ $featuresHeading }}
            </h2>
            @if(!empty($featuresIntro))
                <p class="text-sm sm:text-base text-slate-600 font-sans leading-relaxed">
                    {{ $featuresIntro }}
                </p>
            @endif
        </div>

        <!-- 3-Column Modern Elevated Card Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            @foreach($featuresItems as $index => $item)
                @php
                    $title = $item['title'] ?? '';
                    $desc  = $item['desc'] ?? '';
                    $icon  = $icons[$index % count($icons)];
                @endphp
                <div class="p-7 sm:p-8 rounded-3xl bg-white border border-slate-100 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between">
                    <div>
                        <!-- Icon & Number -->
                        <div class="flex items-center justify-between mb-6">
                            <div class="w-12 h-12 rounded-2xl bg-accent/15 text-accent flex items-center justify-center text-xl group-hover:bg-accent group-hover:text-[color:var(--accent-contrast)] transition-colors">
                                {{ $icon }}
                            </div>
                            <span class="text-xs font-sans font-bold text-slate-300">0{{ $index + 1 }}</span>
                        </div>

                        <!-- Card Content -->
                        <h3 class="font-sans font-bold text-lg text-ink mb-3 group-hover:text-accent transition-colors">
                            {{ $title }}
                        </h3>
                        <p class="font-sans text-sm text-slate-600 leading-relaxed">
                            {{ $desc }}
                        </p>
                    </div>

                    <!-- Subtle bottom accent line on hover -->
                    <div class="w-0 group-hover:w-full h-1 bg-accent rounded-full mt-6 transition-all duration-300"></div>
                </div>
            @endforeach
        </div>

        <!-- Bottom CTA -->
        @if(!empty($featuresCtaText))
            <div class="text-center mt-12">
                <a href="{{ $featuresCtaLink }}" class="inline-flex items-center gap-2 px-8 py-3.5 rounded-full bg-ink text-white font-sans font-semibold text-sm hover:bg-ink/90 transition-all shadow-md">
                    <span>{{ $featuresCtaText }}</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
            </div>
        @endif

    </div>
</section>
