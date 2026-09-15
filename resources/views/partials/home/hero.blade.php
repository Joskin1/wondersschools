@php
    $heroImageRaw = \App\Services\FrontendLibrary::get('hero_image', 'https://placehold.co/1920x1080/0B2545/FAF8F4?text=Apex+Crown+College+Scholars+Lagos');
    $heroImage = \App\Services\FrontendLibrary::imageUrl($heroImageRaw, 'https://placehold.co/1920x1080/0B2545/FAF8F4?text=Apex+Crown+College+Scholars+Lagos');
    $heroImageAlt = \App\Services\FrontendLibrary::get('hero_image_alt', (\App\Services\FrontendLibrary::getSetting('school_name', 'Apex Crown College') . ' Scholars Lagos'));
    $heroBadge = \App\Services\FrontendLibrary::get('hero_badge', '2026 / 2027 Academic Session');
    $heroTitle = \App\Services\FrontendLibrary::get('hero_title', 'Nurturing Intellectual Depth & Moral Leadership');
    $heroSubtitle = \App\Services\FrontendLibrary::get('hero_subtitle', 'An accredited British-Nigerian secondary institution committed to scholastic rigor, scientific inquiry, and the formation of character.');
    $heroPrimaryCtaText = \App\Services\FrontendLibrary::get('hero_primary_cta_text', 'Apply for Admission');
    $heroPrimaryCtaLink = \App\Services\FrontendLibrary::get('hero_primary_cta_link', '#admissions');
    $heroSecondaryCtaText = \App\Services\FrontendLibrary::get('hero_secondary_cta_text', 'Explore Prospectus');
    $heroSecondaryCtaLink = \App\Services\FrontendLibrary::get('hero_secondary_cta_link', '#about');
    $heroScrollLabel = \App\Services\FrontendLibrary::get('hero_scroll_label', 'Scroll to explore prospectus');
    $heroLocation = \App\Services\FrontendLibrary::getSetting('school_address', 'Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos State, Nigeria');
@endphp

<!-- ====== Hero Section (Ivy League Prospectus Editorial) ====== -->
<section id="home" class="relative min-h-[85vh] flex flex-col justify-between overflow-hidden bg-ink text-[color:var(--ink-contrast)]">
  
  <!-- Full-bleed background photograph with left-to-right Ink gradient overlay -->
  <div class="absolute inset-0 z-0">
    <img src="{{ $heroImage }}"
         alt="{{ $heroImageAlt }}"
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
      @if(!empty($heroBadge))
        <div class="flex items-center gap-3">
          <span class="w-8 h-[1px] bg-accent"></span>
          <span class="text-xs uppercase tracking-[0.25em] text-accent font-sans font-semibold">
            {{ $heroBadge }}
          </span>
        </div>
      @endif

      <!-- Heading -->
      <h1 class="font-serif font-semibold text-white tracking-tight leading-[1.05]" style="font-size: clamp(2.75rem, 6vw, 5rem);">
        {{ $heroTitle }}
      </h1>

      <!-- One-sentence sub (strictly <= 62ch) -->
      @if(!empty($heroSubtitle))
        <p class="text-base sm:text-lg text-paper/90 max-w-[62ch] font-sans font-normal leading-[1.7]">
          {{ $heroSubtitle }}
        </p>
      @endif

      <!-- Rectangular Buttons -->
      <div class="pt-4 flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
        @if(!empty($heroPrimaryCtaText))
          <a href="{{ $heroPrimaryCtaLink }}"
             class="px-8 py-4 uppercase text-xs sm:text-sm tracking-wider font-sans font-semibold bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition text-center rounded-none shadow-none">
            {{ $heroPrimaryCtaText }}
          </a>
        @endif

        @if(!empty($heroSecondaryCtaText))
          <a href="{{ $heroSecondaryCtaLink }}"
             class="px-8 py-4 uppercase text-xs sm:text-sm tracking-wider font-sans font-semibold border border-white/40 text-white bg-transparent hover:bg-white hover:text-ink transition text-center rounded-none shadow-none">
            {{ $heroSecondaryCtaText }}
          </a>
        @endif
      </div>

    </div>
  </div>

  <!-- Bottom Scroll Indicator & Hairline -->
  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-6 w-full flex items-center justify-between text-xs font-sans tracking-widest text-white/60 uppercase">
    <div class="flex items-center gap-2">
      <span class="w-2 h-2 rounded-full border border-white/40 animate-bounce"></span>
      <span>{{ $heroScrollLabel }}</span>
    </div>
    <div class="hidden sm:block">
      <span>{{ $heroLocation }}</span>
    </div>
  </div>

</section>
