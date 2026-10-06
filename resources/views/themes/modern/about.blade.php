@php
    $schoolName = \App\Services\FrontendLibrary::getSetting('school_name', 'Living Spring');
    $aboutEyebrow = \App\Services\FrontendLibrary::get('about_eyebrow', 'ABOUT OUR INSTITUTION');
    $aboutHeading = \App\Services\FrontendLibrary::get('about_heading', 'A Tradition of Uncompromising Academic Standard');
    $aboutImageRaw = \App\Services\FrontendLibrary::get('about_image', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=800&auto=format&fit=crop');
    $aboutImage = \App\Services\FrontendLibrary::imageUrl($aboutImageRaw);
    $aboutImageAlt = \App\Services\FrontendLibrary::get('about_image_alt', 'Head of School');
    $aboutYearsBadge = \App\Services\FrontendLibrary::get('about_years_badge', '25+');
    $aboutYearsLabel = \App\Services\FrontendLibrary::get('about_years_label', 'Years of Academic Legacy');
    $aboutBody = \App\Services\FrontendLibrary::get('about_body', "<p>Founded with a commitment to excellence, {$schoolName} synthesizes the rigorous Nigerian National Curriculum with international best practices...</p>");
    $aboutPrincipalName = \App\Services\FrontendLibrary::get('about_principal_name', 'Head of School');
    $aboutPrincipalTitle = \App\Services\FrontendLibrary::get('about_principal_title', 'Principal & Academic Director');
@endphp

<!-- ====== Modern Theme About Section ====== -->
<section id="about" class="py-16 sm:py-24 bg-white relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
            
            <!-- Left Column: Modern Framed Image with Floating Badge (5 cols) -->
            <div class="lg:col-span-5 relative">
                <div class="relative rounded-3xl overflow-hidden shadow-xl aspect-[4/5] bg-slate-100 border border-slate-100">
                    <img src="{{ $aboutImage }}"
                         alt="{{ $aboutImageAlt }}"
                         class="w-full h-full object-cover object-center" />
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>

                    <!-- Bottom Principal Bio Overlay -->
                    @if(!empty($aboutPrincipalName))
                        <div class="absolute bottom-4 left-4 right-4 p-4 rounded-2xl bg-white/95 backdrop-blur-md text-ink shadow-lg">
                            <div class="font-sans font-bold text-sm sm:text-base text-ink">{{ $aboutPrincipalName }}</div>
                            <div class="text-xs text-slate-500 font-sans mt-0.5">{{ $aboutPrincipalTitle }}</div>
                        </div>
                    @endif
                </div>

                <!-- Floating Years Badge -->
                <div class="absolute -top-4 -left-4 sm:-left-6 bg-accent text-[color:var(--accent-contrast)] p-4 sm:p-5 rounded-2xl shadow-xl text-center">
                    <div class="text-2xl sm:text-3xl font-extrabold font-sans leading-none">{{ $aboutYearsBadge }}</div>
                    <div class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider mt-1 opacity-90">{{ $aboutYearsLabel }}</div>
                </div>
            </div>

            <!-- Right Column: Narrative & Values (7 cols) -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Eyebrow -->
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-accent/15 text-accent text-xs font-sans font-bold uppercase tracking-wider">
                    <span>{{ $aboutEyebrow }}</span>
                </div>

                <!-- Main Heading -->
                <h2 class="font-sans font-extrabold text-ink text-2xl sm:text-3xl lg:text-4xl tracking-tight leading-tight">
                    {{ $aboutHeading }}
                </h2>

                <!-- Rich Text Body -->
                <div class="prose prose-slate max-w-none font-sans text-slate-600 text-sm sm:text-base leading-relaxed space-y-4">
                    {!! $aboutBody !!}
                </div>

                <!-- Modern Highlight Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5">
                        <div class="text-accent text-xl font-bold">🎯 Moral & Academic Depth</div>
                        <p class="text-xs text-slate-600 font-sans leading-relaxed">
                            Holistic student development emphasizing discipline, integrity, leadership, and critical reasoning.
                        </p>
                    </div>
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-100 space-y-1.5">
                        <div class="text-accent text-xl font-bold">🔬 Modern Campus Facilities</div>
                        <p class="text-xs text-slate-600 font-sans leading-relaxed">
                            State-of-the-art STEM labs, digital computer resource centers, and modern sporting courts.
                        </p>
                    </div>
                </div>

                <!-- CTA Link -->
                <div class="pt-2">
                    <a href="#academics" class="inline-flex items-center gap-2 text-sm font-sans font-bold text-accent hover:text-accent-hover transition-colors">
                        <span>Discover our Academic Divisions</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>

            </div>

        </div>
    </div>
</section>
