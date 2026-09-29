@php
    $resultsEyebrow = \App\Services\FrontendLibrary::get('results_eyebrow', 'EXAMINATION OUTCOMES & REPUTATION');
    $resultsHeading = \App\Services\FrontendLibrary::get('results_heading', 'A Proven Record of Scholastic Brilliance');
    $resultsStats = \App\Services\FrontendLibrary::getJson('stats_items', [
        [
            'num'   => '98%',
            'label' => 'WAEC & NECO 5+ Credits Distinction Rate',
        ],
        [
            'num'   => '285+',
            'label' => 'Average JAMB UTME Aggregate Score',
        ],
        [
            'num'   => '100%',
            'label' => 'BECE Junior School Examination Clearance',
        ],
        [
            'num'   => '100%',
            'label' => 'University & Higher Institution Placement',
        ],
    ]);

    $destinations = \App\Services\FrontendLibrary::getJson('stats_destinations', [
        'University of Lagos (UNILAG)',
        'University of Ibadan (UI)',
        'Covenant University',
        'Obafemi Awolowo University (OAU)',
        'Babcock University',
        'UK & US University Placements',
    ]);
@endphp

<!-- ====== Modern Theme Results / Stats Section ====== -->
<section id="results" class="py-16 sm:py-24 bg-white relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Large Dark Modern Card Container -->
        <div class="relative rounded-3xl bg-ink text-white p-8 sm:p-12 lg:p-16 shadow-2xl overflow-hidden">
            
            <!-- Ambient Radial Glow -->
            <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-accent/20 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-96 h-96 rounded-full bg-accent/15 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 space-y-12">
                
                <!-- Header -->
                <div class="text-center max-w-3xl mx-auto space-y-3">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-accent/20 text-accent text-xs font-sans font-bold uppercase tracking-wider">
                        <span>{{ $resultsEyebrow }}</span>
                    </div>
                    <h2 class="font-sans font-extrabold text-white text-2xl sm:text-3xl lg:text-4xl tracking-tight leading-tight">
                        {{ $resultsHeading }}
                    </h2>
                </div>

                <!-- 4 Modern Stat Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($resultsStats as $stat)
                        <div class="p-6 sm:p-8 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 text-center hover:bg-white/15 transition-all group">
                            <div class="text-4xl sm:text-5xl font-sans font-extrabold text-accent mb-2 tracking-tight group-hover:scale-105 transition-transform">
                                {{ $stat['num'] ?? '' }}
                            </div>
                            <div class="text-xs sm:text-sm text-paper/85 font-sans leading-relaxed">
                                {{ $stat['label'] ?? '' }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Destinations / Accreditations Chips -->
                @if(!empty($destinations))
                    <div class="pt-8 border-t border-white/15 text-center space-y-4">
                        <div class="text-xs uppercase tracking-wider text-accent font-sans font-semibold">
                            Select Higher Institution & University Destinations
                        </div>
                        <div class="flex flex-wrap gap-2.5 justify-center">
                            @foreach($destinations as $dest)
                                <span class="px-4 py-1.5 rounded-full bg-white/10 text-white/90 text-xs font-sans font-medium border border-white/10 hover:border-accent hover:text-accent transition-colors">
                                    {{ $dest }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

        </div>

    </div>
</section>
