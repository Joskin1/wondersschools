@php
    $academicsEyebrow = \App\Services\FrontendLibrary::get('academics_eyebrow', 'CURRICULUM & PROGRAMMES');
    $academicsHeading = \App\Services\FrontendLibrary::get('academics_heading', 'Structured Pathways for Secondary Scholars');
    $academicsIntro = \App\Services\FrontendLibrary::get('academics_intro', 'A comprehensive curriculum designed to build foundational mastery in the junior years and deep specialization in the senior years.');
    $academicsTracks = \App\Services\FrontendLibrary::getJson('academics_tracks', [
        [
            'code'     => 'JSS 1 — JSS 3',
            'name'     => 'Junior Secondary School',
            'ages'     => 'Ages 10 — 13 Years',
            'certs'    => 'BECE & Cambridge Checkpoint',
            'desc'     => 'Focuses on foundational intellectual development: computational thinking, language mastery, basic science, and cultural appreciation.',
            'subjects' => ['General Mathematics', 'English & Literature', 'Basic Science & Tech', 'Coding Basics', 'French & Languages', 'Business Studies'],
        ],
        [
            'code'     => 'SSS 1 — SSS 3',
            'name'     => 'Senior Sciences & Technology',
            'ages'     => 'Ages 13 — 17 Years',
            'certs'    => 'WAEC, NECO, IGCSE & JAMB',
            'desc'     => 'Rigorous scientific inquiry for aspiring medical doctors, software architects, agricultural biotechnologists, and structural engineers.',
            'subjects' => ['Further Mathematics', 'Physics & Chemistry', 'Biology & Agric', 'Technical Drawing', 'Data Processing', 'Weekly Practical Labs'],
        ],
        [
            'code'     => 'SSS 1 — SSS 3',
            'name'     => 'Senior Arts & Commercial Studies',
            'ages'     => 'Ages 13 — 17 Years',
            'certs'    => 'WAEC, NECO, IGCSE & JAMB',
            'desc'     => 'For future jurists, economists, chartered accountants, diplomats, and business leaders with intensive essay and analysis training.',
            'subjects' => ['Literature in English', 'Government & History', 'Financial Accounting', 'Economics & Commerce', 'Visual Arts & Music', 'Debating Society'],
        ],
    ]);
@endphp

<!-- ====== 04 — Curriculum & Programmes (Editorial Prospectus) ====== -->
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

    <!-- 3 Programme Cards: Hairline borders, White card fill, Sharp corners -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
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
