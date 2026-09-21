@php
    $newsEyebrow = \App\Services\FrontendLibrary::get('news_eyebrow', 'BULLETIN & CALENDAR');
    $newsHeading = \App\Services\FrontendLibrary::get('news_heading', 'Recent Announcements & Key Dates');
    $newsArticles = \App\Services\FrontendLibrary::getJson('news_articles', [
        [
            'title'    => '2026/2027 First Batch National Entrance Examination & Scholarship Screening',
            'category' => 'ADMISSIONS',
            'date'     => 'Saturday, 18 April 2026',
            'summary'  => 'Prospective candidates for JSS 1 and transfer classes will sit for Mathematics, English Language, and General Aptitude screening. Top 5 candidates receive merit tuition scholarships.',
            'image'    => 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=400&q=80',
        ],
        [
            'title'    => '24th Annual Inter-House Athletics & March-Past Championship',
            'category' => 'ATHLETICS',
            'date'     => 'Friday, 27 March 2026',
            'summary'  => 'Emerald, Ruby, Sapphire, and Topaz houses compete for track, field, and cultural march-past honors. Parents, guardians, and alumni are cordially invited to the Main Sports Arena.',
            'image'    => 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?auto=format&fit=crop&w=400&q=80',
        ],
        [
            'title'    => 'Annual Young Innovators STEM & Robotics Public Exhibition',
            'category' => 'ACADEMICS',
            'date'     => 'Wednesday, 13 May 2026',
            'summary'  => 'Senior secondary scholars present functional solar micro-inverter designs, automated irrigation models, and AI chatbot demonstrators to university visiting professors.',
            'image'    => 'https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?auto=format&fit=crop&w=400&q=80',
        ],
    ]);
@endphp

<!-- ====== 06 — Bulletin & Announcements (Horizontal Editorial Rows) ====== -->
<section id="news" class="py-24 md:py-32 bg-paper">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
          {{ $sectionNumber ?? '06' }} &mdash; {{ $newsEyebrow }}
        </span>
        <span class="flex-grow h-[1px] bg-rule"></span>
      </div>
    </div>

    <!-- Section Heading Block -->
    <div class="mb-16 max-w-2xl">
      <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
        {{ $newsHeading }}
      </h2>
    </div>

    <!-- 3 Horizontal Rows with Small Square Thumbnail on Left (Strictly NOT vertical cards) -->
    <div class="divide-y divide-rule border-t border-b border-rule">
      
      @foreach($newsArticles as $article)
        <article class="py-8 sm:py-10 grid grid-cols-1 md:grid-cols-12 gap-6 items-center group hover:bg-paper/80 transition">
          
          <!-- Small Square Thumbnail on the Left (Cols 1-2) -->
          <div class="md:col-span-2">
            <div class="w-24 h-24 sm:w-28 sm:h-28 border border-rule bg-white p-1 flex-shrink-0 overflow-hidden">
              <img src="{{ \App\Services\FrontendLibrary::imageUrl($article['image'] ?? null, 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?auto=format&fit=crop&w=400&q=80') }}"
                   alt="{{ $article['title'] ?? 'News' }}"
                   loading="lazy"
                   class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
            </div>
          </div>

          <!-- Metadata & Title Column (Cols 3-7) -->
          <div class="md:col-span-5 space-y-2">
            <div class="flex items-center gap-3 text-xs font-sans font-semibold">
              <span class="text-accent uppercase tracking-wider">{{ $article['category'] ?? 'NEWS' }}</span>
              <span class="text-ink/40">&bull;</span>
              <span class="text-ink/70">{{ $article['date'] ?? '' }}</span>
            </div>
            <h3 class="font-serif text-lg sm:text-xl font-semibold text-ink group-hover:text-accent transition leading-snug">
              <a href="#admissions">{{ $article['title'] ?? '' }}</a>
            </h3>
          </div>

          <!-- Summary Column (Cols 8-12) -->
          <div class="md:col-span-5 text-xs sm:text-sm text-body/85 font-sans leading-[1.7] max-w-[62ch]">
            <p>{{ $article['summary'] ?? '' }}</p>
          </div>

        </article>
      @endforeach

    </div>

  </div>
</section>

<!-- Full container hairline divider -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="h-[1px] w-full bg-rule"></div>
</div>
