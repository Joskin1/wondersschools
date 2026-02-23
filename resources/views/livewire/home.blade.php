<div>
    <!-- Hero Section -->
    <div class="relative bg-dark-green text-white overflow-hidden" x-data="{
        images: {{ \App\Models\Setting::where('key', 'hero_images')->value('value') ?? '[]' }},
        active: 0,
        init() {
            if (this.images.length > 1) {
                setInterval(() => {
                    this.active = (this.active + 1) % this.images.length;
                }, 5000);
            }
        }
    }">
        <!-- Slider Images -->
        <template x-for="(image, index) in images" :key="index">
            <div class="absolute inset-0 transition-opacity duration-1000 ease-in-out"
                 :class="{ 'opacity-100': active === index, 'opacity-0': active !== index }">
                <img :src="'/storage/' + image" alt="School Campus" class="w-full h-full object-cover opacity-40">
                <div class="absolute inset-0 bg-gradient-to-r from-dark-green via-dark-green/80 to-transparent"></div>
            </div>
        </template>
        
        <!-- Fallback if no images -->
        <div class="absolute inset-0" x-show="images.length === 0">
             <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80" alt="School Campus" class="w-full h-full object-cover opacity-40">
             <div class="absolute inset-0 bg-gradient-to-r from-dark-green via-dark-green/80 to-transparent"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
            <div class="lg:w-2/3">
                <span class="block text-lime-green font-bold tracking-wide uppercase text-sm mb-2">Welcome to Wonders Kiddies Foundation Schools</span>
                <h1 class="text-4xl lg:text-6xl font-extrabold tracking-tight leading-tight mb-6">
                    A Foundation That <span class="text-lime-green">Builds Futures.</span>
                </h1>
                <p class="text-xl text-gray-200 mb-8 max-w-2xl leading-relaxed">
                    We don't just teach children; we cultivate thinkers, leaders, and compassionate citizens in a secure, nurturing environment.
                </p>
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="{{ route('academics') }}" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-base font-bold rounded-md text-dark-green bg-lime-green hover:bg-opacity-90 transition duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Explore Our Curriculum
                        <svg class="ml-2 -mr-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </a>
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-base font-bold rounded-md text-white hover:bg-white hover:text-dark-green transition duration-300">
                        Book a Tour
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Trust Strip -->
    <div class="bg-lime-green py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center md:text-left">
                <div class="flex items-center justify-center md:justify-start space-x-2">
                    <svg class="w-6 h-6 text-dark-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-bold text-dark-green text-sm sm:text-base">Verified Curriculum</span>
                </div>
                <div class="flex items-center justify-center md:justify-start space-x-2">
                    <svg class="w-6 h-6 text-dark-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span class="font-bold text-dark-green text-sm sm:text-base">Experienced Educators</span>
                </div>
                <div class="flex items-center justify-center md:justify-start space-x-2">
                    <svg class="w-6 h-6 text-dark-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span class="font-bold text-dark-green text-sm sm:text-base">Secure Campus</span>
                </div>
                <div class="flex items-center justify-center md:justify-start space-x-2">
                    <svg class="w-6 h-6 text-dark-green" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    <span class="font-bold text-dark-green text-sm sm:text-base">Proven Results</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Why WKFS Section (Bento Grid Style) -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Why "Wonders"?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Because Every Child is a World of Potential.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition duration-300 border-b-4 border-lime-green">
                    <div class="w-14 h-14 bg-lime-green/20 rounded-full flex items-center justify-center mb-6 text-dark-green">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Academic Excellence</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Our curriculum is designed to challenge and inspire. We focus on building a strong foundation in literacy, numeracy, and critical thinking.
                    </p>
                </div>

                <!-- Card 2 -->
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition duration-300 border-b-4 border-light-blue">
                    <div class="w-14 h-14 bg-light-blue/20 rounded-full flex items-center justify-center mb-6 text-light-blue">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Holistic Development</h3>
                    <p class="text-gray-600 leading-relaxed">
                        We nurture the whole child. From sports to arts, we provide opportunities for students to explore their passions and talents.
                    </p>
                </div>

                <!-- Card 3 -->
                <div class="bg-white p-8 rounded-2xl shadow-lg hover:shadow-xl transition duration-300 border-b-4 border-brand-red">
                    <div class="w-14 h-14 bg-brand-red/20 rounded-full flex items-center justify-center mb-6 text-brand-red">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">Community & Values</h3>
                    <p class="text-gray-600 leading-relaxed">
                        We instill strong moral values and a sense of community. Our students learn to be respectful, responsible, and kind.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="bg-dark-green py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-lime-green mb-2">15+</div>
                    <div class="text-white font-medium">Years of Excellence</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-lime-green mb-2">500+</div>
                    <div class="text-white font-medium">Happy Students</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-lime-green mb-2">50+</div>
                    <div class="text-white font-medium">Expert Staff</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-extrabold text-lime-green mb-2">100%</div>
                    <div class="text-white font-medium">Parent Satisfaction</div>
                </div>
            </div>
        </div>
    </div>

    <!-- School Life (News) -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-end mb-12">
                <div>
                    <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-2">More Than a Classroom</h2>
                    <p class="text-xl text-gray-600">A Place Your Child Can Thrive.</p>
                </div>
                <a href="{{ route('news') }}" class="hidden md:inline-flex items-center font-bold text-dark-green hover:text-lime-green transition">
                    View All News <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @forelse($latestNews as $post)
                    <div class="group bg-white rounded-xl overflow-hidden shadow-lg hover:shadow-2xl transition duration-300 border border-gray-100">
                        <div class="relative h-48 overflow-hidden">
                            <img src="{{ Str::startsWith($post->image, 'http') ? $post->image : Storage::url($post->image) }}" alt="{{ $post->title }}" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
                            <div class="absolute top-4 left-4 bg-lime-green text-dark-green text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                                News
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="text-sm text-gray-500 mb-2">{{ $post->published_at->format('M d, Y') }}</div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-dark-green transition">
                                <a href="{{ route('post', $post) }}">{{ $post->title }}</a>
                            </h3>
                            <p class="text-gray-600 line-clamp-3 mb-4">
                                {{ Str::limit(strip_tags($post->body), 100) }}
                            </p>
                            <a href="{{ route('post', $post) }}" class="inline-flex items-center text-dark-green font-semibold hover:text-lime-green transition">
                                Read More <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                        <p class="text-gray-500">No news updates available at the moment.</p>
                    </div>
                @endforelse
            </div>
            
            <div class="mt-8 text-center md:hidden">
                <a href="{{ route('news') }}" class="inline-flex items-center font-bold text-dark-green hover:text-lime-green transition">
                    View All News <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </a>
            </div>
        </div>
    </div>

    <!-- Meet the Leadership -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">Our Commitment</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">Experienced Hands, Nurturing Hearts.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                 @php
                    $staffMembers = \App\Models\Staff::take(3)->get();
                @endphp
                @forelse($staffMembers as $staff)
                    <div class="bg-white rounded-xl overflow-hidden shadow-md hover:shadow-lg transition duration-300 flex flex-col items-center p-8 text-center">
                        <div class="w-32 h-32 rounded-full overflow-hidden mb-6 border-4 border-lime-green/30">
                            <img src="{{ Str::startsWith($staff->image, 'http') ? $staff->image : Storage::url($staff->image) }}" alt="{{ $staff->name }}" class="w-full h-full object-cover">
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">{{ $staff->name }}</h3>
                        <p class="text-dark-green font-medium mb-4">{{ $staff->role }}</p>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            {{ Str::limit($staff->bio, 120) }}
                        </p>
                    </div>
                @empty
                     <div class="col-span-3 text-center text-gray-500">
                        Leadership profiles coming soon.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Final CTA Strip -->
    <div class="bg-dark-green py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-8">
                Ready for the WKFS Foundation?
            </h2>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="{{ route('admissions') }}" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-bold rounded-md text-dark-green bg-lime-green hover:bg-opacity-90 transition duration-300 shadow-lg">
                    Enrol Now
                </a>
                <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-8 py-4 border-2 border-white text-lg font-bold rounded-md text-white hover:bg-white hover:text-dark-green transition duration-300">
                    Book a Tour
                </a>
                <a href="https://wa.me/{{ str_replace(['+', ' '], '', \App\Models\Setting::where('key', 'school_phone')->value('value') ?? '') }}" target="_blank" class="inline-flex items-center justify-center px-8 py-4 border border-transparent text-lg font-bold rounded-md text-white bg-green-600 hover:bg-green-700 transition duration-300">
                    <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    Chat on WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>
