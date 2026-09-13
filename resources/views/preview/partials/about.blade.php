@if(!empty($school['about']))
<!-- ====== 01 — About / Head of School (Prospectus Editorial) ====== -->
<section id="about" class="py-24 md:py-32 bg-[#FAF8F4] overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-12">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-[#C8A951]">
          {{ $school['about']['number'] ?? '01' }} &mdash; {{ $school['about']['eyebrow'] ?? 'ABOUT' }}
        </span>
        <span class="flex-grow h-[1px] bg-[#E5E0D8]"></span>
      </div>
    </div>

    <!-- Asymmetric 12-Column Grid: Image cols 1–6, Text cols 8–12 -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
      
      <!-- Left: Portrait / Image Column (Cols 1-6) -->
      <div class="lg:col-span-6 relative">
        <div class="relative border border-[#E5E0D8] bg-white p-2">
          <img src="{{ $school['about']['image'] ?? 'https://placehold.co/800x1000/0B2545/FAF8F4?text=Principal+Portrait' }}"
               alt="{{ $school['about']['principal_name'] ?? 'Principal' }}"
               loading="lazy"
               class="w-full h-[440px] sm:h-[540px] object-cover object-top" />
          
          <!-- Editorial Caption Card -->
          @if(!empty($school['about']['years_badge']))
            <div class="absolute -bottom-6 -right-4 sm:right-6 bg-[#0B2545] text-[#FAF8F4] p-6 border border-[#C8A951]/40 max-w-[240px]">
              <span class="block font-serif text-3xl sm:text-4xl font-semibold text-[#C8A951] leading-none">
                {{ $school['about']['years_badge'] }}+
              </span>
              <span class="block text-xs font-sans uppercase tracking-wider text-white/80 mt-1 leading-snug">
                {{ $school['about']['years_label'] ?? 'Years of Excellence' }}
              </span>
            </div>
          @endif
        </div>
      </div>

      <!-- Right: Narrative & Welcome (Cols 8-12, col 7 is empty gap) -->
      <div class="lg:col-span-5 lg:col-start-8 space-y-6">
        
        <h2 class="font-serif font-semibold text-[#0B2545] tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
          {{ $school['about']['heading'] }}
        </h2>

        <!-- Hairline Accent -->
        <div class="w-16 h-[2px] bg-[#C8A951]"></div>

        <div class="space-y-4 text-[#2D3748] font-sans text-base leading-[1.7] max-w-[62ch]">
          @foreach($school['about']['paragraphs'] as $para)
            <p>{{ $para }}</p>
          @endforeach
        </div>

        <!-- Principal Sign-off Block -->
        @if(!empty($school['about']['principal_name']))
          <div class="pt-6 border-t border-[#E5E0D8]">
            <div class="font-serif italic text-xl text-[#0B2545] font-semibold">
              {{ $school['about']['principal_name'] }}
            </div>
            <div class="text-xs font-sans uppercase tracking-wider text-[#C8A951] mt-0.5">
              {{ $school['about']['principal_title'] }}
            </div>
          </div>
        @endif

      </div>

    </div>

  </div>
</section>

<!-- Full container hairline divider -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="h-[1px] w-full bg-[#E5E0D8]"></div>
</div>
@endif
