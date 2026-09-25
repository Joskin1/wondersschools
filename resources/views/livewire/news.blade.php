<div>
    <!-- Editorial Page Header -->
    <div class="relative overflow-hidden py-20 lg:py-24 border-b border-[var(--rule)] bg-[var(--paper)]">
        <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(var(--ink) 1px, transparent 1px); background-size: 24px 24px;"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold tracking-wider uppercase bg-[var(--accent)]/10 text-[var(--accent)] mb-4 border border-[var(--accent)]/20">
                <span class="w-1.5 h-1.5 rounded-full bg-[var(--accent)]"></span>
                Official Dispatches &amp; Chronicle
            </div>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-serif font-black tracking-tight text-[var(--ink)] leading-tight">
                News & Events
            </h1>
            <p class="mt-4 text-lg sm:text-xl font-medium text-[var(--support)] max-w-2xl mx-auto leading-relaxed">
                Stay updated with the latest happenings.
            </p>
        </div>
    </div>

    <!-- Newsroom Section -->
    <div class="py-16 sm:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($posts->count() > 0)
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($posts as $post)
                        <article class="group flex flex-col bg-[var(--paper)] border border-[var(--rule)] rounded-2xl overflow-hidden shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                            <!-- Post Image Container -->
                            <div class="relative h-52 sm:h-56 w-full overflow-hidden bg-gray-100">
                                @php
                                    $imageUrl = Str::startsWith($post->image, 'http') ? $post->image : Storage::disk(config('filesystems.upload_disk', 'public'))->url($post->image);
                                @endphp
                                @if($post->image)
                                    <img class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500" 
                                         src="{{ $imageUrl }}" 
                                         alt="{{ $post->title }}"
                                         loading="lazy">
                                @else
                                    <div class="h-full w-full flex items-center justify-center bg-[var(--ink)] text-white/40">
                                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="absolute top-4 left-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider bg-white/95 text-[var(--ink)] shadow-sm backdrop-blur-sm">
                                        News
                                    </span>
                                </div>
                            </div>

                            <!-- Post Content -->
                            <div class="flex-1 p-6 sm:p-7 flex flex-col justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 text-xs font-semibold text-[var(--support)] mb-3">
                                        <svg class="w-4 h-4 text-[var(--accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <time datetime="{{ $post->published_at ? $post->published_at->format('Y-m-d') : '' }}">
                                            {{ $post->published_at ? $post->published_at->format('M d, Y') : '' }}
                                        </time>
                                    </div>

                                    <a href="{{ route('post', $post) }}" class="block group/link">
                                        <h2 class="text-xl font-serif font-bold text-[var(--ink)] group-hover/link:text-[var(--accent)] transition-colors leading-snug line-clamp-2">
                                            {{ $post->title }}
                                        </h2>
                                        <p class="mt-3 text-sm text-[var(--body)] line-clamp-3 leading-relaxed">
                                            {{ Str::limit(strip_tags($post->body), 120) }}
                                        </p>
                                    </a>
                                </div>

                                <div class="mt-6 pt-5 border-t border-[var(--rule)] flex items-center justify-between">
                                    <a href="{{ route('post', $post) }}" class="inline-flex items-center text-xs font-bold uppercase tracking-wider text-[var(--accent)] group-hover:translate-x-1 transition-transform">
                                        <span>Read Story</span>
                                        <svg class="ml-1.5 w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </a>
                                    <span class="text-[11px] font-medium text-[var(--support)]">
                                        {{ max(1, ceil(str_word_count(strip_tags($post->body)) / 200)) }} min read
                                    </span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-12 sm:mt-16 flex justify-center">
                    {{ $posts->links() }}
                </div>
            @else
                <!-- Refined Empty State -->
                <div class="max-w-md mx-auto text-center py-16 px-4 bg-[var(--paper)] rounded-2xl border border-[var(--rule)]">
                    <div class="w-16 h-16 mx-auto rounded-full bg-[var(--accent)]/10 text-[var(--accent)] flex items-center justify-center mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-serif font-bold text-[var(--ink)] mb-2">No news available at the moment</h3>
                    <p class="text-sm text-[var(--support)] leading-relaxed">
                        Check back shortly for upcoming announcements, academic terms schedules, and student achievement showcases.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

