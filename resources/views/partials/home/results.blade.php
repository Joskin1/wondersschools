@if(!empty($school['stats']))
<!-- ====== 03 — Examination Outcomes (Full-Bleed Ink Band) ====== -->
<section id="stats" class="py-24 md:py-32 bg-ink text-[color:var(--ink-contrast)] relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
          {{ $school['stats']['number'] ?? '03' }} &mdash; {{ $school['stats']['eyebrow'] ?? 'EXAMINATION OUTCOMES' }}
        </span>
        <span class="flex-grow h-[1px] bg-white/15"></span>
      </div>
    </div>

    <!-- Left-aligned Section Heading -->
    <div class="mb-16 max-w-2xl">
      <h2 class="font-serif font-semibold text-white tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
        {{ $school['stats']['heading'] }}
      </h2>
    </div>

    <!-- 4 Stats Counters: Divided by vertical hairlines, NOT gaps -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 border-t border-b border-white/15">
      
      @foreach($school['stats']['items'] as $index => $stat)
        <div class="py-10 px-6 sm:px-8 {{ !$loop->last ? 'lg:border-r border-white/15' : '' }} {{ $index % 2 == 0 ? 'sm:border-r lg:border-r-0' : '' }} {{ $loop->iteration > 2 ? 'border-t sm:border-t-0' : '' }} space-y-3">
          <!-- Serif Numerals text-6xl in Gold -->
          <div class="font-serif text-5xl sm:text-6xl font-semibold text-accent tracking-tight leading-none">
            {{ $stat['value'] }}
          </div>
          <!-- Thin Uppercase Labels -->
          <div class="text-xs uppercase tracking-[0.15em] text-white font-sans font-semibold">
            {{ $stat['label'] }}
          </div>
          <p class="text-xs text-white/70 font-sans leading-relaxed max-w-[32ch]">
            {{ $stat['detail'] }}
          </p>
        </div>
      @endforeach

    </div>

    <!-- University Destinations Footnote -->
    @if(!empty($school['stats']['destinations']))
      <div class="mt-12 pt-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 text-xs font-sans">
        <span class="text-accent uppercase tracking-widest font-semibold">Representative Matriculations:</span>
        <div class="flex flex-wrap items-center gap-3 text-white/80">
          @foreach($school['stats']['destinations'] as $dest)
            <span>{{ $dest }}</span>
            @if(!$loop->last)
              <span class="text-accent">&bull;</span>
            @endif
          @endforeach
        </div>
      </div>
    @endif

  </div>
</section>
@endif
