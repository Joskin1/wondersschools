@php
    $testimonialsEyebrow = \App\Services\FrontendLibrary::get('testimonials_eyebrow', 'COMMUNITY PERSPECTIVES');
    $testimonialsHeading = \App\Services\FrontendLibrary::get('testimonials_heading', 'What Parents & Scholars Say');
    $testimonialsItems = \App\Services\FrontendLibrary::getJson('testimonials_items', [
        [
            'quote'  => 'The standard of teaching and disciplined ethos at Cathedral College have transformed our children’s academic focus. Their dual curriculum gave our daughter the edge to secure top distinction.',
            'author' => 'Barrister (Mrs.) O. Adebisi',
            'role'   => 'Parent (SS3 Graduate Class)',
            'rating' => 5,
        ],
        [
            'quote'  => 'Studying here gave me not just academic mastery in the Sciences, but confidence, spiritual grounding, and leadership skills. I passed WAEC and JAMB in one sitting with flying colours.',
            'author' => 'Toluwanimi Adeleke',
            'role'   => 'Alumnus & National Merit Scholar',
            'rating' => 5,
        ],
        [
            'quote'  => 'The science laboratories and dedicated teachers make complex concepts easy to grasp. We have seen tremendous growth in both knowledge and character in our son.',
            'author' => 'Engr. K. Balogun',
            'role'   => 'Parent (JS2 & SS1 Scholars)',
            'rating' => 5,
        ],
    ]);
@endphp

<!-- ====== Modern Theme Testimonials Section ====== -->
<section id="testimonials" class="py-16 sm:py-24 bg-white relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-12 sm:mb-16">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-accent/15 text-accent text-xs font-sans font-bold uppercase tracking-wider">
                <span>{{ $testimonialsEyebrow }}</span>
            </div>
            <h2 class="font-sans font-extrabold text-ink text-2xl sm:text-3xl lg:text-4xl tracking-tight leading-tight">
                {{ $testimonialsHeading }}
            </h2>
        </div>

        <!-- 3-Column Modern Testimonial Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($testimonialsItems as $item)
                @php
                    $quote = $item['quote'] ?? '';
                    $author = $item['author'] ?? '';
                    $role = $item['role'] ?? '';
                @endphp
                <div class="p-8 rounded-3xl bg-slate-50 border border-slate-100 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between">
                    <div class="space-y-4">
                        <!-- 5 Star Rating -->
                        <div class="flex items-center gap-1 text-accent text-sm">
                            ★★★★★
                        </div>
                        <p class="font-sans text-sm text-slate-700 leading-relaxed italic">
                            “{{ $quote }}”
                        </p>
                    </div>

                    <!-- Author Info -->
                    <div class="pt-6 border-t border-slate-200/60 mt-6 flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-full bg-accent/20 text-accent font-sans font-bold flex items-center justify-center text-sm">
                            {{ substr($author, 0, 1) }}
                        </div>
                        <div>
                            <div class="font-sans font-bold text-sm text-ink">{{ $author }}</div>
                            <div class="text-xs text-slate-500 font-sans">{{ $role }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>
