@php
    $aboutEyebrow = \App\Services\FrontendLibrary::get('about_eyebrow', 'ABOUT THE COLLEGE');
    $aboutHeading = \App\Services\FrontendLibrary::get('about_heading', 'A Tradition of Uncompromising Academic Standard');
    $aboutImageRaw = \App\Services\FrontendLibrary::get('about_image', 'https://placehold.co/800x1000/0B2545/FAF8F4?text=Principal+Portrait');
    $aboutImage = \App\Services\FrontendLibrary::imageUrl($aboutImageRaw, 'https://placehold.co/800x1000/0B2545/FAF8F4?text=Principal+Portrait');
    $aboutImageAlt = \App\Services\FrontendLibrary::get('about_image_alt', 'Dr. Mrs. Adebisi Balogun Head of School');
    $aboutYearsBadge = \App\Services\FrontendLibrary::get('about_years_badge', '25');
    $aboutYearsLabel = \App\Services\FrontendLibrary::get('about_years_label', 'Years of Academic Legacy in Lagos');
    $aboutBody = \App\Services\FrontendLibrary::get('about_body', '<p>Founded in 2001, Apex Crown College synthesizes the rigorous Nigerian National Basic & Senior Secondary Curriculum with Cambridge Assessment International standards. We believe secondary education is not simply an examination preparatory phase, but the crucible where character, intellectual curiosity, and self-governance are forged.</p><p>Our dedicated tutorial masters, modern science laboratories, and immersive pastoral mentorship ensure every student discovers their latent gifts and matures into an articulate, disciplined contributor to national and global society.</p>');
    $aboutPrincipalName = \App\Services\FrontendLibrary::get('about_principal_name', 'Dr. (Mrs.) Adebisi Balogun');
    $aboutPrincipalTitle = \App\Services\FrontendLibrary::get('about_principal_title', 'B.Sc, M.Ed, Ph.D. — Principal & Head of School');
@endphp

<!-- ====== 01 — About / Head of School (Prospectus Editorial) ====== -->
<section id="about" class="py-24 md:py-32 bg-paper overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-12">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
          {{ $sectionNumber ?? '01' }} &mdash; {{ $aboutEyebrow }}
        </span>
        <span class="flex-grow h-[1px] bg-rule"></span>
      </div>
    </div>

    <!-- Asymmetric Grid: Image cols 1–6 (if present), Text cols 8–12 (or full width if no image) -->
    <div class="grid grid-cols-1 {{ !empty($aboutImage) ? 'lg:grid-cols-12 gap-12 lg:gap-8 items-center' : 'max-w-3xl' }}">
      
      @if(!empty($aboutImage))
        <!-- Left: Portrait / Image Column (Cols 1-6) -->
        <div class="lg:col-span-6 relative">
          <div class="relative border border-rule bg-white p-2">
            <img src="{{ $aboutImage }}"
                 alt="{{ $aboutImageAlt }}"
                 loading="lazy"
                 class="w-full h-[440px] sm:h-[540px] object-cover object-top" />
            
            <!-- Editorial Caption Card -->
            @if(!empty($aboutYearsBadge))
              <div class="absolute -bottom-6 -right-4 sm:right-6 bg-ink text-paper p-6 border border-accent/40 max-w-[240px]">
                <span class="block font-serif text-3xl sm:text-4xl font-semibold text-accent leading-none">
                  {{ $aboutYearsBadge }}+
                </span>
                <span class="block text-xs font-sans uppercase tracking-wider text-white/80 mt-1 leading-snug">
                  {{ $aboutYearsLabel }}
                </span>
              </div>
            @endif
          </div>
        </div>
      @endif

      <!-- Right: Narrative & Welcome -->
      <div class="{{ !empty($aboutImage) ? 'lg:col-span-5 lg:col-start-8' : '' }} space-y-6">
        
        <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
          {{ $aboutHeading }}
        </h2>

        <!-- Hairline Accent -->
        <div class="w-16 h-[2px] bg-accent"></div>

        <div class="space-y-4 text-body font-sans text-base leading-[1.7] max-w-[62ch]">
          {!! $aboutBody !!}
        </div>

        <!-- Principal Sign-off Block -->
        @if(!empty($aboutPrincipalName))
          <div class="pt-6 border-t border-rule">
            <div class="font-serif italic text-xl text-ink font-semibold">
              {{ $aboutPrincipalName }}
            </div>
            @if(!empty($aboutPrincipalTitle))
              <div class="text-xs font-sans uppercase tracking-wider text-accent mt-0.5">
                {{ $aboutPrincipalTitle }}
              </div>
            @endif
          </div>
        @endif

      </div>

    </div>

  </div>
</section>

<!-- Full container hairline divider -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="h-[1px] w-full bg-rule"></div>
</div>
