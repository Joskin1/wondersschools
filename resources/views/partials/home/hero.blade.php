@if(!empty($school['hero']))
<!-- ====== Hero Section (Ivy League Prospectus Editorial) ====== -->
<section id="home" class="relative min-h-[85vh] flex flex-col justify-between overflow-hidden bg-ink text-[color:var(--ink-contrast)]">
  
  <!-- Full-bleed background photograph with left-to-right Ink gradient overlay -->
  <div class="absolute inset-0 z-0">
    <img src="{{ $school['hero']['image'] ?? 'https://placehold.co/1920x1080/0B2545/FAF8F4?text=Apex+Crown+College+Scholars+Lagos' }}"
         alt="{{ $school['name'] ?? 'College' }} Campus"
         class="w-full h-full object-cover object-center" />
    <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/85 md:via-ink/70 to-ink/30"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-ink via-transparent to-black/20"></div>
  </div>

  <!-- Empty top spacer -->
  <div></div>

  <!-- Text block in the lower-left third -->
  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24 w-full">
    <div class="max-w-3xl space-y-6">
      
      <!-- Eyebrow -->
      @if(!empty($school['hero']['badge']))
        <div class="flex items-center gap-3">
          <span class="w-8 h-[1px] bg-accent"></span>
          <span class="text-xs uppercase tracking-[0.25em] text-accent font-sans font-semibold">
            {{ $school['hero']['badge'] }}
          </span>
        </div>
      @endif

      <!-- Heading -->
      <h1 class="font-serif font-semibold text-white tracking-tight leading-[1.05]" style="font-size: clamp(2.75rem, 6vw, 5rem);">
        {{ $school['hero']['title'] }}
      </h1>

      <!-- One-sentence sub (strictly <= 62ch) -->
      @if(!empty($school['hero']['subtitle']))
        <p class="text-base sm:text-lg text-paper/90 max-w-[62ch] font-sans font-normal leading-[1.7]">
          {{ $school['hero']['subtitle'] }}
        </p>
      @endif

      <!-- Rectangular Buttons -->
      <div class="pt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
        @if(!empty($school['hero']['primary_cta_text']))
          <a href="{{ $school['hero']['primary_cta_link'] ?? '#admissions' }}"
             class="px-8 py-4 uppercase text-xs sm:text-sm tracking-wider font-sans font-semibold bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition text-center rounded-none shadow-none">
            {{ $school['hero']['primary_cta_text'] }}
          </a>
        @endif

        @if(!empty($school['hero']['secondary_cta_text']))
          <a href="{{ $school['hero']['secondary_cta_link'] ?? '#about' }}"
             class="px-8 py-4 uppercase text-xs sm:text-sm tracking-wider font-sans font-semibold border border-white/40 text-white bg-transparent hover:bg-white hover:text-ink transition text-center rounded-none shadow-none">
            {{ $school['hero']['secondary_cta_text'] }}
          </a>
        @endif
      </div>

    </div>
  </div>

  <!-- Bottom Scroll Indicator & Hairline -->
  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-6 w-full flex items-center justify-between text-xs font-sans tracking-widest text-white/60 uppercase">
    <div class="flex items-center gap-2">
      <span class="w-2 h-2 rounded-full border border-white/40 animate-bounce"></span>
      <span>Scroll to explore prospectus</span>
    </div>
    <div class="hidden sm:block">
      <span>{{ $school['location'] ?? 'Lagos, Nigeria' }}</span>
    </div>
  </div>

</section>
@endif
