@php
    $testimonialsEyebrow = \App\Services\FrontendLibrary::get('testimonials_eyebrow', 'COMMUNITY COMMENDATIONS');
    $testimonialsHeading = \App\Services\FrontendLibrary::get('testimonials_heading', 'Testimonies of Scholastic Formation');
    $testimonialsItems = \App\Services\FrontendLibrary::getJson('testimonials_items', [
        [
            'quote'  => 'The standard of teaching and disciplined ethos at Cathedral College have transformed our children’s academic focus. Their dual curriculum gave our daughter the edge to secure top distinction.',
            'author' => 'Barrister (Mrs.) O. Adebisi',
            'role'   => 'Parent (SS3 Graduate Class)',
        ],
        [
            'quote'  => 'Studying here gave me not just academic mastery in the Sciences, but confidence, spiritual grounding, and leadership skills. I passed WAEC and JAMB in one sitting with flying colours.',
            'author' => 'Toluwanimi Adeleke',
            'role'   => 'Alumnus & National Merit Scholar',
        ],
        [
            'quote'  => 'The science laboratories and dedicated teachers make complex concepts easy to grasp. We have seen tremendous growth in both knowledge and character in our son.',
            'author' => 'Engr. K. Balogun',
            'role'   => 'Parent (JS2 & SS1 Scholars)',
        ],
    ]);
@endphp

<!-- ====== Classic Theme Testimonials Section ====== -->
<section id="testimonials" class="py-16 sm:py-24 bg-white relative">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-3 mb-12 sm:mb-16">
            <div class="text-xs font-sans uppercase tracking-[0.25em] text-accent font-bold">
                {{ $testimonialsEyebrow }}
            </div>
            <h2 class="font-serif font-normal text-ink text-2xl sm:text-3xl lg:text-4xl leading-tight">
                {{ $testimonialsHeading }}
            </h2>
            <div class="flex items-center justify-center gap-3 pt-1">
                <span class="w-10 h-[1px] bg-accent/40"></span>
                <span class="text-accent text-xs">◆</span>
                <span class="w-10 h-[1px] bg-accent/40"></span>
            </div>
        </div>

        <!-- 3 Formal Commendation Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($testimonialsItems as $item)
                @php
                    $quote = $item['quote'] ?? '';
                    $author = $item['author'] ?? '';
                    $role = $item['role'] ?? '';
                @endphp
                <div class="border-2 border-accent/20 bg-paper p-8 flex flex-col justify-between relative shadow-sm">
                    <div class="space-y-4">
                        <div class="text-3xl font-serif text-accent leading-none">“</div>
                        <p class="font-serif text-sm text-body leading-relaxed italic">
                            {{ $quote }}
                        </p>
                    </div>

                    <div class="pt-6 mt-6 border-t border-rule">
                        <div class="font-serif font-bold text-sm text-ink">{{ $author }}</div>
                        <div class="text-[11px] font-sans uppercase tracking-wider text-slate-500 mt-0.5">{{ $role }}</div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>
