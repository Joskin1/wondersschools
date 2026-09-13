@if(!empty($school['academics']))
<!-- ====== 04 — Curriculum & Programmes (Editorial Prospectus) ====== -->
<section id="academics" class="py-24 md:py-32 bg-paper">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
          {{ $school['academics']['number'] ?? '04' }} &mdash; {{ $school['academics']['eyebrow'] ?? 'CURRICULUM' }}
        </span>
        <span class="flex-grow h-[1px] bg-rule"></span>
      </div>
    </div>

    <!-- Section Heading & Intro -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-16 items-baseline">
      <div class="lg:col-span-6">
        <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
          {{ $school['academics']['heading'] }}
        </h2>
      </div>
      <div class="lg:col-span-6">
        <p class="text-sm sm:text-base text-body font-sans leading-[1.7] max-w-[62ch]">
          {{ $school['academics']['intro'] }}
        </p>
      </div>
    </div>

    <!-- 3 Programme Cards: Hairline borders, White card fill, Sharp corners -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      @foreach($school['academics']['tracks'] as $track)
        <div class="bg-white border border-rule p-8 sm:p-10 flex flex-col justify-between space-y-8 rounded-none">
          
          <div class="space-y-6">
            <!-- Header Block -->
            <div class="pb-6 border-b border-rule space-y-1.5">
              <span class="text-xs uppercase tracking-[0.15em] text-accent font-sans font-bold block">
                {{ $track['code'] }} &bull; {{ $track['ages'] }}
              </span>
              <h3 class="font-serif text-2xl font-semibold text-ink">
                {{ $track['name'] }}
              </h3>
              <span class="inline-block text-xs font-sans text-ink/70 font-medium">
                Qualifications: <strong class="text-ink">{{ $track['certs'] }}</strong>
              </span>
            </div>

            <!-- Description -->
            <p class="text-xs sm:text-sm text-body/90 font-sans leading-[1.7] max-w-[62ch]">
              {{ $track['desc'] }}
            </p>

            <!-- Subject Syllabus List -->
            <div class="space-y-2">
              <span class="text-[11px] uppercase tracking-wider text-ink font-sans font-bold block">
                Core Disciplines:
              </span>
              <ul class="grid grid-cols-1 gap-1.5 text-xs text-body font-sans">
                @foreach($track['subjects'] as $subject)
                  <li class="flex items-center gap-2">
                    <span class="w-1 h-1 bg-accent"></span>
                    <span>{{ $subject }}</span>
                  </li>
                @endforeach
              </ul>
            </div>
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
@endif
