@php
    $facilitiesEyebrow = \App\Services\FrontendLibrary::get('facilities_eyebrow', 'CAMPUS INFRASTRUCTURE');
    $facilitiesHeading = \App\Services\FrontendLibrary::get('facilities_heading', 'Purpose-Built Learning & Living Environments');
    $facilitiesItems = \App\Services\FrontendLibrary::getJson('facilities_items', [
        [
            'title'    => 'Advanced Science Laboratories',
            'category' => 'ACADEMIC',
            'desc'     => 'Dedicated biology, chemistry, and physics laboratories fully fitted with modern glassware, fume hoods, and analytical instrumentation.',
            'image'    => 'https://placehold.co/1000x800/0B2545/FAF8F4?text=Science+Laboratories+Apex+Crown',
        ],
        [
            'title'    => 'Digital ICT & AI Suites',
            'category' => 'TECHNOLOGY',
            'desc'     => 'High-speed gigabit workstations, interactive smartboards, and robotics hardware kits.',
            'image'    => 'https://placehold.co/600x400/0B2545/FAF8F4?text=Digital+ICT+Suites',
        ],
        [
            'title'    => 'E-Library & Study Commons',
            'category' => 'RESEARCH',
            'desc'     => 'Over 15,000 bound volumes complemented by digital JSTOR and Britannica research terminals.',
            'image'    => 'https://placehold.co/600x400/0B2545/FAF8F4?text=E-Library+Commons',
        ],
        [
            'title'    => 'Sports Arena & Athletic Complex',
            'category' => 'ATHLETICS',
            'desc'     => 'Standard football pitch, outdoor basketball and tennis courts, and all-weather track.',
            'image'    => 'https://placehold.co/600x400/0B2545/FAF8F4?text=Sports+Complex',
        ],
        [
            'title'    => 'Residential Hostels & Dining',
            'category' => 'RESIDENTIAL',
            'desc'     => 'Air-conditioned boarding houses with 24/7 power backup, resident house parents, and dining hall.',
            'image'    => 'https://placehold.co/600x400/0B2545/FAF8F4?text=Boarding+Hostels',
        ],
        [
            'title'    => 'Acoustic Auditorium & Music Studio',
            'category' => 'CULTURE',
            'desc'     => '800-seat theater hall for assemblies, orchestral recitals, and graduation valedictions.',
            'image'    => 'https://placehold.co/600x400/0B2545/FAF8F4?text=Auditorium+Studio',
        ],
    ]);
@endphp

<!-- ====== 05 — Campus Infrastructure (Uneven Editorial Gallery) ====== -->
<section id="facilities" class="py-24 md:py-32 bg-paper"
         x-data="{ 
            modalOpen: false, 
            activeTitle: '', 
            activeDesc: '', 
            activeImg: '', 
            openZoom(title, desc, img) {
                this.activeTitle = title;
                this.activeDesc = desc;
                this.activeImg = img;
                this.modalOpen = true;
            }
         }">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
          05 &mdash; {{ $facilitiesEyebrow }}
        </span>
        <span class="flex-grow h-[1px] bg-rule"></span>
      </div>
    </div>

    <!-- Section Heading -->
    <div class="mb-16 max-w-2xl">
      <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
        {{ $facilitiesHeading }}
      </h2>
    </div>

    <!-- Deliberately Uneven Grid: First item 2x2 (col-span-8 row-span-2), remaining 1x1 (col-span-4) -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
      
      @foreach($facilitiesItems as $index => $fac)
        @php
            $spanClass = $index === 0 ? 'col-span-12 md:col-span-8 md:row-span-2' : 'col-span-12 md:col-span-4';
        @endphp
        <div @click="openZoom('{{ addslashes($fac['title'] ?? '') }}', '{{ addslashes($fac['desc'] ?? '') }}', '{{ $fac['image'] ?? '' }}')"
             class="{{ $spanClass }} relative group cursor-pointer border border-rule bg-white p-2 overflow-hidden">
          
          <div class="relative w-full {{ $index === 0 ? 'h-[360px] sm:h-[480px] md:h-full min-h-[380px]' : 'h-[240px]' }} overflow-hidden">
            <img src="{{ $fac['image'] ?? '' }}"
                 alt="{{ $fac['title'] ?? 'Campus Facility' }}"
                 loading="lazy"
                 class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-700" />
            <div class="absolute inset-0 bg-ink/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center p-6 text-center">
              <span class="px-5 py-2.5 bg-paper text-ink text-xs uppercase tracking-widest font-sans font-bold">
                Examine Facility &rarr;
              </span>
            </div>
          </div>

          <!-- Caption Bar -->
          <div class="p-4 bg-white space-y-1">
            <span class="text-[10px] uppercase tracking-widest text-accent font-sans font-bold block">
              {{ $fac['category'] ?? 'CAMPUS' }}
            </span>
            <h3 class="font-serif text-base sm:text-lg font-semibold text-ink">
              {{ $fac['title'] ?? '' }}
            </h3>
            <p class="text-xs text-body/80 font-sans line-clamp-2 leading-relaxed">
              {{ $fac['desc'] ?? '' }}
            </p>
          </div>

        </div>
      @endforeach

    </div>

  </div>

  <!-- Editorial Lightbox Modal -->
  <div x-show="modalOpen" x-cloak
       @keydown.escape.window="modalOpen = false"
       class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/90 backdrop-blur-sm">
    <div @click.outside="modalOpen = false"
         class="relative max-w-3xl w-full bg-paper border border-rule p-4 sm:p-6 shadow-2xl rounded-none">
      <button @click="modalOpen = false" class="absolute top-4 right-4 z-10 w-8 h-8 bg-ink text-white flex items-center justify-center text-sm font-bold">
        &times;
      </button>
      <img :src="activeImg" :alt="activeTitle" class="w-full h-72 sm:h-96 object-cover border border-rule" />
      <div class="mt-4 space-y-2">
        <h3 class="font-serif text-xl sm:text-2xl font-semibold text-ink" x-text="activeTitle"></h3>
        <p class="text-xs sm:text-sm text-body font-sans leading-[1.7] max-w-[62ch]" x-text="activeDesc"></p>
        <div class="pt-3 flex justify-end">
          <button @click="modalOpen = false" class="px-6 py-2.5 text-xs uppercase tracking-widest font-sans font-semibold bg-ink text-white rounded-none">
            Close View
          </button>
        </div>
      </div>
    </div>
  </div>

</section>

<!-- Full container hairline divider -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
  <div class="h-[1px] w-full bg-rule"></div>
</div>
