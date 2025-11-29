<div>
    <!-- Header -->
    <div class="bg-dark-green py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-extrabold text-white sm:text-5xl lg:text-6xl">Gallery</h1>
            <p class="mt-4 text-xl text-lime-green">Moments captured at WKFS.</p>
        </div>
    </div>

    <!-- Gallery -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Filter -->
            <div class="flex justify-center space-x-4 mb-12 flex-wrap">
                <button wire:click="setCategory('all')" class="px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 {{ $category === 'all' ? 'bg-lime-green text-dark-green' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    All
                </button>
                @foreach($categories as $cat)
                    <button wire:click="setCategory('{{ $cat }}')" class="px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 {{ $category === $cat ? 'bg-lime-green text-dark-green' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ ucfirst($cat) }}
                    </button>
                @endforeach
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse($images as $image)
                    <div class="relative group overflow-hidden rounded-lg shadow-lg aspect-w-1 aspect-h-1">
                        <img class="w-full h-full object-cover transform transition-transform duration-300 group-hover:scale-110" src="{{ Str::startsWith($image->image, 'http') ? $image->image : Storage::url($image->image) }}" alt="{{ $image->caption }}">
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-opacity duration-300 flex items-center justify-center">
                            <p class="text-white text-center px-4 opacity-0 group-hover:opacity-100 transition-opacity duration-300 font-medium">
                                {{ $image->caption }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center text-gray-500">
                        No images found.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
