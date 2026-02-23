<div>
    <!-- Header -->
    <div class="bg-dark-green py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-extrabold text-white sm:text-5xl lg:text-6xl">We Build Foundations That Last.</h1>
            <p class="mt-4 text-xl text-lime-green">Wonders Kiddies Foundation Schools</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-lg mx-auto text-gray-500">
                <p>
                    Wonders Kiddies Foundation Schools (WKFS) is dedicated to providing a high-quality, nurturing, and secure educational environment. Our approach is simple: we focus on the <strong>whole child</strong>—intellectually, emotionally, and morally—to ensure they thrive in every aspect of life. We believe a strong foundation in the early years is the rarest, most valuable asset a parent can provide.
                </p>
            </div>
        </div>
    </div>

    <!-- Mission, Vision, Values -->
    <div class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <!-- Mission -->
                <div class="bg-white p-8 rounded-xl shadow-md border-l-4 border-lime-green">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Our Mission</h3>
                    <p class="text-gray-600 text-lg leading-relaxed">
                        To deliver secure, well-planned education that fosters creativity, academic mastery, and strong character development.
                    </p>
                </div>

                <!-- Vision -->
                <div class="bg-white p-8 rounded-xl shadow-md border-l-4 border-light-blue">
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Our Vision</h3>
                    <p class="text-gray-600 text-lg leading-relaxed">
                        To be the most trusted educational brand known for foundational excellence, transparency, and dependable long-term student success.
                    </p>
                </div>
            </div>

            <!-- Core Values -->
            <div class="mt-16">
                <h3 class="text-3xl font-extrabold text-center text-gray-900 mb-10">Our Core Values</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition text-center">
                        <div class="w-12 h-12 bg-dark-green text-white rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">1</div>
                        <h4 class="text-lg font-bold text-gray-900">Integrity of Instruction</h4>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition text-center">
                        <div class="w-12 h-12 bg-dark-green text-white rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">2</div>
                        <h4 class="text-lg font-bold text-gray-900">Student-Centric Nurturing</h4>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition text-center">
                        <div class="w-12 h-12 bg-dark-green text-white rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">3</div>
                        <h4 class="text-lg font-bold text-gray-900">Strategic Curriculum Delivery</h4>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition text-center">
                        <div class="w-12 h-12 bg-dark-green text-white rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">4</div>
                        <h4 class="text-lg font-bold text-gray-900">Transparent Parent Partnership</h4>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow hover:shadow-md transition text-center">
                        <div class="w-12 h-12 bg-dark-green text-white rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">5</div>
                        <h4 class="text-lg font-bold text-gray-900">Long-term Value Creation</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leadership -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">Meet Our Leadership</h2>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 mx-auto">
                    The dedicated team guiding our school.
                </p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-12">
                @forelse($staff as $member)
                    <div class="text-center group">
                        <div class="space-y-4">
                            <div class="mx-auto h-40 w-40 rounded-full overflow-hidden border-4 border-lime-green shadow-lg transform group-hover:scale-105 transition duration-300">
                                <img class="w-full h-full object-cover" src="{{ Str::startsWith($member->image, 'http') ? $member->image : Storage::url($member->image) }}" alt="{{ $member->name }}">
                            </div>
                            <div class="space-y-2">
                                <div class="text-lg leading-6 font-medium space-y-1">
                                    <h3 class="text-xl font-bold text-gray-900">{{ $member->name }}</h3>
                                    <p class="text-dark-green font-medium">{{ $member->role }}</p>
                                </div>
                                <div class="text-sm text-gray-500 max-w-xs mx-auto">
                                    {{ Str::limit($member->bio, 150) }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center text-gray-500">
                        Leadership team information coming soon.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
