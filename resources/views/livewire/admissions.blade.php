<div>
    <!-- Header -->
    <div class="bg-dark-green py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-extrabold text-white sm:text-5xl lg:text-6xl">Admissions</h1>
            <p class="mt-4 text-xl text-lime-green">Join the WKFS family today.</p>
        </div>
    </div>

    <!-- Process -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">Admission Process</h2>
                <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                    We have made our admission process simple and transparent.
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                <div class="relative">
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-lime-green text-dark-green text-2xl font-bold mx-auto mb-4">
                        1
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Inquire</h3>
                    <p class="text-gray-500">
                        Fill out the inquiry form below or visit the school to pick up an application form.
                    </p>
                </div>
                <div class="relative">
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-lime-green text-dark-green text-2xl font-bold mx-auto mb-4">
                        2
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Assessment</h3>
                    <p class="text-gray-500">
                        Schedule a brief assessment for your child to help us understand their needs and placement.
                    </p>
                </div>
                <div class="relative">
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-lime-green text-dark-green text-2xl font-bold mx-auto mb-4">
                        3
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Enrollment</h3>
                    <p class="text-gray-500">
                        Complete the registration process and welcome to the WKFS family!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Fee Schedule -->
    <div class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-8">School Fees</h2>
            <p class="text-xl text-gray-500 mb-8">
                Our fee structure is competitive and offers great value for the quality of education we provide.
            </p>
            @if(\App\Models\Setting::where('key', 'fee_schedule_link')->value('value'))
                <a href="{{ \App\Models\Setting::where('key', 'fee_schedule_link')->value('value') }}" target="_blank" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-dark-green hover:bg-opacity-90">
                    Download Fee Schedule (PDF)
                </a>
            @else
                <div class="bg-white shadow overflow-hidden sm:rounded-lg max-w-3xl mx-auto">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Current Term Fees
                        </h3>
                    </div>
                    <div class="border-t border-gray-200">
                        <dl>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    Creche / Playgroup
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    Contact for details
                                </dd>
                            </div>
                            <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    Nursery
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    Contact for details
                                </dd>
                            </div>
                            <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500">
                                    Primary
                                </dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                                    Contact for details
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Inquiry Form -->
    <div class="py-16 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">Admission Inquiry</h2>
                <p class="mt-4 text-xl text-gray-500">
                    Interested in enrolling your child? Fill out the form below.
                </p>
            </div>

            @if (session()->has('message'))
                <div class="rounded-md bg-green-50 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ session('message') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="submit" class="grid grid-cols-1 gap-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Parent's Name</label>
                    <div class="mt-1">
                        <input type="text" wire:model="name" id="name" class="py-3 px-4 block w-full shadow-sm focus:ring-lime-green focus:border-lime-green border-gray-300 rounded-md">
                    </div>
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <div class="mt-1">
                        <input type="email" wire:model="email" id="email" class="py-3 px-4 block w-full shadow-sm focus:ring-lime-green focus:border-lime-green border-gray-300 rounded-md">
                    </div>
                    @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <div class="mt-1">
                        <input type="text" wire:model="phone" id="phone" class="py-3 px-4 block w-full shadow-sm focus:ring-lime-green focus:border-lime-green border-gray-300 rounded-md">
                    </div>
                    @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="child_age" class="block text-sm font-medium text-gray-700">Child's Age / Class</label>
                    <div class="mt-1">
                        <input type="text" wire:model="child_age" id="child_age" class="py-3 px-4 block w-full shadow-sm focus:ring-lime-green focus:border-lime-green border-gray-300 rounded-md">
                    </div>
                    @error('child_age') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700">Additional Message (Optional)</label>
                    <div class="mt-1">
                        <textarea wire:model="message" id="message" rows="4" class="py-3 px-4 block w-full shadow-sm focus:ring-lime-green focus:border-lime-green border-gray-300 rounded-md"></textarea>
                    </div>
                    @error('message') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <button type="submit" class="w-full inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-md text-white bg-dark-green hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-green">
                        Submit Inquiry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
