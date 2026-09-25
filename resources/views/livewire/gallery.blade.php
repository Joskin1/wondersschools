<div x-data="{ 
    modalOpen: false, 
    activeImg: '', 
    activeCaption: '', 
    activeCategory: '',
    openModal(img, caption, category) {
        this.activeImg = img;
        this.activeCaption = caption;
        this.activeCategory = category;
        this.modalOpen = true;
    }
}">
    <!-- ====== 01 — Subpage Header Banner ====== -->
    <section class="relative bg-ink text-paper py-20 sm:py-28 overflow-hidden border-b border-rule">
        <div class="absolute inset-0 z-0 opacity-15">
            <img src="https://images.unsplash.com/photo-1521587760476-6c12a4b040da?auto=format&fit=crop&w=1920&q=80"
                 alt="Gallery Cover"
                 class="w-full h-full object-cover object-center" />
            <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/90 to-ink/70"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-[1px] bg-accent"></span>
                    <span class="text-xs uppercase tracking-[0.25em] text-accent font-sans font-semibold">
                        Campus Photographic Archive
                    </span>
                </div>
                <h1 class="font-serif font-semibold text-white tracking-tight leading-[1.1]" style="font-size: clamp(2.5rem, 5vw, 4rem);">
                    Gallery
                </h1>
                <p class="text-base sm:text-lg text-paper/80 font-sans leading-relaxed max-w-[62ch]">
                    Moments captured at our school.
                </p>
            </div>
        </div>
    </section>

    <!-- ====== 02 — Category Filters & Grid ====== -->
    <section class="py-16 sm:py-24 bg-paper min-h-[60vh]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Category Filter Tabs -->
            <div class="flex items-center justify-center gap-2 sm:gap-3 mb-12 flex-wrap font-sans">
                <button wire:click="setCategory('all')"
                        class="px-5 py-2.5 text-xs uppercase tracking-wider font-semibold transition rounded-none {{ $category === 'all' ? 'bg-ink text-accent border border-ink' : 'bg-white text-body/80 border border-rule hover:border-ink hover:text-ink' }}">
                    All
                </button>
                @foreach($categories as $cat)
                    <button wire:click="setCategory('{{ $cat }}')"
                            class="px-5 py-2.5 text-xs uppercase tracking-wider font-semibold transition rounded-none {{ $category === $cat ? 'bg-ink text-accent border border-ink' : 'bg-white text-body/80 border border-rule hover:border-ink hover:text-ink' }}">
                        {{ ucfirst($cat) }}
                    </button>
                @endforeach
            </div>

            <!-- Image Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @forelse($images as $image)
                    @php
                        $imgUrl = Str::startsWith($image->image, 'http') ? $image->image : \App\Services\FrontendLibrary::imageUrl($image->image);
                    @endphp
                    <div @click="openModal('{{ $imgUrl }}', '{{ addslashes($image->caption) }}', '{{ addslashes($image->category ?? 'Campus') }}')"
                         class="group relative border border-rule bg-white p-2 cursor-pointer overflow-hidden hover:border-ink transition">
                        
                        <div class="relative w-full h-64 overflow-hidden bg-paper">
                            <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                 src="{{ $imgUrl }}"
                                 alt="{{ $image->caption }}"
                                 loading="lazy">
                            
                            <div class="absolute inset-0 bg-ink/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center p-4 text-center">
                                <span class="px-4 py-2 bg-paper text-ink text-[11px] uppercase tracking-widest font-sans font-bold shadow">
                                    Enlarge Photo &rarr;
                                </span>
                            </div>
                        </div>

                        <div class="p-3 bg-white space-y-1">
                            @if(!empty($image->category))
                                <span class="text-[10px] uppercase tracking-widest font-sans font-bold text-accent block">
                                    {{ $image->category }}
                                </span>
                            @endif
                            <p class="font-serif text-sm font-semibold text-ink line-clamp-1">
                                {{ $image->caption }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full border border-dashed border-rule p-16 text-center text-sm font-sans text-body/70">
                        No images found.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- ====== 03 — Lightbox Modal ====== -->
    <div x-show="modalOpen" x-cloak
         @keydown.escape.window="modalOpen = false"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-ink/90 backdrop-blur-sm">
        <div @click.outside="modalOpen = false"
             class="relative max-w-4xl w-full bg-paper border border-rule p-4 sm:p-6 shadow-2xl rounded-none">
            <button @click="modalOpen = false"
                    class="absolute top-4 right-4 z-10 w-8 h-8 bg-ink text-white flex items-center justify-center text-sm font-bold">
                &times;
            </button>
            <div class="w-full max-h-[70vh] overflow-hidden flex items-center justify-center bg-black/5 border border-rule">
                <img :src="activeImg" :alt="activeCaption" class="max-h-[70vh] w-auto object-contain" />
            </div>
            <div class="mt-4 flex items-center justify-between font-sans">
                <div>
                    <span class="text-[10px] uppercase tracking-widest text-accent font-bold block" x-text="activeCategory"></span>
                    <h3 class="font-serif text-lg sm:text-xl font-semibold text-ink" x-text="activeCaption"></h3>
                </div>
                <button @click="modalOpen = false" class="px-5 py-2 text-xs uppercase tracking-widest font-semibold bg-ink text-white rounded-none">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
