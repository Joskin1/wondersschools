@php
    $facilitiesEyebrow = \App\Services\FrontendLibrary::get('facilities_eyebrow', 'INFRASTRUCTURE');
    $facilitiesHeading = \App\Services\FrontendLibrary::get('facilities_heading', 'World-Class Infrastructure for Total Learning');
    $facilitiesItems = \App\Services\FrontendLibrary::getJson('facilities_items', [
        [
            'title' => 'Science & STEM Laboratories',
            'desc'  => 'Dedicated, fully ventilated Physics, Chemistry, and Biology laboratories equipped with modern apparatus.',
            'image' => 'https://images.unsplash.com/photo-1562774053-701939374585?q=80&w=800&auto=format&fit=crop',
            'tag'   => 'STEM & Discovery',
        ],
        [
            'title' => 'ICT & Robotics Suite',
            'desc'  => 'High-speed networked computer workstations, interactive smart screens, and software coding terminals.',
            'image' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?q=80&w=800&auto=format&fit=crop',
            'tag'   => 'Digital Innovation',
        ],
        [
            'title' => 'E-Library & Resource Archive',
            'desc'  => 'Extensive academic book collection, quiet individual study carrels, and digital research terminals.',
            'image' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?q=80&w=800&auto=format&fit=crop',
            'tag'   => 'Research & Study',
        ],
        [
            'title' => 'Auditorium & Arts Theatre',
            'desc'  => 'Modern multipurpose cultural hall for school assemblies, drama productions, music recitals, and debates.',
            'image' => 'https://images.unsplash.com/photo-1580582932707-520aed937b7b?q=80&w=800&auto=format&fit=crop',
            'tag'   => 'Arts & Culture',
        ],
        [
            'title' => 'Sports Complex & Courts',
            'desc'  => 'Standard football pitch, basketball court, volleyball court, and athletic training tracks.',
            'image' => 'https://images.unsplash.com/photo-1526676037777-05a232554f77?q=80&w=800&auto=format&fit=crop',
            'tag'   => 'Athletics & Fitness',
        ],
        [
            'title' => 'Health Bay & Boarding House',
            'desc'  => 'Clean, well-supervised boarding dormitories with on-site registered nurses and medical triage.',
            'image' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=800&auto=format&fit=crop',
            'tag'   => 'Health & Wellness',
        ],
    ]);
@endphp

<!-- ====== Modern Theme Facilities Section ====== -->
<section id="facilities" class="py-16 sm:py-24 bg-white relative" x-data="{ modalOpen: false, activeImage: '', activeTitle: '', activeDesc: '' }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-12 sm:mb-16">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-accent/15 text-accent text-xs font-sans font-bold uppercase tracking-wider">
                <span>{{ $facilitiesEyebrow }}</span>
            </div>
            <h2 class="font-sans font-extrabold text-ink text-2xl sm:text-3xl lg:text-4xl tracking-tight leading-tight">
                {{ $facilitiesHeading }}
            </h2>
        </div>

        <!-- 3-Column Modern Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            @foreach($facilitiesItems as $facility)
                @php
                    $img = \App\Services\FrontendLibrary::imageUrl($facility['image'] ?? '');
                    $title = $facility['title'] ?? '';
                    $desc = $facility['desc'] ?? '';
                    $tag = $facility['tag'] ?? 'Campus Facility';
                @endphp
                <div class="group rounded-3xl overflow-hidden bg-slate-50 border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 cursor-pointer"
                     @click="modalOpen = true; activeImage = '{{ $img }}'; activeTitle = '{{ addslashes($title) }}'; activeDesc = '{{ addslashes($desc) }}'">
                    
                    <!-- Image with Tag -->
                    <div class="relative aspect-[16/10] overflow-hidden bg-slate-200">
                        <img src="{{ $img }}"
                             alt="{{ $title }}"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-60 group-hover:opacity-40 transition-opacity"></div>

                        <div class="absolute top-3 left-3">
                            <span class="px-3 py-1 rounded-full bg-white/90 backdrop-blur-md text-ink text-[11px] font-sans font-bold shadow-sm">
                                {{ $tag }}
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <h3 class="font-sans font-bold text-lg text-ink mb-2 group-hover:text-accent transition-colors">
                            {{ $title }}
                        </h3>
                        <p class="font-sans text-xs sm:text-sm text-slate-600 leading-relaxed line-clamp-2">
                            {{ $desc }}
                        </p>
                        <div class="mt-4 flex items-center text-xs font-sans font-semibold text-accent gap-1">
                            <span>View details & photo</span>
                            <span class="group-hover:translate-x-1 transition-transform">→</span>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

    </div>

    <!-- Interactive Lightbox Modal -->
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 bg-black/80 backdrop-blur-sm"
         style="display: none;"
         @keydown.escape.window="modalOpen = false">
        
        <div class="relative bg-white rounded-3xl max-w-2xl w-full overflow-hidden shadow-2xl border border-slate-100"
             @click.away="modalOpen = false">
            
            <button @click="modalOpen = false"
                    class="absolute top-4 right-4 z-10 w-9 h-9 rounded-full bg-black/50 hover:bg-black text-white flex items-center justify-center font-bold text-sm transition-colors">
                ✕
            </button>

            <div class="aspect-[16/10] bg-slate-900">
                <img :src="activeImage" :alt="activeTitle" class="w-full h-full object-cover" />
            </div>

            <div class="p-6 sm:p-8 space-y-2">
                <h4 class="font-sans font-extrabold text-xl text-ink" x-text="activeTitle"></h4>
                <p class="font-sans text-sm text-slate-600 leading-relaxed" x-text="activeDesc"></p>
            </div>
        </div>
    </div>
</section>
