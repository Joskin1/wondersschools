@php
    $featuresEyebrow = \App\Services\FrontendLibrary::get('features_eyebrow', 'DISTINCTIVES');
    $featuresHeading = \App\Services\FrontendLibrary::get('features_heading', 'The Pillars of an Apex Crown Education');
    $featuresIntro = \App\Services\FrontendLibrary::get('features_intro', 'A deliberate blend of academic depth, moral discipline, and technological literacy structured to cultivate leaders.');
    $featuresCtaText = \App\Services\FrontendLibrary::get('features_cta_text', 'Review Full Curriculum');
    $featuresCtaLink = \App\Services\FrontendLibrary::get('features_cta_link', '#academics');
    $featuresItems = \App\Services\FrontendLibrary::getJson('features_items', [
        [
            'title' => 'Integrated Dual Curriculum',
            'desc'  => 'Simultaneous mastery of the Nigerian National Curriculum (WAEC & NECO) alongside British Cambridge Checkpoint and IGCSE examinations.',
        ],
        [
            'title' => 'Individualized Tutorial Mentorship',
            'desc'  => 'Strict 1:12 faculty-to-student ratio ensuring individualized attention, customized academic interventions, and dedicated pastoral tutors.',
        ],
        [
            'title' => 'Applied STEM & Computational Thinking',
            'desc'  => 'Purpose-built laboratories for physics, chemistry, biology, agricultural science, and dedicated robotics/coding suites.',
        ],
        [
            'title' => 'Moral Formation & Character Discipline',
            'desc'  => 'Uncompromising emphasis on integrity, punctuality, self-respect, civic responsibility, and community service.',
        ],
        [
            'title' => 'Comprehensive Boarding & Pastoral Care',
            'desc'  => 'Modern, secure air-conditioned dormitories with round-the-clock power, resident housemasters, and multi-course nutritional dining.',
        ],
        [
            'title' => 'Oratory, Athletics & Cultural Life',
            'desc'  => 'Weekly parliamentary debating, orchestral music tuition, Model United Nations, and championship track and field athletics.',
        ],
    ]);
@endphp

<!-- ====== 02 — Distinctives (Editorial Table of Contents Layout) ====== -->
<section id="features" class="py-24 md:py-32 bg-paper">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
          02 &mdash; {{ $featuresEyebrow }}
        </span>
        <span class="flex-grow h-[1px] bg-rule"></span>
      </div>
    </div>

    <!-- 12-Column Grid: Headings (Cols 1-4), Editorial Rows (Cols 6-12) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8">
      
      <!-- Left Heading Block (Cols 1-4) -->
      <div class="lg:col-span-4 space-y-4">
        <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
          {{ $featuresHeading }}
        </h2>
        @if(!empty($featuresIntro))
          <p class="text-sm sm:text-base text-body font-sans leading-[1.7] max-w-[62ch]">
            {{ $featuresIntro }}
          </p>
        @endif
        @if(!empty($featuresCtaText))
          <div class="pt-4">
            <a href="{{ $featuresCtaLink }}" class="inline-flex items-center gap-2 text-xs uppercase tracking-widest font-sans font-semibold text-ink hover:text-accent transition">
              <span>{{ $featuresCtaText }}</span>
              <span>&rarr;</span>
            </a>
          </div>
        @endif
      </div>

      <!-- Right 2-Column Table of Contents Style Rows (Cols 6-12) -->
      <div class="lg:col-span-7 lg:col-start-6">
        <div class="divide-y divide-rule border-t border-b border-rule">
          
          @foreach($featuresItems as $item)
            <div class="py-6 sm:py-8 grid grid-cols-1 sm:grid-cols-12 gap-4 items-baseline group hover:bg-paper/80 transition">
              
              <!-- Number Column (2 cols) -->
              <div class="sm:col-span-2 font-serif text-lg sm:text-xl font-semibold text-accent">
                {{ sprintf('%02d', $loop->iteration) }}
              </div>

              <!-- Title & Description Column (10 cols) -->
              <div class="sm:col-span-10 space-y-1.5">
                <h3 class="font-serif text-lg sm:text-xl font-semibold text-ink group-hover:text-accent transition">
                  {{ $item['title'] ?? '' }}
                </h3>
                <p class="text-xs sm:text-sm text-body/80 font-sans leading-[1.7] max-w-[62ch]">
                  {{ $item['desc'] ?? '' }}
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
  <div class="h-[1px] w-full bg-rule"></div>
</div>
