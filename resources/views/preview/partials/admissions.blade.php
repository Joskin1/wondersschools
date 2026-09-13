@if(!empty($school['admissions_cta']))
<!-- ====== Admissions Call to Action (Full-Bleed Ink, Centred by Design) ====== -->
<section id="admissions" class="py-24 md:py-32 bg-[#0B2545] text-[#FAF8F4] relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
    
    <!-- Centred CTA Headline (The single intentional centred block in prospectus design) -->
    <div class="max-w-3xl mx-auto text-center space-y-6">
      
      <div class="flex items-center justify-center gap-3">
        <span class="w-8 h-[1px] bg-[#C8A951]"></span>
        <span class="text-xs uppercase tracking-[0.25em] text-[#C8A951] font-sans font-bold">
          {{ $school['admissions_cta']['eyebrow'] ?? 'ADMISSIONS' }}
        </span>
        <span class="w-8 h-[1px] bg-[#C8A951]"></span>
      </div>

      <h2 class="font-serif font-semibold text-white tracking-tight leading-[1.15]" style="font-size: clamp(2.25rem, 5vw, 3.5rem);">
        {{ $school['admissions_cta']['heading'] }}
      </h2>

      @if(!empty($school['admissions_cta']['subtitle']))
        <p class="text-base sm:text-lg text-white/85 font-sans leading-[1.7] max-w-[62ch] mx-auto">
          {{ $school['admissions_cta']['subtitle'] }}
        </p>
      @endif

    </div>

    <!-- 4-Step Application Protocol Grid -->
    @if(!empty($school['admissions_cta']['steps']))
      <div class="mt-16 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 border-t border-b border-white/15 py-10">
        @foreach($school['admissions_cta']['steps'] as $step)
          <div class="space-y-2 text-left">
            <span class="font-serif text-2xl font-semibold text-[#C8A951] block">{{ $step['num'] }}</span>
            <h3 class="font-serif text-base font-semibold text-white">{{ $step['title'] }}</h3>
            <p class="text-xs text-white/70 font-sans leading-relaxed">{{ $step['desc'] }}</p>
          </div>
        @endforeach
      </div>
    @endif

    <!-- CTA Actions -->
    <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4">
      <a href="#contact"
         class="w-full sm:w-auto px-10 py-4 uppercase text-xs sm:text-sm tracking-widest font-sans font-semibold bg-[#C8A951] text-[#0B2545] hover:bg-[#b89840] transition text-center rounded-none shadow-none">
        {{ $school['admissions_cta']['primary_btn'] ?? 'Begin Online Application' }}
      </a>
      <a href="#contact"
         class="w-full sm:w-auto px-10 py-4 uppercase text-xs sm:text-sm tracking-widest font-sans font-semibold border border-white/40 text-white bg-transparent hover:bg-white hover:text-[#0B2545] transition text-center rounded-none shadow-none">
        {{ $school['admissions_cta']['secondary_btn'] ?? 'Download Prospectus (PDF)' }}
      </a>
    </div>

  </div>
</section>
@endif
