<div>
    <!-- Top Article Breadcrumb Bar -->
    <div class="bg-[var(--paper)] border-b border-[var(--rule)] py-4">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <a href="{{ route('news') }}" class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold uppercase tracking-wider text-[var(--support)] hover:text-[var(--accent)] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Back to News</span>
                </a>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-[var(--accent)]/10 text-[var(--accent)] border border-[var(--accent)]/20">
                        Official Dispatch
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Article Header Area -->
    <article class="py-12 sm:py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <header class="mb-10 text-center sm:text-left">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-xs font-semibold text-[var(--support)] mb-4">
                    <span class="inline-flex items-center gap-1.5 bg-[var(--paper)] px-3 py-1 rounded-md border border-[var(--rule)]">
                        <svg class="w-3.5 h-3.5 text-[var(--accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <time datetime="{{ $post->published_at ? $post->published_at->format('Y-m-d') : '' }}">
                            {{ $post->published_at ? $post->published_at->format('F d, Y') : '' }}
                        </time>
                    </span>
                    <span class="text-gray-300">•</span>
                    <span class="text-[var(--support)]">{{ max(1, ceil(str_word_count(strip_tags($post->body)) / 200)) }} min read</span>
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-serif font-black tracking-tight text-[var(--ink)] leading-tight">
                    {{ $post->title }}
                </h1>
            </header>

            <!-- Featured Image -->
            @if($post->image)
                @php
                    $imageUrl = Str::startsWith($post->image, 'http') ? $post->image : Storage::disk(config('filesystems.upload_disk', 'public'))->url($post->image);
                @endphp
                <div class="mb-12 rounded-2xl overflow-hidden shadow-lg border border-[var(--rule)] bg-gray-50">
                    <img class="w-full max-h-[500px] object-cover" 
                         src="{{ $imageUrl }}" 
                         alt="{{ $post->title }}">
                </div>
            @endif

            <!-- Article Body -->
            <div class="prose prose-lg prose-slate max-w-none text-[var(--body)] leading-relaxed
                        prose-headings:font-serif prose-headings:font-bold prose-headings:text-[var(--ink)]
                        prose-p:text-[var(--body)] prose-p:leading-relaxed
                        prose-a:text-[var(--accent)] prose-a:underline hover:prose-a:opacity-80
                        prose-blockquote:border-l-[var(--accent)] prose-blockquote:bg-[var(--paper)] prose-blockquote:py-2 prose-blockquote:px-4 prose-blockquote:rounded-r-lg
                        prose-img:rounded-xl prose-img:shadow-md">
                {!! $post->body !!}
            </div>

            <!-- Author & Institutional Signature -->
            <div class="mt-16 pt-8 border-t border-[var(--rule)] flex flex-col sm:flex-row items-center justify-between gap-6 bg-[var(--paper)] p-6 sm:p-8 rounded-2xl border">
                <div class="flex items-center gap-4 text-center sm:text-left">
                    <div class="w-12 h-12 rounded-full bg-[var(--accent)]/15 text-[var(--accent)] flex items-center justify-center font-serif font-bold text-lg flex-shrink-0">
                        WK
                    </div>
                    <div>
                        <h4 class="font-bold text-[var(--ink)] text-sm">Wonder Kiddies Foundation Schools</h4>
                        <p class="text-xs text-[var(--support)]">Office of Communications &amp; Institutional Registry</p>
                    </div>
                </div>

                <a href="{{ route('news') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border border-[var(--rule)] bg-white shadow-sm text-xs font-bold uppercase tracking-wider text-[var(--ink)] hover:bg-[var(--paper)] hover:text-[var(--accent)] transition-all">
                    <span>Back to News</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>

        </div>
    </article>
</div>

