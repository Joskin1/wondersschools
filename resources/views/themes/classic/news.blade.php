@php
    $newsEyebrow = \App\Services\FrontendLibrary::get('news_eyebrow', 'ACADEMIC GAZETTE & DISPATCHES');
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

<!-- ====== Classic Theme News Section ====== -->
<section id="news" class="py-16 sm:py-24 bg-paper relative">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-3 mb-12 sm:mb-16">
            <div class="text-xs font-sans uppercase tracking-[0.25em] text-accent font-bold">
                {{ $newsEyebrow }}
            </div>
            <h2 class="font-serif font-normal text-ink text-2xl sm:text-3xl lg:text-4xl leading-tight">
                {{ $newsHeading }}
            </h2>
            <div class="flex items-center justify-center gap-3 pt-1">
                <span class="w-10 h-[1px] bg-accent/40"></span>
                <span class="text-accent text-xs">◆</span>
                <span class="w-10 h-[1px] bg-accent/40"></span>
            </div>
        </div>

        <!-- Gazette Table Rows -->
        <div class="border-t border-b border-rule divide-y divide-rule bg-white">
            @foreach($newsArticles as $article)
                @php
                    $img = \App\Services\FrontendLibrary::imageUrl($article['image'] ?? '');
                    $title = $article['title'] ?? '';
                    $date = $article['date'] ?? '';
                    $cat = $article['category'] ?? 'OFFICIAL';
                    $excerpt = $article['excerpt'] ?? '';
                @endphp
                <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-12 gap-6 items-center hover:bg-paper/50 transition-colors">
                    <div class="md:col-span-2 text-xs font-serif uppercase tracking-widest text-accent font-bold">
                        {{ $date }}
                    </div>
                    <div class="md:col-span-8 space-y-2">
                        <span class="text-[10px] uppercase tracking-wider px-2 py-0.5 border border-rule text-slate-500 font-sans">
                            {{ $cat }}
                        </span>
                        <h3 class="font-serif font-semibold text-lg text-ink">
                            {{ $title }}
                        </h3>
                        <p class="font-sans text-xs text-body/80 leading-relaxed">
                            {{ $excerpt }}
                        </p>
                    </div>
                    <div class="md:col-span-2 text-right">
                        <span class="text-xs uppercase tracking-wider font-sans font-bold text-accent hover:underline inline-flex items-center gap-1">
                            <span>Read</span>
                            <span>→</span>
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>
