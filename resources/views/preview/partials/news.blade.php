@if(!empty($school['news']))
<!-- ====== 06 — Bulletin & Announcements (Horizontal Editorial Rows) ====== -->
<section id="news" class="py-24 md:py-32 bg-[#FAF8F4]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-[#C8A951]">
          {{ $school['news']['number'] ?? '06' }} &mdash; {{ $school['news']['eyebrow'] ?? 'BULLETIN' }}
        </span>
        <span class="flex-grow h-[1px] bg-[#E5E0D8]"></span>
      </div>
    </div>

    <!-- Section Heading Block -->
    <div class="mb-16 max-w-2xl">
      <h2 class="font-serif font-semibold text-[#0B2545] tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
        {{ $school['news']['heading'] }}
      </h2>
    </div>

    <!-- 3 Horizontal Rows with Small Square Thumbnail on Left (Strictly NOT vertical cards) -->
    <div class="divide-y divide-[#E5E0D8] border-t border-b border-[#E5E0D8]">
      
      @foreach($school['news']['articles'] as $article)
        <article class="py-8 sm:py-10 grid grid-cols-1 md:grid-cols-12 gap-6 items-center group hover:bg-[#FAF8F4]/80 transition">
          
          <!-- Small Square Thumbnail on the Left (Cols 1-2) -->
          <div class="md:col-span-2">
            <div class="w-24 h-24 sm:w-28 sm:h-28 border border-[#E5E0D8] bg-white p-1 flex-shrink-0 overflow-hidden">
              <img src="{{ $article['image'] }}"
                   alt="{{ $article['title'] }}"
                   loading="lazy"
                   class="w-full h-full object-cover group-hover:scale-105 transition duration-500" />
            </div>
          </div>

          <!-- Metadata & Title Column (Cols 3-7) -->
          <div class="md:col-span-5 space-y-2">
            <div class="flex items-center gap-3 text-xs font-sans font-semibold">
              <span class="text-[#C8A951] uppercase tracking-wider">{{ $article['category'] }}</span>
              <span class="text-[#0B2545]/40">&bull;</span>
              <span class="text-[#0B2545]/70">{{ $article['date'] }}</span>
            </div>
            <h3 class="font-serif text-lg sm:text-xl font-semibold text-[#0B2545] group-hover:text-[#C8A951] transition leading-snug">
              <a href="#admissions">{{ $article['title'] }}</a>
            </h3>
          </div>

          <!-- Summary Column (Cols 8-12) -->
          <div class="md:col-span-5 text-xs sm:text-sm text-[#2D3748]/85 font-sans leading-[1.7] max-w-[62ch]">
            <p>{{ $article['summary'] }}</p>
          </div>

        </article>
      @endforeach

    </div>

  </div>
</section>

<!-- Full container hairline divider -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="h-[1px] w-full bg-[#E5E0D8]"></div>
</div>
@endif
