@php
    $facilitiesEyebrow = \App\Services\FrontendLibrary::get('facilities_eyebrow', 'CAMPUS INFRASTRUCTURE');
    $facilitiesHeading = \App\Services\FrontendLibrary::get('facilities_heading', 'State-of-the-Art Facilities & Learning Environments');
    $facilitiesItems = \App\Services\FrontendLibrary::getJson('facilities_items', [
        [
            'title' => 'Science & STEM Laboratories',
            'desc'  => 'Dedicated, fully ventilated Physics, Chemistry, and Biology laboratories equipped with modern apparatus.',
            'image' => 'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=800&auto=format&fit=crop',
        ],
        [
            'title' => 'ICT & Robotics Suite',
            'desc'  => 'High-speed networked computer workstations, interactive smart screens, and software coding terminals.',
            'image' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=800&auto=format&fit=crop',
        ],
        [
            'title' => 'E-Library & Resource Archive',
            'desc'  => 'Extensive academic book collection, quiet individual study carrels, and digital research terminals.',
            'image' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?q=80&w=800&auto=format&fit=crop',
        ],
        [
            'title' => 'Auditorium & Arts Theatre',
            'desc'  => 'Modern multipurpose cultural hall for school assemblies, drama productions, music recitals, and debates.',
            'image' => 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?q=80&w=800&auto=format&fit=crop',
        ],
        [
            'title' => 'Sports Complex & Courts',
            'desc'  => 'Standard football pitch, basketball court, volleyball court, and athletic training tracks.',
            'image' => 'https://images.unsplash.com/photo-1526676037777-05a232554f77?q=80&w=800&auto=format&fit=crop',
        ],
        [
            'title' => 'Health Bay & Boarding House',
            'desc'  => 'Clean, well-supervised boarding dormitories with on-site registered nurses and medical triage.',
            'image' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=800&auto=format&fit=crop',
        ],
    ]);
@endphp

<!-- ====== Classic Theme Facilities Section ====== -->
<section id="facilities" class="py-16 sm:py-24 bg-white relative">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-3 mb-12 sm:mb-16">
            <div class="text-xs font-sans uppercase tracking-[0.25em] text-accent font-bold">
                {{ $facilitiesEyebrow }}
            </div>
            <h2 class="font-serif font-normal text-ink text-2xl sm:text-3xl lg:text-4xl leading-tight">
                {{ $facilitiesHeading }}
            </h2>
            <div class="flex items-center justify-center gap-3 pt-1">
                <span class="w-10 h-[1px] bg-accent/40"></span>
                <span class="text-accent text-xs">◆</span>
                <span class="w-10 h-[1px] bg-accent/40"></span>
            </div>
        </div>

        <!-- 3x2 Symmetrical Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            @foreach($facilitiesItems as $item)
                @php
                    $img = \App\Services\FrontendLibrary::imageUrl($item['image'] ?? '');
                    $title = $item['title'] ?? '';
                    $desc = $item['desc'] ?? '';
                @endphp
                <div class="border border-rule bg-paper p-3 shadow-sm">
                    <div class="aspect-[16/10] overflow-hidden bg-slate-200 border border-rule">
                        <img src="{{ $img }}"
                             alt="{{ $title }}"
                             class="w-full h-full object-cover" />
                    </div>
                    <div class="p-4 space-y-2">
                        <h3 class="font-serif font-semibold text-base text-ink">
                            {{ $title }}
                        </h3>
                        <p class="font-sans text-xs text-body/75 leading-relaxed">
                            {{ $desc }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

    </div>
</section>
