<div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 flex flex-col justify-center">
    <div class="max-w-2xl mx-auto w-full">
        
        {{-- Brand & Header Card --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 text-indigo-700 mb-4 shadow-sm">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                </svg>
            </div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight sm:text-4xl">
                Teacher Self-Registration
            </h1>
            <p class="mt-2 text-base text-gray-600">
                Join <span class="font-semibold text-indigo-900">{{ $appName }}</span> academic staff. Complete your details below to register your profile.
            </p>
        </div>

        @if (session()->has('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-md shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0 text-red-500">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if ($submitted)
            {{-- Registration Success Card --}}
            <div class="bg-white rounded-2xl shadow-xl p-8 sm:p-10 text-center border border-gray-100 transform transition-all">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-emerald-100 text-emerald-600 mb-6">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Registration Submitted Successfully!</h2>
                <p class="text-gray-600 leading-relaxed max-w-md mx-auto mb-6">
                    Thank you, <span class="font-semibold text-gray-900">{{ $name }}</span>. Your teacher registration profile has been recorded in our system.
                </p>

                <div class="bg-indigo-50/70 border border-indigo-100 rounded-xl p-5 mb-8 text-left">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 text-indigo-600 mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-semibold text-indigo-900">What happens next?</h3>
                            <p class="text-xs sm:text-sm text-indigo-800 mt-1 leading-relaxed">
                                Your portal access is currently pending administrator approval. As soon as the school admin toggles your portal access, an automated welcome email with a direct login link will be sent to <strong>{{ $email }}</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition">
                        Return to Homepage
                    </a>
                </div>
            </div>
        @else
            {{-- Main Form Card --}}
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <form wire:submit.prevent="submit" class="p-6 sm:p-10 space-y-6">
                    
                    {{-- Profile Picture Section --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture (Optional)</label>
                        <div class="flex items-center space-x-6">
                            <div class="relative flex-shrink-0">
                                @if ($profile_picture)
                                    <img src="{{ $profile_picture->temporaryUrl() }}" class="h-20 w-20 rounded-full object-cover border-2 border-indigo-500 shadow">
                                @else
                                    <div class="h-20 w-20 rounded-full bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400">
                                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div>
                                <input type="file" wire:model="profile_picture" id="profile_picture" class="hidden" accept="image/*">
                                <label for="profile_picture" class="cursor-pointer inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition">
                                    <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    Upload Photo
                                </label>
                                <p class="text-xs text-gray-500 mt-1">PNG, JPG up to 2MB</p>
                                @error('profile_picture') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="border-gray-100">

                    {{-- Personal Information Grid --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        
                        {{-- Full Name --}}
                        <div class="sm:col-span-2">
                            <label for="name" class="block text-sm font-medium text-gray-700">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="name" id="name" placeholder="e.g. John Doe" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-3 px-4">
                            @error('name') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Email Address --}}
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address <span class="text-red-500">*</span></label>
                            <input type="email" wire:model.defer="email" id="email" placeholder="teacher@example.com" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-3 px-4">
                            @error('email') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Phone Number --}}
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="phone" id="phone" placeholder="08012345678" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-3 px-4">
                            @error('phone') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Gender --}}
                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700">Gender <span class="text-red-500">*</span></label>
                            <select wire:model.defer="gender" id="gender" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-3 px-4">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            @error('gender') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Date of Birth --}}
                        <div>
                            <label for="dob" class="block text-sm font-medium text-gray-700">Date of Birth <span class="text-red-500">*</span></label>
                            <input type="date" wire:model.defer="dob" id="dob" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-3 px-4">
                            @error('dob') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Address --}}
                        <div class="sm:col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-700">Home Address <span class="text-red-500">*</span></label>
                            <textarea wire:model.defer="address" id="address" rows="2" placeholder="Street address, City..." class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-3 px-4"></textarea>
                            @error('address') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Password --}}
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Portal Password <span class="text-red-500">*</span></label>
                            <input type="password" wire:model.defer="password" id="password" placeholder="••••••••" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-3 px-4">
                            @error('password') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        {{-- Password Confirmation --}}
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                            <input type="password" wire:model.defer="password_confirmation" id="password_confirmation" placeholder="••••••••" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-3 px-4">
                            @error('password_confirmation') <span class="text-xs text-red-600 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="pt-4">
                        <button type="submit" wire:loading.attr="disabled" class="w-full inline-flex justify-center items-center py-3.5 px-6 border border-transparent rounded-xl shadow-md text-base font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 disabled:opacity-50">
                            <svg wire:loading class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Complete Teacher Registration</span>
                        </button>
                    </div>

                </form>
            </div>
        @endif

    </div>
</div>
