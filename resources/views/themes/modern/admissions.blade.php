@php
    $admissionsEyebrow = \App\Services\FrontendLibrary::get('admissions_cta_eyebrow', 'ENROLLMENT PROTOCOL');
    $admissionsHeading = \App\Services\FrontendLibrary::get('admissions_cta_heading', 'Begin Your Scholar’s Journey Today');
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

<!-- ====== Modern Theme Admissions Section ====== -->
<section id="admissions" class="py-16 sm:py-24 bg-slate-50 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-12 sm:mb-16">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-accent/15 text-accent text-xs font-sans font-bold uppercase tracking-wider">
                <span>{{ $admissionsEyebrow }}</span>
            </div>
            <h2 class="font-sans font-extrabold text-ink text-2xl sm:text-3xl lg:text-4xl tracking-tight leading-tight">
                {{ $admissionsHeading }}
            </h2>
            @if(!empty($admissionsSubtitle))
                <p class="text-sm sm:text-base text-slate-600 font-sans leading-relaxed">
                    {{ $admissionsSubtitle }}
                </p>
            @endif
        </div>

        <!-- 4-Step Modern Roadmap Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 sm:gap-8">
            @foreach($admissionsSteps as $step)
                <div class="p-7 rounded-3xl bg-white border border-slate-100 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between group">
                    <div>
                        <!-- Step Badge -->
                        <div class="w-12 h-12 rounded-2xl bg-accent text-[color:var(--accent-contrast)] flex items-center justify-center font-sans font-extrabold text-base mb-6 shadow-md group-hover:scale-110 transition-transform">
                            {{ $step['num'] ?? '01' }}
                        </div>
                        <h3 class="font-sans font-bold text-base sm:text-lg text-ink mb-3 group-hover:text-accent transition-colors">
                            {{ $step['title'] ?? '' }}
                        </h3>
                        <p class="font-sans text-xs sm:text-sm text-slate-600 leading-relaxed">
                            {{ $step['desc'] ?? '' }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Central Action Card -->
        <div class="mt-12 sm:mt-16 p-8 sm:p-10 rounded-3xl bg-ink text-white text-center max-w-3xl mx-auto shadow-xl relative overflow-hidden">
            <div class="relative z-10 space-y-4">
                <h3 class="font-sans font-extrabold text-xl sm:text-2xl text-white">Ready to Apply for the 2026/2027 Academic Session?</h3>
                <p class="text-xs sm:text-sm text-paper/80 font-sans max-w-xl mx-auto">
                    Limited vacancies available across Nursery, Primary, Junior and Senior Secondary classes.
                </p>
                <div class="pt-2 flex flex-wrap justify-center gap-4">
                    <a href="#contact" class="px-8 py-3.5 rounded-full font-sans font-bold text-sm bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition-all shadow-md">
                        Submit Online Admission Inquiry
                    </a>
                </div>
            </div>
        </div>

    </div>
</section>
