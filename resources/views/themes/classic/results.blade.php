@php
    $resultsEyebrow = \App\Services\FrontendLibrary::get('results_eyebrow', 'ACADEMIC OUTCOMES & REPUTATION');
    $resultsHeading = \App\Services\FrontendLibrary::get('results_heading', 'A Record of Unbroken Scholastic Brilliance');
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

<!-- ====== Classic Theme Results Section (Honor Roll Board) ====== -->
<section id="results" class="py-16 sm:py-24 bg-ink text-white relative">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Framed Classical Board Container -->
        <div class="border-2 border-accent/40 p-8 sm:p-12 relative bg-ink/95">
            
            <!-- Corner Accents -->
            <div class="absolute -top-1.5 -left-1.5 w-3 h-3 bg-accent"></div>
            <div class="absolute -top-1.5 -right-1.5 w-3 h-3 bg-accent"></div>
            <div class="absolute -bottom-1.5 -left-1.5 w-3 h-3 bg-accent"></div>
            <div class="absolute -bottom-1.5 -right-1.5 w-3 h-3 bg-accent"></div>

            <!-- Header -->
            <div class="text-center max-w-3xl mx-auto space-y-3 mb-12">
                <div class="text-xs font-sans uppercase tracking-[0.3em] text-accent font-bold">
                    {{ $resultsEyebrow }}
                </div>
                <h2 class="font-serif font-normal text-white text-2xl sm:text-3xl lg:text-4xl leading-tight">
                    {{ $resultsHeading }}
                </h2>
                <div class="flex items-center justify-center gap-3 pt-1">
                    <span class="w-10 h-[1px] bg-accent/40"></span>
                    <span class="text-accent text-xs">◆</span>
                    <span class="w-10 h-[1px] bg-accent/40"></span>
                </div>
            </div>

            <!-- 4 Stat Boxes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($resultsStats as $stat)
                    <div class="p-6 border border-white/15 bg-white/5 text-center">
                        <div class="text-4xl sm:text-5xl font-serif text-accent mb-2">
                            {{ $stat['num'] ?? '' }}
                        </div>
                        <div class="text-xs font-sans text-paper/80 uppercase tracking-wider leading-relaxed">
                            {{ $stat['label'] ?? '' }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Destinations -->
            @if(!empty($destinations))
                <div class="mt-10 pt-8 border-t border-accent/30 text-center space-y-3">
                    <div class="text-[11px] uppercase tracking-widest text-accent font-sans font-semibold">
                        Higher Institution & University Destinations
                    </div>
                    <div class="flex flex-wrap gap-2 justify-center">
                        @foreach($destinations as $dest)
                            <span class="px-3 py-1 border border-white/20 text-paper/90 text-xs font-sans">
                                {{ $dest }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

    </div>
</section>
