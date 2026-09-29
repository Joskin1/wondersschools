@php
    $schoolName = \App\Services\FrontendLibrary::getSetting('school_name', 'Cathedral College');
    $aboutEyebrow = \App\Services\FrontendLibrary::get('about_eyebrow', 'INSTITUTIONAL HERITAGE');
    $aboutHeading = \App\Services\FrontendLibrary::get('about_heading', 'A Tradition of Uncompromising Academic Standard');
    $aboutImageRaw = \App\Services\FrontendLibrary::get('about_image', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?q=80&w=800&auto=format&fit=crop');
    $aboutImage = \App\Services\FrontendLibrary::imageUrl($aboutImageRaw);
    $aboutImageAlt = \App\Services\FrontendLibrary::get('about_image_alt', 'Head of School');
    $aboutYearsBadge = \App\Services\FrontendLibrary::get('about_years_badge', '25');
    $aboutYearsLabel = \App\Services\FrontendLibrary::get('about_years_label', 'Years of Academic Excellence');
    $aboutBody = \App\Services\FrontendLibrary::get('about_body', "<p>Founded with a commitment to excellence, {$schoolName} synthesizes the rigorous Nigerian National Curriculum with international standards...</p>");
    $aboutPrincipalName = \App\Services\FrontendLibrary::get('about_principal_name', 'Principal & Head of School');
    $aboutPrincipalTitle = \App\Services\FrontendLibrary::get('about_principal_title', 'B.Sc, M.Ed — Head of Administration');
@endphp

<!-- ====== Classic Theme About Section ====== -->
<section id="about" class="py-16 sm:py-24 bg-paper relative">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="text-center max-w-3xl mx-auto space-y-3 mb-12 sm:mb-16">
            <div class="text-xs font-sans uppercase tracking-[0.25em] text-accent font-bold">
                {{ $aboutEyebrow }}
            </div>
            <h2 class="font-serif font-normal text-ink text-2xl sm:text-3xl lg:text-4xl leading-tight">
                {{ $aboutHeading }}
            </h2>
            <div class="flex items-center justify-center gap-3 pt-1">
                <span class="w-10 h-[1px] bg-accent/40"></span>
                <span class="text-accent text-xs">◆</span>
                <span class="w-10 h-[1px] bg-accent/40"></span>
            </div>
        </div>

        <!-- Symmetrical Framed Layout -->
        <div class="p-6 sm:p-10 border-2 border-accent/30 bg-white shadow-sm relative">
            
            <!-- Decorative Gold Corners -->
            <div class="absolute -top-1.5 -left-1.5 w-3 h-3 bg-accent"></div>
            <div class="absolute -top-1.5 -right-1.5 w-3 h-3 bg-accent"></div>
            <div class="absolute -bottom-1.5 -left-1.5 w-3 h-3 bg-accent"></div>
            <div class="absolute -bottom-1.5 -right-1.5 w-3 h-3 bg-accent"></div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                
                <!-- Left: Framed Portrait (5 cols) -->
                <div class="lg:col-span-5 text-center">
                    <div class="p-2 border border-rule bg-paper inline-block shadow-sm">
                        <img src="{{ $aboutImage }}"
                             alt="{{ $aboutImageAlt }}"
                             class="w-full max-w-sm h-auto object-cover" />
                    </div>
                    <div class="mt-4 space-y-1">
                        <div class="font-serif font-bold text-base text-ink">{{ $aboutPrincipalName }}</div>
                        <div class="text-xs font-sans text-slate-500 uppercase tracking-wider">{{ $aboutPrincipalTitle }}</div>
                    </div>
                </div>

                <!-- Right: Editorial Narrative (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <div class="prose prose-slate max-w-none font-serif text-body text-sm sm:text-base leading-relaxed space-y-4">
                        {!! $aboutBody !!}
                    </div>

                    <div class="pt-4 border-t border-rule flex items-center justify-between">
                        <div>
                            <span class="text-3xl font-serif font-bold text-accent">{{ $aboutYearsBadge }}</span>
                            <span class="text-xs uppercase tracking-wider font-sans text-slate-500 ml-2">{{ $aboutYearsLabel }}</span>
                        </div>
                        <a href="#academics" class="text-xs uppercase tracking-widest font-sans font-bold text-accent hover:text-ink transition">
                            Curricular Framework →
                        </a>
                    </div>
                </div>

            </div>

        </div>

    </div>
</section>
