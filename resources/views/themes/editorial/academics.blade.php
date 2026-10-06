@php
    $academicsEyebrow = \App\Services\FrontendLibrary::get('academics_eyebrow', 'CURRICULUM & PROGRAMMES');
    $academicsHeading = \App\Services\FrontendLibrary::get('academics_heading', 'Structured Pathways for Secondary Scholars');
    $academicsIntro = \App\Services\FrontendLibrary::get('academics_intro', 'A comprehensive curriculum designed to build foundational mastery in the junior years and deep specialization in the senior years.');
    $academicsTracks = \App\Services\FrontendLibrary::getJson('academics_tracks', [
        [
            'code'     => 'CRECHE • NURSERY • KG',
            'name'     => 'Preschool & Playgroup',
            'ages'     => 'Ages 18 Mo — 5 Yrs',
            'certs'    => 'Early Years Foundation Stage (EYFS)',
            'desc'     => 'A warm, stimulating environment fostering social confidence, early phonics (Jolly Phonics), sensory discovery, and foundational cognitive readiness.',
            'subjects' => ['Jolly Phonics & Pre-Reading', 'Early Numeracy & Shapes', 'Sensory Exploration & Play', 'Rhymes & Creative Arts', 'Fine Motor Skills & Etiquette', 'French & Music Intro'],
        ],
        [
            'code'     => 'BASIC 1 — BASIC 6',
            'name'     => 'Primary Basic School',
            'ages'     => 'Ages 5 — 11 Years',
            'certs'    => 'National Common Entrance & CAS',
            'desc'     => 'A blended British-Nigerian curriculum building solid competencies in computational thinking, quantitative reasoning, basic science, coding, and articulate communication.',
            'subjects' => ['Mathematics & Quantitative', 'English & Verbal Reasoning', 'Basic Science & Technology', 'Coding & Computer Studies', 'French & Civic Education', 'Agricultural Science'],
        ],
        [
            'code'     => 'JSS 1 — SSS 3',
            'name'     => 'Secondary College',
            'ages'     => 'Ages 11 — 17 Years',
            'certs'    => 'WAEC, NECO, BECE & JAMB',
            'desc'     => 'Rigorous junior and senior secondary education preparing scholars for university excellence with specialized tracks in Sciences, Commercial Studies, and Humanities.',
            'subjects' => ['Mathematics & Further Maths', 'Physics, Chemistry & Biology', 'Literature & Government', 'Financial Accounting & Commerce', 'Technical Drawing & Data Proc.', 'Weekly Practical Labs'],
        ],
    ]);
@endphp

<!-- ====== 04 — Academic Divisions & Programmes (Editorial Prospectus) ====== -->
<section id="academics" class="py-24 md:py-32 bg-paper">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
          {{ $sectionNumber ?? '04' }} &mdash; {{ $academicsEyebrow }}
        </span>
        <span class="flex-grow h-[1px] bg-rule"></span>
      </div>
    </div>

    <!-- Section Heading & Intro -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-16 items-baseline">
      <div class="lg:col-span-6">
        <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
          {{ $academicsHeading }}
        </h2>
      </div>
      <div class="lg:col-span-6">
        <p class="text-sm sm:text-base text-body font-sans leading-[1.7] max-w-[62ch]">
          {{ $academicsIntro }}
        </p>
      </div>
    </div>

    <!-- Programme Cards Grid: Hairline borders, White card fill, Sharp corners -->
    <div class="grid {{ count($academicsTracks) === 4 ? 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4' : (count($academicsTracks) === 2 ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1 lg:grid-cols-3') }} gap-8">
      
      @foreach($academicsTracks as $track)
        <div class="bg-white border border-rule p-8 sm:p-10 flex flex-col justify-between space-y-8 rounded-none">
          
          <div class="space-y-6">
            <!-- Header Block -->
            <div class="pb-6 border-b border-rule space-y-1.5">
              <span class="text-xs uppercase tracking-[0.15em] text-accent font-sans font-bold block">
                {{ $track['code'] ?? '' }} &bull; {{ $track['ages'] ?? '' }}
              </span>
              <h3 class="font-serif text-2xl font-semibold text-ink">
                {{ $track['name'] ?? '' }}
              </h3>
              @if(!empty($track['certs']))
                <span class="inline-block text-xs font-sans text-ink/70 font-medium">
                  Qualifications: <strong class="text-ink">{{ $track['certs'] }}</strong>
                </span>
              @endif
            </div>

            <!-- Description -->
            <p class="text-xs sm:text-sm text-body/90 font-sans leading-[1.7] max-w-[62ch]">
              {{ $track['desc'] ?? '' }}
            </p>

            <!-- Subject Syllabus List -->
            @if(!empty($track['subjects']))
              <div class="space-y-2">
                <span class="text-[11px] uppercase tracking-wider text-ink font-sans font-bold block">
                  Core Disciplines:
                </span>
                <ul class="grid grid-cols-1 gap-1.5 text-xs text-body font-sans">
                  @foreach((array)$track['subjects'] as $subject)
                    <li class="flex items-center gap-2">
                      <span class="w-1 h-1 bg-accent"></span>
                      <span>{{ $subject }}</span>
                    </li>
                  @endforeach
                </ul>
              </div>
            @endif
          </div>

          <!-- Apply Trigger Button -->
          <div class="pt-6 border-t border-rule">
            <a href="#admissions"
               class="inline-block w-full py-3 px-4 text-center text-xs uppercase tracking-widest font-sans font-semibold border border-ink text-ink hover:bg-ink hover:text-paper transition rounded-none">
              Admission Requirements &rarr;
            </a>
          </div>

        </div>
      @endforeach

    </div>

  </div>
</section>

<!-- Full container hairline divider -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="h-[1px] w-full bg-rule"></div>
</div>
