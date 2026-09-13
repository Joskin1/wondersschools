@if(!empty($school['features']))
<!-- ====== 02 — Distinctives (Editorial Table of Contents Layout) ====== -->
<section id="features" class="py-24 md:py-32 bg-[#FAF8F4]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-[#C8A951]">
          {{ $school['features']['number'] ?? '02' }} &mdash; {{ $school['features']['eyebrow'] ?? 'DISTINCTIVES' }}
        </span>
        <span class="flex-grow h-[1px] bg-[#E5E0D8]"></span>
      </div>
    </div>

    <!-- 12-Column Grid: Headings (Cols 1-4), Editorial Rows (Cols 6-12) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8">
      
      <!-- Left Heading Block (Cols 1-4) -->
      <div class="lg:col-span-4 space-y-4">
        <h2 class="font-serif font-semibold text-[#0B2545] tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
          {{ $school['features']['heading'] }}
        </h2>
        @if(!empty($school['features']['intro']))
          <p class="text-sm sm:text-base text-[#2D3748] font-sans leading-[1.7] max-w-[62ch]">
            {{ $school['features']['intro'] }}
          </p>
        @endif
        <div class="pt-4">
          <a href="#academics" class="inline-flex items-center gap-2 text-xs uppercase tracking-widest font-sans font-semibold text-[#0B2545] hover:text-[#C8A951] transition">
            <span>Review Full Curriculum</span>
            <span>&rarr;</span>
          </a>
        </div>
      </div>

      <!-- Right 2-Column Table of Contents Style Rows (Cols 6-12) -->
      <div class="lg:col-span-7 lg:col-start-6">
        <div class="divide-y divide-[#E5E0D8] border-t border-b border-[#E5E0D8]">
          
          @foreach($school['features']['items'] as $item)
            <div class="py-6 sm:py-8 grid grid-cols-1 sm:grid-cols-12 gap-4 items-baseline group hover:bg-[#FAF8F4]/80 transition">
              
              <!-- Number Column (2 cols) -->
              <div class="sm:col-span-2 font-serif text-lg sm:text-xl font-semibold text-[#C8A951]">
                {{ $item['num'] }}
              </div>

              <!-- Title & Description Column (10 cols) -->
              <div class="sm:col-span-10 space-y-1.5">
                <h3 class="font-serif text-lg sm:text-xl font-semibold text-[#0B2545] group-hover:text-[#C8A951] transition">
                  {{ $item['title'] }}
                </h3>
                <p class="text-xs sm:text-sm text-[#2D3748]/80 font-sans leading-[1.7] max-w-[62ch]">
                  {{ $item['desc'] }}
                </p>
              </div>

            </div>
          @endforeach

        </div>
      </div>

    </div>

  </div>
</section>

<!-- Full container hairline divider -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="h-[1px] w-full bg-[#E5E0D8]"></div>
</div>
@endif
