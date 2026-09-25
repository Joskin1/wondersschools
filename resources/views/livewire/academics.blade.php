@php
    $tenantName = function_exists('tenant') && tenant('name') ? tenant('name') : null;
    $defaultSchoolName = $tenantName ?? config('app.name', 'Wonders Kiddies Foundation Schools');
@endphp

<div>
    <!-- ====== 01 — Subpage Header Banner ====== -->
    <section class="relative bg-ink text-paper py-20 sm:py-28 overflow-hidden border-b border-rule">
        <div class="absolute inset-0 z-0 opacity-15">
            <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&w=1920&q=80"
                 alt="Scholastic Rigor"
                 class="w-full h-full object-cover object-center" />
            <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/90 to-ink/70"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-[1px] bg-accent"></span>
                    <span class="text-xs uppercase tracking-[0.25em] text-accent font-sans font-semibold">
                        Curricular Architecture & Programmes
                    </span>
                </div>
                <h1 class="font-serif font-semibold text-white tracking-tight leading-[1.1]" style="font-size: clamp(2.5rem, 5vw, 4rem);">
                    {{ \App\Services\FrontendLibrary::get('advantage_hero_title', 'The WKFS Advantage') }}
                </h1>
                <p class="text-base sm:text-lg text-paper/80 font-sans leading-relaxed max-w-[62ch]">
                    {{ \App\Services\FrontendLibrary::get('advantage_hero_subtitle', 'A Foundation That Outlasts Trends.') }}
                </p>
            </div>
        </div>
    </section>

    <!-- ====== 02 — Curricular Ethos & Intro ====== -->
    <section class="py-20 sm:py-24 bg-paper">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <div class="flex items-center gap-3">
                    <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
                        01 &mdash; SCHOLASTIC PHILOSOPHY
                    </span>
                    <span class="flex-grow h-[1px] bg-rule"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-start">
                <div class="lg:col-span-7 space-y-6">
                    <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(1.85rem, 3.5vw, 2.75rem);">
                        Rigorous, Dual-Standard Curricular Pathways Structured for Global Impact
                    </h2>
                    <div class="prose prose-base text-body/90 font-sans leading-[1.8] space-y-4 max-w-none">
                        {!! \App\Services\FrontendLibrary::get(
                            'advantage_intro',
                            'A child\'s future is defined by the quality of their foundation. At Wonders Kiddies Foundation Schools (WKFS), our curriculum is strategically designed not just to meet required standards, but to <strong>exceed them</strong> by cultivating critical thinking, creativity, and essential life skills. We combine a solid core curriculum with modern, integrated learning methods to ensure every student is prepared not just for the next class, but for a fast-changing world.'
                        ) !!}
                    </div>
                </div>

                <div class="lg:col-span-5 space-y-6 border border-rule bg-white p-6 sm:p-8">
                    <span class="text-xs uppercase tracking-widest text-accent font-sans font-bold block">Key Academic Metrics</span>
                    <div class="space-y-4 divide-y divide-rule font-sans">
                        <div class="pt-2 flex items-baseline justify-between">
                            <span class="text-xs uppercase tracking-wider text-body/70">Faculty Ratio</span>
                            <span class="font-serif text-xl font-bold text-ink">1 : 12 Scholars</span>
                        </div>
                        <div class="pt-3 flex items-baseline justify-between">
                            <span class="text-xs uppercase tracking-wider text-body/70">Curricular Framework</span>
                            <span class="font-serif text-xl font-bold text-ink">British-Nigerian Integrated</span>
                        </div>
                        <div class="pt-3 flex items-baseline justify-between">
                            <span class="text-xs uppercase tracking-wider text-body/70">Laboratory Practicality</span>
                            <span class="font-serif text-xl font-bold text-ink">Weekly Guided Sessions</span>
                        </div>
                        <div class="pt-3 flex items-baseline justify-between">
                            <span class="text-xs uppercase tracking-wider text-body/70">Matriculation Rate</span>
                            <span class="font-serif text-xl font-bold text-ink">100% University Entry</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== 03 — Structured Academic Levels ====== -->
    <section class="py-20 sm:py-28 bg-white border-t border-b border-rule">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <div class="flex items-center gap-3">
                    <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
                        02 &mdash; ACADEMIC DIVISIONS & PROGRAMMES
                    </span>
                    <span class="flex-grow h-[1px] bg-rule"></span>
                </div>
            </div>

            <div class="mb-12 max-w-2xl">
                <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
                    {{ \App\Services\FrontendLibrary::get('learning_levels_title', 'Structured Learning Levels') }}
                </h2>
                <p class="mt-3 text-sm sm:text-base text-body/80 font-sans leading-relaxed">
                    {{ \App\Services\FrontendLibrary::get('learning_levels_subtitle', 'Tailored approaches for every stage of development.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                <!-- EYFS Division -->
                <div class="border border-rule bg-paper p-8 sm:p-10 space-y-6 relative hover:border-ink transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs uppercase tracking-widest font-sans font-bold text-accent">Ages 18 Mo &mdash; 5 Years</span>
                        <span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-sans font-bold border border-ink bg-ink text-accent">Stage 01</span>
                    </div>
                    
                    <h3 class="font-serif text-2xl font-semibold text-ink">
                        {{ \App\Services\FrontendLibrary::get('eyfs_title', 'Early Years Foundation Stage (EYFS)') }}
                    </h3>

                    <div class="space-y-4 pt-4 border-t border-rule font-sans">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-accent block mb-1">
                                {{ \App\Services\FrontendLibrary::get('eyfs_focus_label', 'Focus') }}
                            </span>
                            <p class="text-sm text-body leading-relaxed">
                                {{ \App\Services\FrontendLibrary::get('eyfs_focus_text', 'Play-based learning, sensory exploration, and developing early literacy and numeracy.') }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-accent block mb-1">
                                {{ \App\Services\FrontendLibrary::get('eyfs_outcome_label', 'Key Outcome') }}
                            </span>
                            <p class="text-sm text-body leading-relaxed">
                                {{ \App\Services\FrontendLibrary::get('eyfs_outcome_text', 'Building curiosity, fine motor skills, and social-emotional readiness.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Primary Division -->
                <div class="border border-rule bg-paper p-8 sm:p-10 space-y-6 relative hover:border-ink transition">
                    <div class="flex items-center justify-between">
                        <span class="text-xs uppercase tracking-widest font-sans font-bold text-accent">Ages 5 &mdash; 11 Years</span>
                        <span class="px-2.5 py-1 text-[10px] uppercase tracking-wider font-sans font-bold border border-ink bg-ink text-accent">Stage 02</span>
                    </div>

                    <h3 class="font-serif text-2xl font-semibold text-ink">
                        {{ \App\Services\FrontendLibrary::get('primary_title', 'Primary School Programme') }}
                    </h3>

                    <div class="space-y-4 pt-4 border-t border-rule font-sans">
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-accent block mb-1">
                                {{ \App\Services\FrontendLibrary::get('primary_focus_label', 'Focus') }}
                            </span>
                            <p class="text-sm text-body leading-relaxed">
                                {{ \App\Services\FrontendLibrary::get('primary_focus_text', 'Mastery of core subjects (Numeracy, Literacy, Science) combined with integrated studies (STEM, Coding Introduction).') }}
                            </p>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-accent block mb-1">
                                {{ \App\Services\FrontendLibrary::get('primary_outcome_label', 'Key Outcome') }}
                            </span>
                            <p class="text-sm text-body leading-relaxed">
                                {{ \App\Services\FrontendLibrary::get('primary_outcome_text', 'Fostering independence, research skills, and strong problem-solving abilities.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== 04 — Core Disciplinary Highlights ====== -->
    <section class="py-20 sm:py-28 bg-paper">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <div class="flex items-center gap-3">
                    <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
                        03 &mdash; DISCIPLINARY PILLARS
                    </span>
                    <span class="flex-grow h-[1px] bg-rule"></span>
                </div>
            </div>

            <div class="mb-12 max-w-2xl">
                <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
                    {{ \App\Services\FrontendLibrary::get('subjects_title', 'Subject Highlights: Building Mastery') }}
                </h2>
                <p class="mt-3 text-sm sm:text-base text-body/80 font-sans leading-relaxed">
                    {{ \App\Services\FrontendLibrary::get('subjects_subtitle', 'Our approach to key subject areas.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Literacy & Communication -->
                <div class="border border-rule bg-white p-8 space-y-3 hover:border-ink transition">
                    <span class="font-serif text-lg font-bold text-accent block">01</span>
                    <h3 class="font-serif text-xl font-semibold text-ink">
                        {{ \App\Services\FrontendLibrary::get('subject_literacy_title', 'Literacy & Communication') }}
                    </h3>
                    <p class="text-sm text-body/85 font-sans leading-relaxed">
                        {!! \App\Services\FrontendLibrary::get(
                            'subject_literacy_text',
                            'We emphasize reading for comprehension and creative writing. Students learn not just <em>what</em> to read, but <em>how</em> to analyze, articulate, and present their ideas confidently.'
                        ) !!}
                    </p>
                </div>

                <!-- Numeracy & Logic -->
                <div class="border border-rule bg-white p-8 space-y-3 hover:border-ink transition">
                    <span class="font-serif text-lg font-bold text-accent block">02</span>
                    <h3 class="font-serif text-xl font-semibold text-ink">
                        {{ \App\Services\FrontendLibrary::get('subject_numeracy_title', 'Numeracy & Logic') }}
                    </h3>
                    <p class="text-sm text-body/85 font-sans leading-relaxed">
                        {{ \App\Services\FrontendLibrary::get(
                            'subject_numeracy_text',
                            'Moving beyond rote arithmetic, we use hands-on, conceptual learning to build strong mathematical reasoning. Our students learn to apply logic to real-world problems.'
                        ) }}
                    </p>
                </div>

                <!-- Integrated Science (STEM) -->
                <div class="border border-rule bg-white p-8 space-y-3 hover:border-ink transition">
                    <span class="font-serif text-lg font-bold text-accent block">03</span>
                    <h3 class="font-serif text-xl font-semibold text-ink">
                        {{ \App\Services\FrontendLibrary::get('subject_stem_title', 'Integrated Science (STEM)') }}
                    </h3>
                    <p class="text-sm text-body/85 font-sans leading-relaxed">
                        {{ \App\Services\FrontendLibrary::get(
                            'subject_stem_text',
                            'Science is taught through practical experimentation and inquiry, preparing students for future tech and engineering fields.'
                        ) }}
                    </p>
                </div>

                <!-- Character & Ethics -->
                <div class="border border-rule bg-white p-8 space-y-3 hover:border-ink transition">
                    <span class="font-serif text-lg font-bold text-accent block">04</span>
                    <h3 class="font-serif text-xl font-semibold text-ink">
                        {{ \App\Services\FrontendLibrary::get('subject_character_title', 'Character & Ethics') }}
                    </h3>
                    <p class="text-sm text-body/85 font-sans leading-relaxed">
                        {{ \App\Services\FrontendLibrary::get(
                            'subject_character_text',
                            'Robust training in core values, empathy, leadership, and responsibility, ensuring your child grows into a well-rounded and compassionate individual.'
                        ) }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>