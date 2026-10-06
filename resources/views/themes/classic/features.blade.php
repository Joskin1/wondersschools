@php
    $defaultSchoolName = \App\Services\FrontendLibrary::getSetting('school_name', 'Cathedral College');
    $featuresEyebrow = \App\Services\FrontendLibrary::get('features_eyebrow', 'DISTINCTIVE PILLARS');
    $featuresHeading = \App\Services\FrontendLibrary::get('features_heading', "The Foundations of a {$defaultSchoolName} Education");
    $featuresIntro = \App\Services\FrontendLibrary::get('features_intro', 'A deliberate blend of academic depth, moral discipline, and technological literacy structured to cultivate leaders.');
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
            'title' => 'Distinguished Faculty',
            'desc'  => 'Certified educators with postgraduate qualifications and decades of cumulative teaching excellence.',
        ],
        [
            'title' => 'Secure & Nurturing Campus',
            'desc'  => 'A serene, secure learning environment with close parent-teacher communication and modern health bay.',
        ],
    ]);

    $romans = ['I', 'II', 'III', 'IV', 'V', 'VI'];
@endphp

<!-- ====== Classic Theme Features Section ====== -->
<section id="features" class="py-16 sm:py-24 bg-white relative">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-3 mb-12 sm:mb-16">
            <div class="text-xs font-sans uppercase tracking-[0.25em] text-accent font-bold">
                {{ $featuresEyebrow }}
            </div>
            <h2 class="font-serif font-normal text-ink text-2xl sm:text-3xl lg:text-4xl leading-tight">
                {{ $featuresHeading }}
            </h2>
            <div class="flex items-center justify-center gap-3 pt-1">
                <span class="w-10 h-[1px] bg-accent/40"></span>
                <span class="text-accent text-xs">◆</span>
                <span class="w-10 h-[1px] bg-accent/40"></span>
            </div>
        </div>

        <!-- 6 Structured Classical Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($featuresItems as $index => $item)
                @php
                    $roman = $romans[$index % count($romans)];
                    $title = $item['title'] ?? '';
                    $desc = $item['desc'] ?? '';
                @endphp
                <div class="p-8 border border-rule bg-paper hover:border-accent transition-colors duration-300 relative group flex flex-col justify-between">
                    <div>
                        <div class="font-serif text-accent text-lg font-bold mb-2">{{ $roman }}.</div>
                        <h3 class="font-serif font-semibold text-lg text-ink mb-3 group-hover:text-accent transition-colors">
                            {{ $title }}
                        </h3>
                        <p class="font-sans text-xs sm:text-sm text-body/80 leading-relaxed">
                            {{ $desc }}
                        </p>
                    </div>
                    <div class="pt-4 mt-6 border-t border-rule/60 text-right">
                        <span class="text-[10px] uppercase tracking-widest font-sans text-accent font-bold">Pillar {{ $index + 1 }}</span>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>
