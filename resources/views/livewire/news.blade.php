<div>
    <!-- Header -->
    <div class="relative overflow-hidden py-20" style="background: linear-gradient(135deg, color-mix(in srgb, var(--color-tenant-primary) 8%, white), color-mix(in srgb, var(--color-tenant-primary) 3%, white));">
        <div class="absolute top-0 right-0 w-64 h-64 rounded-full opacity-[0.06] -translate-y-1/3 translate-x-1/4" style="background: var(--color-tenant-primary);"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
            <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl lg:text-6xl">News & Events</h1>
            <p class="mt-4 text-xl font-medium text-tenant-primary">Stay updated with the latest happenings.</p>
        </div>
    </div>

    <!-- News Grid -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @forelse($posts as $post)
                    <div class="flex flex-col rounded-lg shadow-lg overflow-hidden">
                        <div class="flex-shrink-0">
                            <img class="h-48 w-full object-cover" src="{{ Str::startsWith($post->image, 'http') ? $post->image : Storage::url($post->image) }}" alt="{{ $post->title }}">
                        </div>
                        <div class="flex-1 bg-white p-6 flex flex-col justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-tenant-accent">
                                    News
                                </p>
                                <a href="{{ route('post', $post) }}" class="block mt-2">
                                    <p class="text-xl font-semibold text-gray-900">
                                        {{ $post->title }}
                                    </p>
                                    <p class="mt-3 text-base text-gray-500 line-clamp-3">
                                        {{ Str::limit(strip_tags($post->body), 100) }}
                                    </p>
                                </a>
                            </div>
                            <div class="mt-6 flex items-center">
                                <div class="flex-shrink-0">
                                    <span class="sr-only">WKFS</span>
                                </div>
                                <div class="ml-3">
                                    <div class="flex space-x-1 text-sm text-gray-500">
                                        <time datetime="{{ $post->published_at->format('Y-m-d') }}">
                                            {{ $post->published_at->format('M d, Y') }}
                                        </time>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center text-gray-500">
                        No news available at the moment.
                    </div>
                @endforelse
            </div>

            <div class="mt-12">
                {{ $posts->links() }}
            </div>
        </div>
    </div>
</div>
