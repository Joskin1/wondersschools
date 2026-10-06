@php
    $admissionsEyebrow = \App\Services\FrontendLibrary::get('admissions_cta_eyebrow', 'ADMISSIONS 2026 / 2027');
    $admissionsHeading = \App\Services\FrontendLibrary::get('admissions_cta_heading', 'Enroll Your Child in a Tradition of Distinction');
    $admissionsSubtitle = \App\Services\FrontendLibrary::get('admissions_cta_subtitle', 'Applications are now being received for JSS 1 and limited transfer vacancies into JSS 2 and SSS 1. Day and Full-Boarding options available.');
    $admissionsSteps = \App\Services\FrontendLibrary::getJson('admissions_cta_steps', [
        ['num' => '01', 'title' => 'Obtain Form', 'desc' => 'Complete the online application or purchase the dossier at the campus Registry.'],
        ['num' => '02', 'title' => 'Entrance Assessment', 'desc' => 'Candidate attends the written examination in Mathematics, English, and Aptitude.'],
        ['num' => '03', 'title' => 'Admission Offer', 'desc' => 'Successful applicants receive formal letters of admission within 5 working days.'],
        ['num' => '04', 'title' => 'Resumption & Induction', 'desc' => 'Scholars check in for the matriculation orientation and academic commencement.'],
    ]);
    $admissionsPrimaryBtn = \App\Services\FrontendLibrary::get('admissions_cta_primary_btn', 'Begin Online Application');
    $admissionsPrimaryLink = \App\Services\FrontendLibrary::get('admissions_cta_primary_link', '#contact');
    $admissionsSecondaryBtn = \App\Services\FrontendLibrary::get('admissions_cta_secondary_btn', 'Download Prospectus (PDF)');
    $admissionsSecondaryLink = \App\Services\FrontendLibrary::get('admissions_cta_secondary_link', '#contact');
@endphp

<!-- ====== Admissions Call to Action (Full-Bleed Ink, Centred by Design) ====== -->
<section id="admissions" class="py-24 md:py-32 bg-ink text-[color:var(--ink-contrast)] relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
    
    <!-- Centred CTA Headline (The single intentional centred block in prospectus design) -->
    <div class="max-w-3xl mx-auto text-center space-y-6">
      
      @if(!empty($admissionsEyebrow))
        <div class="flex items-center justify-center gap-3">
          <span class="w-8 h-[1px] bg-accent"></span>
          <span class="text-xs uppercase tracking-[0.25em] text-accent font-sans font-bold">
            {{ $admissionsEyebrow }}
          </span>
          <span class="w-8 h-[1px] bg-accent"></span>
        </div>
      @endif

      <h2 class="font-serif font-semibold text-white tracking-tight leading-[1.15]" style="font-size: clamp(2.25rem, 5vw, 3.5rem);">
        {{ $admissionsHeading }}
      </h2>

      @if(!empty($admissionsSubtitle))
        <p class="text-base sm:text-lg text-white/85 font-sans leading-[1.7] max-w-[62ch] mx-auto">
          {{ $admissionsSubtitle }}
        </p>
      @endif

    </div>

    <!-- 4-Step Application Protocol Grid -->
    @if(!empty($admissionsSteps))
      <div class="mt-16 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 border-t border-b border-white/15 py-10">
        @foreach($admissionsSteps as $step)
          <div class="space-y-2 text-left">
            <span class="font-serif text-2xl font-semibold text-accent block">{{ $step['num'] ?? sprintf('%02d', $loop->iteration) }}</span>
            <h3 class="font-serif text-base font-semibold text-white">{{ $step['title'] ?? '' }}</h3>
            <p class="text-xs text-white/70 font-sans leading-relaxed">{{ $step['desc'] ?? '' }}</p>
          </div>
        @endforeach
      </div>
    @endif

    <!-- CTA Actions -->
    <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-4">
      @if(!empty($admissionsPrimaryBtn))
        <a href="{{ $admissionsPrimaryLink }}"
           class="w-full sm:w-auto px-10 py-4 uppercase text-xs sm:text-sm tracking-widest font-sans font-semibold bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition text-center rounded-none shadow-none">
          {{ $admissionsPrimaryBtn }}
        </a>
      @endif
      @if(!empty($admissionsSecondaryBtn))
        <a href="{{ $admissionsSecondaryLink }}"
           class="w-full sm:w-auto px-10 py-4 uppercase text-xs sm:text-sm tracking-widest font-sans font-semibold border border-white/40 text-white bg-transparent hover:bg-white hover:text-ink transition text-center rounded-none shadow-none">
          {{ $admissionsSecondaryBtn }}
        </a>
      @endif
    </div>

  </div>
</section>
