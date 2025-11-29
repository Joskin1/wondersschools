<div>
    <!-- Header Image -->
    <div class="relative h-96">
        <img class="w-full h-full object-cover" src="{{ Str::startsWith($post->image, 'http') ? $post->image : Storage::url($post->image) }}" alt="{{ $post->title }}">
        <div class="absolute inset-0 bg-gray-900 bg-opacity-50"></div>
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center px-4">
                <h1 class="text-4xl font-extrabold text-white sm:text-5xl lg:text-6xl">{{ $post->title }}</h1>
                <p class="mt-4 text-xl text-gray-200">
                    {{ $post->published_at->format('F d, Y') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="py-16 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-lg prose-green mx-auto text-gray-500">
                {!! $post->body !!}
            </div>

            <div class="mt-12 border-t border-gray-200 pt-8">
                <a href="{{ route('news') }}" class="text-dark-green hover:text-lime-green font-medium">
                    &larr; Back to News
                </a>
            </div>
        </div>
    </div>
</div>
