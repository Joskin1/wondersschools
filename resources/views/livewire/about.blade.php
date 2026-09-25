@php
    $tenantName = function_exists('tenant') && tenant('name') ? tenant('name') : null;
    $defaultSchoolName = $tenantName ?? config('app.name', 'Wonders Kiddies Foundation Schools');
    $schoolShort = $tenantName ? strtoupper(substr($tenantName, 0, 2)) : 'WKFS';
@endphp

<div>
    <!-- ====== 01 — Subpage Header Banner ====== -->
    <section class="relative bg-ink text-paper py-20 sm:py-28 overflow-hidden border-b border-rule">
        <div class="absolute inset-0 z-0 opacity-15">
            <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1920&q=80"
                 alt="Campus Heritage"
                 class="w-full h-full object-cover object-center" />
            <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/90 to-ink/70"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-[1px] bg-accent"></span>
                    <span class="text-xs uppercase tracking-[0.25em] text-accent font-sans font-semibold">
                        Institutional Heritage & Ethos
                    </span>
                </div>
                <h1 class="font-serif font-semibold text-white tracking-tight leading-[1.1]" style="font-size: clamp(2.5rem, 5vw, 4rem);">
                    {{ \App\Services\FrontendLibrary::get('about_hero_title', 'We Build Foundations That Last.') }}
                </h1>
                <p class="text-base sm:text-lg text-paper/80 font-sans leading-relaxed max-w-[62ch]">
                    {{ \App\Services\FrontendLibrary::get('about_hero_subtitle', 'Wonders Kiddies Foundation Schools') }}
                </p>
            </div>
        </div>
    </section>

    <!-- ====== 02 — Founding Ethos & Narrative ====== -->
    <section class="py-20 sm:py-28 bg-paper">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <div class="flex items-center gap-3">
                    <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
                        01 &mdash; FOUNDING PHILOSOPHY
                    </span>
                    <span class="flex-grow h-[1px] bg-rule"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-start">
                <div class="lg:col-span-6 space-y-6">
                    <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(1.85rem, 3.5vw, 2.75rem);">
                        Dedicated to Scholastic Rigor, Character & Total Child Formation
                    </h2>
                    <div class="prose prose-base text-body/90 font-sans leading-[1.8] space-y-4 max-w-none">
                        {!! \App\Services\FrontendLibrary::get(
                            'about_description',
                            $defaultSchoolName . ' (WKFS) is dedicated to providing a high-quality, nurturing, and secure educational environment. Our approach is simple: we focus on the <strong>whole child</strong>—intellectually, emotionally, and morally—to ensure they thrive in every aspect of life. We believe a strong foundation in the early years is the rarest, most valuable asset a parent can provide.'
                        ) !!}
                    </div>
                </div>

                <div class="lg:col-span-6 relative">
                    <div class="relative border border-rule bg-white p-2">
                        <img src="https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&w=1200&q=80"
                             alt="Academic Scholars"
                             class="w-full h-[380px] sm:h-[460px] object-cover" />
                        <div class="absolute -bottom-6 -right-4 sm:right-6 bg-ink text-paper p-6 border border-accent/40 max-w-[260px]">
                            <span class="block font-serif text-3xl sm:text-4xl font-semibold text-accent leading-none">
                                25+
                            </span>
                            <span class="block text-xs font-sans uppercase tracking-wider text-white/80 mt-1 leading-snug">
                                Years of Unbroken Academic Excellence
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== 03 — Mission & Vision Matrix ====== -->
    <section class="py-20 sm:py-28 bg-white border-t border-b border-rule">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <div class="flex items-center gap-3">
                    <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
                        02 &mdash; MISSION & VISION
                    </span>
                    <span class="flex-grow h-[1px] bg-rule"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                <!-- Mission Card -->
                <div class="border border-rule bg-paper p-8 sm:p-10 relative space-y-4">
                    <div class="w-10 h-10 border border-ink bg-ink text-accent flex items-center justify-center font-serif text-base font-bold">
                        M
                    </div>
                    <h3 class="font-serif text-2xl font-semibold text-ink">
                        {{ \App\Services\FrontendLibrary::get('about_mission_title', 'Our Mission') }}
                    </h3>
                    <p class="text-sm sm:text-base text-body/90 font-sans leading-[1.8]">
                        {{ \App\Services\FrontendLibrary::get(
                            'about_mission_text',
                            'To deliver secure, well-planned education that fosters creativity, academic mastery, and strong character development.'
                        ) }}
                    </p>
                </div>

                <!-- Vision Card -->
                <div class="border border-rule bg-paper p-8 sm:p-10 relative space-y-4">
                    <div class="w-10 h-10 border border-accent bg-accent text-[color:var(--accent-contrast)] flex items-center justify-center font-serif text-base font-bold">
                        V
                    </div>
                    <h3 class="font-serif text-2xl font-semibold text-ink">
                        {{ \App\Services\FrontendLibrary::get('about_vision_title', 'Our Vision') }}
                    </h3>
                    <p class="text-sm sm:text-base text-body/90 font-sans leading-[1.8]">
                        {{ \App\Services\FrontendLibrary::get(
                            'about_vision_text',
                            'To be the most trusted educational brand known for foundational excellence, transparency, and dependable long-term student success.'
                        ) }}
                    </p>
                </div>
            </div>

            <!-- Core Values -->
            <div class="mt-20">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span class="text-xs uppercase tracking-[0.25em] text-accent font-sans font-bold block mb-2">Pillars of Character</span>
                    <h3 class="font-serif text-3xl font-semibold text-ink">
                        {{ \App\Services\FrontendLibrary::get('about_core_values_title', 'Our Core Values') }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                    @php
                        $values = [
                            ['num' => '01', 'key' => 'core_value_1', 'def' => 'Integrity of Instruction', 'desc' => 'Truth, precision, and fidelity in all academic guidance.'],
                            ['num' => '02', 'key' => 'core_value_2', 'def' => 'Student-Centric Nurturing', 'desc' => 'Individualized pastoral care unlocking innate potentials.'],
                            ['num' => '03', 'key' => 'core_value_3', 'def' => 'Strategic Curriculum Delivery', 'desc' => 'Rigorous synthesis of national and global standards.'],
                            ['num' => '04', 'key' => 'core_value_4', 'def' => 'Transparent Parent Partnership', 'desc' => 'Proactive, honest reporting and collaborative stewardship.'],
                            ['num' => '05', 'key' => 'core_value_5', 'def' => 'Long-term Value Creation', 'desc' => 'Preparing articulacy, moral courage, and future readiness.'],
                        ];
                    @endphp

                    @foreach($values as $val)
                        <div class="border border-rule bg-paper p-6 space-y-2 text-left hover:border-ink transition">
                            <span class="font-serif text-lg font-bold text-accent block">{{ $val['num'] }}</span>
                            <h4 class="font-serif text-base font-semibold text-ink leading-snug">
                                {{ \App\Services\FrontendLibrary::get($val['key'], $val['def']) }}
                            </h4>
                            <p class="text-xs text-body/75 font-sans leading-relaxed">
                                {{ $val['desc'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <!-- ====== 04 — Academic Leadership & Faculty ====== -->
    <section class="py-20 sm:py-28 bg-paper">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <div class="flex items-center gap-3">
                    <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
                        03 &mdash; ACADEMIC GOVERNANCE
                    </span>
                    <span class="flex-grow h-[1px] bg-rule"></span>
                </div>
            </div>

            <div class="mb-12 max-w-2xl">
                <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
                    {{ \App\Services\FrontendLibrary::get('about_leadership_title', 'Meet Our Leadership') }}
                </h2>
                <p class="mt-3 text-sm sm:text-base text-body/80 font-sans leading-relaxed">
                    {{ \App\Services\FrontendLibrary::get('about_leadership_subtitle', 'The dedicated team guiding our school.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                @forelse($staff as $member)
                    <div class="border border-rule bg-white p-6 space-y-4 group hover:border-ink transition">
                        <div class="relative w-full h-64 overflow-hidden border border-rule bg-paper">
                            <img class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500"
                                 src="{{ Str::startsWith($member->image, 'http') ? $member->image : (\App\Services\FrontendLibrary::imageUrl($member->image) ?? 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=600&q=80') }}"
                                 alt="{{ $member->name }}">
                        </div>
                        <div class="space-y-1">
                            <h3 class="font-serif text-lg font-semibold text-ink">{{ $member->name }}</h3>
                            <p class="text-xs uppercase tracking-widest text-accent font-sans font-bold">{{ $member->role }}</p>
                            @if(!empty($member->bio))
                                <p class="text-xs text-body/80 font-sans leading-relaxed pt-2 border-t border-rule line-clamp-3">
                                    {{ $member->bio }}
                                </p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full border border-dashed border-rule p-12 text-center text-sm font-sans text-body/70">
                        {{ \App\Services\FrontendLibrary::get('about_leadership_empty', 'Leadership team information coming soon.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- ====== 05 — Institutional Accreditations Strip ====== -->
    <section class="py-12 bg-ink text-paper border-t border-accent/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6 text-center md:text-left">
                <div>
                    <span class="text-xs uppercase tracking-[0.2em] text-accent font-sans font-semibold block">Quality Assurance</span>
                    <h4 class="font-serif text-lg sm:text-xl font-semibold text-white mt-0.5">Accredited by National & International Examination Boards</h4>
                </div>
                <div class="flex items-center gap-6 text-xs uppercase tracking-widest text-white/70 font-sans font-semibold flex-wrap justify-center">
                    <span class="px-3 py-1.5 border border-white/20">WAEC</span>
                    <span class="px-3 py-1.5 border border-white/20">NECO</span>
                    <span class="px-3 py-1.5 border border-white/20">Cambridge Assessment</span>
                    <span class="px-3 py-1.5 border border-white/20">British Council</span>
                </div>
            </div>
        </div>
    </section>
</div>