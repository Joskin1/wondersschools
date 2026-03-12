<div>
    <!-- Header -->
    <div class="bg-dark-green py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-extrabold text-white sm:text-5xl lg:text-6xl">
                Admissions
            </h1>
            <p class="mt-4 text-xl text-tenant-accent max-w-2xl mx-auto">
                Start your child’s journey with excellence, character, and purpose at WKFS.
            </p>
        </div>
    </div>

    <!-- Admission Process -->
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:text-center mb-16">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Our Admission Process
                </h2>
                <p class="mt-4 max-w-2xl text-lg text-gray-600 lg:mx-auto">
                    We’ve designed a simple and transparent process to make enrollment smooth and stress-free.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 text-center">
                
                <!-- Step 1 -->
                <div>
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-tenant-primary text-dark-green text-2xl font-bold mx-auto mb-6">
                        1
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">
                        Make an Inquiry
                    </h3>
                    <p class="text-gray-600">
                        Complete the inquiry form below or visit our campus to obtain an application form and speak with our admissions team.
                    </p>
                </div>

                <!-- Step 2 -->
                <div>
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-tenant-primary text-dark-green text-2xl font-bold mx-auto mb-6">
                        2
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">
                        Child Assessment
                    </h3>
                    <p class="text-gray-600">
                        Your child will participate in a brief assessment to help us determine the appropriate class placement and support needs.
                    </p>
                </div>

                <!-- Step 3 -->
                <div>
                    <div class="flex items-center justify-center h-16 w-16 rounded-full bg-tenant-primary text-dark-green text-2xl font-bold mx-auto mb-6">
                        3
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3">
                        Complete Enrollment
                    </h3>
                    <p class="text-gray-600">
                        Finalize documentation and payment, and officially join the WKFS learning community.
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Fee Schedule -->
    <div class="py-20 bg-gray-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-gray-900 mb-6">
                School Fees
            </h2>
            <p class="text-lg text-gray-600 mb-10 max-w-2xl mx-auto">
                Our fees are structured to provide excellent value while maintaining high academic and developmental standards.
            </p>

            @if(\App\Models\Setting::where('key', 'fee_schedule_link')->value('value'))
                <a href="{{ \App\Models\Setting::where('key', 'fee_schedule_link')->value('value') }}" 
                   target="_blank" 
                   class="inline-flex items-center justify-center px-6 py-3 text-base font-medium rounded-md text-white bg-dark-green hover:bg-opacity-90 transition">
                    Download Full Fee Schedule (PDF)
                </a>
            @else
                <div class="bg-white shadow-md rounded-lg overflow-hidden text-left">
                    <div class="px-6 py-5 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Current Term Fee Categories
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <div class="px-6 py-4 flex justify-between">
                            <span class="font-medium text-gray-700">Creche / Playgroup</span>
                            <span class="text-gray-600">Contact Admissions</span>
                        </div>
                        <div class="px-6 py-4 flex justify-between">
                            <span class="font-medium text-gray-700">Nursery</span>
                            <span class="text-gray-600">Contact Admissions</span>
                        </div>
                        <div class="px-6 py-4 flex justify-between">
                            <span class="font-medium text-gray-700">Primary</span>
                            <span class="text-gray-600">Contact Admissions</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Inquiry Form -->
    <div class="py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Admission Inquiry Form
                </h2>
                <p class="mt-4 text-lg text-gray-600">
                    Complete the form below and our admissions team will contact you shortly.
                </p>
            </div>

            @if (session()->has('message'))
                <div class="rounded-md bg-green-50 p-4 mb-6 border border-green-200">
                    <p class="text-sm font-medium text-green-800">
                        {{ session('message') }}
                    </p>
                </div>
            @endif

            <form wire:submit.prevent="submit" class="grid grid-cols-1 gap-y-6">

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Parent's Full Name
                    </label>
                    <input type="text" wire:model="name"
                           class="mt-1 py-3 px-4 block w-full shadow-sm border-gray-300 rounded-md focus:ring-tenant-accent focus:border-tenant-accent">
                    @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Email Address
                    </label>
                    <input type="email" wire:model="email"
                           class="mt-1 py-3 px-4 block w-full shadow-sm border-gray-300 rounded-md focus:ring-tenant-accent focus:border-tenant-accent">
                    @error('email') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Phone Number
                    </label>
                    <input type="text" wire:model="phone"
                           class="mt-1 py-3 px-4 block w-full shadow-sm border-gray-300 rounded-md focus:ring-tenant-accent focus:border-tenant-accent">
                    @error('phone') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Child’s Age / Intended Class
                    </label>
                    <input type="text" wire:model="child_age"
                           class="mt-1 py-3 px-4 block w-full shadow-sm border-gray-300 rounded-md focus:ring-tenant-accent focus:border-tenant-accent">
                    @error('child_age') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Additional Information (Optional)
                    </label>
                    <textarea wire:model="message" rows="4"
                              class="mt-1 py-3 px-4 block w-full shadow-sm border-gray-300 rounded-md focus:ring-tenant-accent focus:border-tenant-accent"></textarea>
                    @error('message') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <button type="submit"
                            class="w-full py-3 px-6 text-base font-medium rounded-md text-white bg-dark-green hover:bg-opacity-90 transition">
                        Submit Admission Inquiry
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>