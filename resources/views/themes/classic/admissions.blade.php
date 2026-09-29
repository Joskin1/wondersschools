@php
    $admissionsEyebrow = \App\Services\FrontendLibrary::get('admissions_cta_eyebrow', 'ENROLLMENT PROTOCOL');
    $admissionsHeading = \App\Services\FrontendLibrary::get('admissions_cta_heading', 'Admissions & Entrance Examination Protocol');
    $admissionsSubtitle = \App\Services\FrontendLibrary::get('admissions_cta_subtitle', 'Applications are actively invited for Early Years, Primary, and Junior/Senior Secondary enrollment into the 2026/2027 academic session.');
    $admissionsSteps = \App\Services\FrontendLibrary::getJson('admissions_steps', [
        [
            'num'   => '01',
            'title' => 'Obtain Application Package',
            'desc'  => 'Pick up an admission package from the College Registry on Ejinrin Road, Ijebu-Ode, or complete the online inquiry form below.',
        ],
        [
            'num'   => '02',
            'title' => 'Entrance Assessment',
            'desc'  => 'Prospective students complete standard diagnostic assessments in Mathematics, English Language, and General Aptitude.',
        ],
        [
            'num'   => '03',
            'title' => 'Parent & Student Interview',
            'desc'  => 'A personal interaction session with the Academic Board to align expectations, values, and student support requirements.',
        ],
        [
            'num'   => '04',
            'title' => 'Offer of Admission & Clearance',
            'desc'  => 'Successful applicants receive official letters of provisional admission with orientation guidelines and curriculum kits.',
        ],
    ]);
@endphp

<!-- ====== Classic Theme Admissions Section ====== -->
<section id="admissions" class="py-16 sm:py-24 bg-paper relative">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-3 mb-12 sm:mb-16">
            <div class="text-xs font-sans uppercase tracking-[0.25em] text-accent font-bold">
                {{ $admissionsEyebrow }}
            </div>
            <h2 class="font-serif font-normal text-ink text-2xl sm:text-3xl lg:text-4xl leading-tight">
                {{ $admissionsHeading }}
            </h2>
            <div class="flex items-center justify-center gap-3 pt-1">
                <span class="w-10 h-[1px] bg-accent/40"></span>
                <span class="text-accent text-xs">◆</span>
                <span class="w-10 h-[1px] bg-accent/40"></span>
            </div>
            @if(!empty($admissionsSubtitle))
                <p class="text-xs sm:text-sm text-body/80 font-serif leading-relaxed max-w-2xl mx-auto pt-2">
                    {{ $admissionsSubtitle }}
                </p>
            @endif
        </div>

        <!-- 4 Step Boxes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($admissionsSteps as $step)
                <div class="p-6 border border-rule bg-white shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="font-serif text-accent text-xl font-bold mb-3">
                            Step {{ $step['num'] ?? '01' }}
                        </div>
                        <h3 class="font-serif font-semibold text-base text-ink mb-2">
                            {{ $step['title'] ?? '' }}
                        </h3>
                        <p class="font-sans text-xs text-body/80 leading-relaxed">
                            {{ $step['desc'] ?? '' }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Formal CTA Box -->
        <div class="mt-12 p-8 border-2 border-accent/40 bg-ink text-white text-center max-w-3xl mx-auto shadow-lg">
            <h3 class="font-serif text-xl text-white mb-2">2026/2027 Admissions Registration Open</h3>
            <p class="text-xs font-sans text-paper/80 max-w-xl mx-auto mb-6">
                Prospective parents and guardians are invited to visit the College Registry or submit an electronic inquiry.
            </p>
            <a href="#contact" class="inline-block px-8 py-3 uppercase text-xs tracking-widest font-sans font-bold bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition border border-accent">
                Submit Electronic Inquiry
            </a>
        </div>

    </div>
</section>
