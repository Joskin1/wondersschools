@php
    $newsEyebrow = \App\Services\FrontendLibrary::get('news_eyebrow', 'CAMPUS DISPATCH');
    $newsHeading = \App\Services\FrontendLibrary::get('news_heading', 'Latest News, Events & Official Bulletins');
    $newsArticles = \App\Services\FrontendLibrary::getJson('news_articles', [
        [
            'title'    => '2026/2027 Entrance Examination & Scholarship Schedule Announced',
            'date'     => '15 OCT 2026',
            'category' => 'ADMISSIONS',
            'excerpt'  => 'Official dates for the upcoming entrance examination and academic scholarship screening across all testing centers.',
            'image'    => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?q=80&w=800&auto=format&fit=crop',
        ],
        [
            'title'    => 'Scholars Secure Top Honors in National Mathematics Olympiad',
            'date'     => '02 OCT 2026',
            'category' => 'ACADEMIC AWARDS',
            'excerpt'  => 'Our Junior and Senior secondary teams emerged victorious at the state and zonal mathematical sciences competition.',
            'image'    => 'https://images.unsplash.com/photo-1577896851231-70ef18881754?q=80&w=800&auto=format&fit=crop',
        ],
        [
            'title'    => 'New Advanced Robotics & Coding Laboratory Commissioned',
            'date'     => '18 SEP 2026',
            'category' => 'INFRASTRUCTURE',
            'excerpt'  => 'A major infrastructure milestone adding 30 high-speed AI workstations and physical computing apparatus.',
            'image'    => 'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?q=80&w=800&auto=format&fit=crop',
        ],
    ]);
@endphp

<!-- ====== Modern Theme News Section ====== -->
<section id="news" class="py-16 sm:py-24 bg-slate-50 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 sm:mb-16 gap-4">
            <div class="space-y-3">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-accent/15 text-accent text-xs font-sans font-bold uppercase tracking-wider">
                    <span>{{ $newsEyebrow }}</span>
                </div>
                <h2 class="font-sans font-extrabold text-ink text-2xl sm:text-3xl lg:text-4xl tracking-tight leading-tight">
                    {{ $newsHeading }}
                </h2>
            </div>
            <div>
                <a href="#contact" class="inline-flex items-center gap-2 text-sm font-sans font-bold text-accent hover:text-accent-hover transition-colors">
                    <span>View All Announcements</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
        </div>

        <!-- 3-Column Modern Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($newsArticles as $article)
                @php
                    $img = \App\Services\FrontendLibrary::imageUrl($article['image'] ?? '');
                    $title = $article['title'] ?? '';
                    $date = $article['date'] ?? '';
                    $cat = $article['category'] ?? 'NEWS';
                    $excerpt = $article['excerpt'] ?? '';
                @endphp
                <div class="rounded-3xl bg-white border border-slate-100 overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <!-- Image Container -->
                        <div class="relative aspect-[16/9] overflow-hidden bg-slate-200">
                            <img src="{{ $img }}"
                                 alt="{{ $title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            
                            <div class="absolute top-3 left-3">
                                <span class="px-3 py-1 rounded-full bg-accent text-[color:var(--accent-contrast)] text-[10px] font-sans font-extrabold uppercase tracking-wider shadow-sm">
                                    {{ $cat }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 sm:p-7 space-y-3">
                            <div class="text-xs font-sans font-semibold text-slate-400 uppercase tracking-wider">
                                📅 {{ $date }}
                            </div>
                            <h3 class="font-sans font-bold text-base sm:text-lg text-ink group-hover:text-accent transition-colors leading-snug line-clamp-2">
                                {{ $title }}
                            </h3>
                            <p class="text-xs sm:text-sm text-slate-600 font-sans leading-relaxed line-clamp-3">
                                {{ $excerpt }}
                            </p>
                        </div>
                    </div>

                    <div class="px-6 sm:px-7 pb-6 pt-2">
                        <span class="text-xs font-sans font-bold text-accent group-hover:underline inline-flex items-center gap-1">
                            <span>Read Full Dispatch</span>
                            <span>→</span>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>
