<div>
    {{-- ── Hero Carousel ──────────────────────────────────────── --}}
    <section style="position: relative; overflow: hidden; height: 90vh; min-height: 600px;" x-data="{
        rawImages: @js($heroImages),
        images: [],
        active: 0,
        total: 0,
        init() {
            this.images = this.rawImages.length > 0 ? this.rawImages : [
                'https://placehold.co/1920x1080/{{ ltrim(config("app.tenant_primary_color", "#0B2545"), "#") }}/FFFFFF?text=Welcome+to+Our+School',
                'https://placehold.co/1920x1080/{{ ltrim(config("app.tenant_secondary_color", "#1e293b"), "#") }}/{{ ltrim(config("app.tenant_accent_color", "#EEB902"), "#") }}?text=Empowering+Students',
                'https://placehold.co/1920x1080/{{ ltrim(config("app.tenant_primary_color", "#0B2545"), "#") }}/FFFFFF?text=Shaping+The+Future'
            ];
            this.total = this.images.length;
            if (this.total > 1) {
                setInterval(() => { this.active = (this.active + 1) % this.total; }, 6000);
            }
        },
        next() { this.active = (this.active + 1) % this.total; },
        prev() { this.active = (this.active - 1 + this.total) % this.total; }
    }">
        {{-- Slide Images --}}
        <template x-for="(image, i) in images" :key="i">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; transition: opacity 1s ease-in-out;"
                 :style="active === i ? 'opacity: 1; z-index: 0;' : 'opacity: 0; z-index: -1; pointer-events: none;'">
                <img :src="image.startsWith('http') ? image : '/storage/' + image" alt="Hero Image" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
        </template>

        {{-- Brand-tinted gradient overlay --}}
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 1; background: linear-gradient(to right, color-mix(in srgb, var(--color-tenant-primary) 85%, transparent), color-mix(in srgb, var(--color-tenant-primary) 50%, transparent), transparent);"></div>

        {{-- Hero Content --}}
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8" style="position: relative; z-index: 10; height: 100%; padding-top: 140px; padding-bottom: 60px;">
            <div class="max-w-2xl w-full">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-4 tracking-tight" style="font-family: 'Montserrat', sans-serif;">
                    {{ \App\Services\FrontendLibrary::get('hero_heading', 'A Foundation That') }}
                    <span style="color: var(--color-tenant-accent);">{{ \App\Services\FrontendLibrary::get('hero_heading_highlight', 'Builds Futures.') }}</span>
                </h1>
                <p class="text-lg sm:text-xl text-white/80 mb-8 leading-relaxed max-w-xl">
                    {{ \App\Services\FrontendLibrary::get('hero_description', "We cultivate thinkers, leaders, and compassionate citizens in a secure, nurturing environment.") }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('academics') }}" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold rounded-lg text-white bg-tenant-primary hover:opacity-90 transition shadow-lg">
                        {{ \App\Services\FrontendLibrary::get('hero_cta_primary', 'Explore Our Campus') }}
                        <svg class="ml-2 w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </a>
                    <a href="{{ route('admissions') }}" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-base font-bold rounded-lg text-white hover:bg-white hover:text-gray-900 transition" style="border: 2px solid white;">
                        {{ \App\Services\FrontendLibrary::get('hero_cta_secondary', 'Admissions Open') }}
                    </a>
                </div>
            </div>
        </div>

        {{-- Chevron Controls --}}
        <template x-if="images.length > 1">
            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 20;">
                <button @click="prev()" style="pointer-events: auto; position: absolute; top: 50%; left: 1rem; transform: translateY(-50%); width: 3rem; height: 3rem; border-radius: 9999px; background-color: rgba(255,255,255,0.15); color: white; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); transition: background-color 0.3s; border: 1px solid rgba(255,255,255,0.3);" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.4)'" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.15)'" aria-label="Previous slide">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button @click="next()" style="pointer-events: auto; position: absolute; top: 50%; right: 1rem; transform: translateY(-50%); width: 3rem; height: 3rem; border-radius: 9999px; background-color: rgba(255,255,255,0.15); color: white; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px); transition: background-color 0.3s; border: 1px solid rgba(255,255,255,0.3);" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.4)'" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.15)'" aria-label="Next slide">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </template>

        {{-- Dot Indicators --}}
        <div style="position: absolute; bottom: 2rem; left: 50%; transform: translateX(-50%); display: flex; gap: 0.75rem; z-index: 20;" x-show="images.length > 1">
            <template x-for="(img, i) in images" :key="'dot-'+i">
                <button @click="active = i" style="width: 12px; height: 12px; border-radius: 9999px; border: 2px solid rgba(255, 255, 255, 0.7); background: transparent; cursor: pointer; transition: all 0.3s ease;" :style="active === i ? 'background: #fff; border-color: #fff; transform: scale(1.2);' : ''" :aria-label="'Go to slide ' + (i+1)"></button>
            </template>
        </div>
    </section>

    {{-- ── About / Introduction Section ───────────────────────── --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                {{-- Text Column --}}
                <div>
                    <span class="inline-block text-sm font-bold uppercase tracking-widest mb-4" style="color: var(--color-tenant-accent);">
                        {{ \App\Services\FrontendLibrary::get('about_intro_welcome', 'Welcome to Our School') }}
                    </span>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-6 leading-tight">
                        {{ \App\Services\FrontendLibrary::get('about_intro_heading', 'Nurturing Young Minds for a Brighter Tomorrow') }}
                    </h2>
                    <p class="text-gray-600 leading-relaxed mb-6">
                        {{ \App\Services\FrontendLibrary::get('about_intro_text', 'We provide a private co-educational environment with a broad-based curriculum that develops the whole child — intellectually, emotionally, and morally.') }}
                    </p>
                    <div class="p-5 rounded-xl mb-8 border-l-4" style="background: color-mix(in srgb, var(--color-tenant-primary) 6%, white); border-color: var(--color-tenant-primary);">
                        <p class="font-semibold text-gray-800 text-sm italic">
                            "{{ \App\Services\FrontendLibrary::get('about_intro_mission', 'To foster critical thinking, global readiness, and character development in every child.') }}"
                        </p>
                    </div>
                    <a href="{{ route('about') }}" class="inline-flex items-center px-6 py-3 text-sm font-bold rounded-lg text-white bg-tenant-primary hover:opacity-90 transition shadow-md">
                        Read More
                        <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    </a>
                </div>

                {{-- Visual Column: Pillar Grid --}}
                <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    @php
                        $pillars = [
                            ['key' => 'pillar_1_label', 'default' => 'Science Laboratory', 'img_key' => 'pillar_1_image'],
                            ['key' => 'pillar_2_label', 'default' => 'Practical Work',     'img_key' => 'pillar_2_image'],
                            ['key' => 'pillar_3_label', 'default' => 'Information Technology', 'img_key' => 'pillar_3_image'],
                            ['key' => 'pillar_4_label', 'default' => 'Creative Arts',      'img_key' => 'pillar_4_image'],
                        ];
                    @endphp
                    @foreach($pillars as $pillar)
                        <div class="pillar-card">
                            @php $img = \App\Services\FrontendLibrary::get($pillar['img_key']); @endphp
                            @if($img)
                                <img src="{{ Storage::url($img) }}" alt="{{ \App\Services\FrontendLibrary::get($pillar['key'], $pillar['default']) }}">
                            @else
                                <div class="w-full h-full flex items-center justify-center" style="background: color-mix(in srgb, var(--color-tenant-primary) 12%, #f3f4f6);">
                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </div>
                            @endif
                            <div class="pillar-card-overlay">
                                <span>{{ \App\Services\FrontendLibrary::get($pillar['key'], $pillar['default']) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ── Feature Grid (4 Pillars) ───────────────────────────── --}}
    <section class="py-20 lg:py-28" style="background: #F8F9FA;">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-14">
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">{{ \App\Services\FrontendLibrary::get('why_us_heading', 'What We Do') }}</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ \App\Services\FrontendLibrary::get('why_us_subheading', 'Building excellence through innovation and care.') }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @php
                    $features = [
                        ['icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'title_key' => 'feature_1_title', 'title_default' => 'Effective Teaching', 'desc_key' => 'feature_1_description', 'desc_default' => 'Unique instructional methods powered by digital infrastructure for seamless online and offline learning.'],
                        ['icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'title_key' => 'feature_2_title', 'title_default' => 'Arts & Creativity', 'desc_key' => 'feature_2_description', 'desc_default' => 'Bringing imagination to reality through creative arts, music, and expressive programs.'],
                        ['icon' => 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', 'title_key' => 'feature_3_title', 'title_default' => 'Practical Sciences', 'desc_key' => 'feature_3_description', 'desc_default' => 'Hands-on, experiment-driven science tracks matching theory with laboratory experience.'],
                        ['icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', 'title_key' => 'feature_4_title', 'title_default' => 'Coding & Tech', 'desc_key' => 'feature_4_description', 'desc_default' => 'Integrated IT training with computing skills embedded directly into the daily learning pattern.'],
                    ];
                @endphp
                @foreach($features as $feature)
                    <div class="feature-card">
                        <div class="feature-card-icon">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $feature['icon'] }}"/></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 mb-3">{{ \App\Services\FrontendLibrary::get($feature['title_key'], $feature['title_default']) }}</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">{{ \App\Services\FrontendLibrary::get($feature['desc_key'], $feature['desc_default']) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── Statistics ──────────────────────────────────────────── --}}
    <section class="py-16 bg-tenant-primary">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center text-white">
                <div><div class="text-4xl md:text-5xl font-extrabold mb-1" style="color: var(--color-tenant-accent);">{{ \App\Services\FrontendLibrary::get('stat_1_value', '15+') }}</div><div class="text-white/70 font-medium text-sm">{{ \App\Services\FrontendLibrary::get('stat_1_label', 'Years of Excellence') }}</div></div>
                <div><div class="text-4xl md:text-5xl font-extrabold mb-1" style="color: var(--color-tenant-accent);">{{ \App\Services\FrontendLibrary::get('stat_2_value', '500+') }}</div><div class="text-white/70 font-medium text-sm">{{ \App\Services\FrontendLibrary::get('stat_2_label', 'Happy Students') }}</div></div>
                <div><div class="text-4xl md:text-5xl font-extrabold mb-1" style="color: var(--color-tenant-accent);">{{ \App\Services\FrontendLibrary::get('stat_3_value', '50+') }}</div><div class="text-white/70 font-medium text-sm">{{ \App\Services\FrontendLibrary::get('stat_3_label', 'Expert Staff') }}</div></div>
                <div><div class="text-4xl md:text-5xl font-extrabold mb-1" style="color: var(--color-tenant-accent);">{{ \App\Services\FrontendLibrary::get('stat_4_value', '100%') }}</div><div class="text-white/70 font-medium text-sm">{{ \App\Services\FrontendLibrary::get('stat_4_label', 'Parent Satisfaction') }}</div></div>
            </div>
        </div>
    </section>

    {{-- ── News Section ───────────────────────────────────────── --}}
    <section class="py-20 lg:py-28 bg-white">
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-end mb-12">
                <div>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-2">{{ \App\Services\FrontendLibrary::get('news_heading', 'School Life') }}</h2>
                    <p class="text-lg text-gray-600">{{ \App\Services\FrontendLibrary::get('news_subheading', 'A Place Your Child Can Thrive.') }}</p>
                </div>
                <a href="{{ route('news') }}" class="hidden md:inline-flex items-center font-bold text-tenant-primary hover:underline text-sm transition">
                    View All <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @forelse($latestNews as $post)
                    <div class="group bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition duration-300 border border-gray-100">
                        <div class="relative h-48 overflow-hidden">
                            <img src="{{ Str::startsWith($post->image, 'http') ? $post->image : Storage::url($post->image) }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            <div class="absolute top-4 left-4 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide" style="background: var(--color-tenant-accent); color: #1D2A44;">News</div>
                        </div>
                        <div class="p-6">
                            <div class="text-sm text-gray-400 mb-2">{{ $post->published_at->format('M d, Y') }}</div>
                            <h3 class="text-lg font-bold text-gray-900 mb-3 group-hover:text-tenant-primary transition"><a href="{{ route('post', $post) }}">{{ $post->title }}</a></h3>
                            <p class="text-gray-600 text-sm line-clamp-3 mb-4">{{ Str::limit(strip_tags($post->body), 100) }}</p>
                            <a href="{{ route('post', $post) }}" class="inline-flex items-center text-sm text-tenant-primary font-semibold hover:underline">Read More <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-16 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                        <p class="text-gray-400">No news updates available at the moment.</p>
                    </div>
                @endforelse
            </div>
            <div class="mt-8 text-center md:hidden">
                <a href="{{ route('news') }}" class="inline-flex items-center font-bold text-tenant-primary hover:underline text-sm">View All News <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></a>
            </div>
        </div>
    </section>

    {{-- ── Final CTA ──────────────────────────────────────────── --}}
    <section class="py-20 relative overflow-hidden" style="background: linear-gradient(135deg, color-mix(in srgb, var(--color-tenant-primary) 12%, white), color-mix(in srgb, var(--color-tenant-primary) 4%, white));">
        <div class="absolute -left-12 top-1/3 w-56 h-56 rounded-full opacity-[0.06]" style="background: var(--color-tenant-primary);"></div>
        <div class="absolute right-8 bottom-0 w-32 h-32 rounded-full opacity-[0.08]" style="background: var(--color-tenant-primary);"></div>
        <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
            <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">{{ \App\Services\FrontendLibrary::get('cta_heading', 'Ready to Join Our Family?') }}</h2>
            <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">Give your child the foundation they deserve. Join our growing family today.</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('admissions') }}" class="inline-flex items-center justify-center px-8 py-4 text-lg font-bold rounded-lg text-white bg-tenant-primary hover:opacity-90 transition shadow-lg">
                    {{ \App\Services\FrontendLibrary::get('cta_enrol', 'Enrol Now') }}
                </a>
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 border-2 border-tenant-primary text-lg font-bold rounded-lg text-tenant-primary hover:bg-tenant-primary hover:text-white transition">
                    {{ \App\Services\FrontendLibrary::get('cta_tour', 'Book a Tour') }}
                </a>
                <a href="https://wa.me/{{ str_replace(['+', ' '], '', \App\Services\FrontendLibrary::getSetting('school_phone', '')) }}" target="_blank" class="inline-flex items-center justify-center px-8 py-4 text-lg font-bold rounded-lg text-white bg-green-600 hover:bg-green-700 transition">
                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    {{ \App\Services\FrontendLibrary::get('cta_whatsapp', 'Chat on WhatsApp') }}
                </a>
            </div>
        </div>
    </section>
</div>
