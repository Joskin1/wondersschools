@if(!empty($school['testimonials']))
<!-- ====== 07 — Perspectives (Editorial Testimonial Layout) ====== -->
<section id="testimonials" class="py-24 md:py-32 bg-[#FAF8F4]"
         x-data="{ 
            active: 0,
            items: {{ Js::from($school['testimonials']['items']) }},
            next() { this.active = (this.active + 1) % this.items.length; },
            prev() { this.active = (this.active - 1 + this.items.length) % this.items.length; }
         }">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-[#C8A951]">
          {{ $school['testimonials']['number'] ?? '07' }} &mdash; {{ $school['testimonials']['eyebrow'] ?? 'PERSPECTIVES' }}
        </span>
        <span class="flex-grow h-[1px] bg-[#E5E0D8]"></span>
      </div>
    </div>

    <!-- 12-Column Asymmetric Quote Display -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-start">
      
      <!-- Left Column: Section Title & Controls (Cols 1-4) -->
      <div class="lg:col-span-4 space-y-6">
        <h2 class="font-serif font-semibold text-[#0B2545] tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
          {{ $school['testimonials']['heading'] }}
        </h2>
        
        <p class="text-xs sm:text-sm text-[#2D3748]/80 font-sans leading-[1.7] max-w-[62ch]">
          Reflections from parents, guardians, and alumni who have experienced the transformative impact of our community.
        </p>

        <!-- Pagination Controls -->
        <div class="pt-4 flex items-center gap-4">
          <button @click="prev()" type="button" class="w-11 h-11 border border-[#0B2545] text-[#0B2545] hover:bg-[#0B2545] hover:text-[#FAF8F4] transition flex items-center justify-center text-sm font-serif">
            &larr;
          </button>
          <span class="text-xs font-sans uppercase tracking-widest text-[#0B2545] font-semibold">
            <span x-text="active + 1"></span> / <span x-text="items.length"></span>
          </span>
          <button @click="next()" type="button" class="w-11 h-11 border border-[#0B2545] text-[#0B2545] hover:bg-[#0B2545] hover:text-[#FAF8F4] transition flex items-center justify-center text-sm font-serif">
            &rarr;
          </button>
        </div>
      </div>

      <!-- Right Column: Editorial Serif Quote Box (Cols 6-12) -->
      <div class="lg:col-span-7 lg:col-start-6">
        <div class="border-l-2 border-[#C8A951] pl-8 sm:pl-12 py-4 space-y-8">
          
          <blockquote class="font-serif text-xl sm:text-2xl text-[#0B2545] font-normal italic leading-relaxed max-w-[62ch]">
            &ldquo;<span x-text="items[active].quote"></span>&rdquo;
          </blockquote>

          <div class="pt-4 border-t border-[#E5E0D8]">
            <div class="font-serif text-base sm:text-lg font-semibold text-[#0B2545]" x-text="items[active].author"></div>
            <div class="text-xs font-sans uppercase tracking-wider text-[#C8A951] mt-0.5" x-text="items[active].role"></div>
          </div>

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
