<div class="min-h-screen py-12 sm:py-16 px-4 sm:px-6 lg:px-8 bg-[var(--paper)]">
    <div class="max-w-3xl mx-auto w-full">
        
        {{-- Brand & Header Card --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold tracking-wider uppercase bg-[var(--accent)]/10 text-[var(--accent)] mb-4 border border-[var(--accent)]/20">
                <span class="w-1.5 h-1.5 rounded-full bg-[var(--accent)]"></span>
                Faculty Recruitment &amp; Accreditation
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-serif font-black text-[var(--ink)] tracking-tight">
                Teacher Self-Registration
            </h1>
            <p class="mt-3 text-base sm:text-lg text-[var(--support)] max-w-xl mx-auto">
                Join <span class="font-bold text-[var(--ink)]">{{ $appName }}</span> academic staff. Complete your details below to register your profile.
            </p>
        </div>

        @if (session()->has('error'))
            <div class="mb-8 bg-red-50 border border-red-200 p-5 rounded-2xl shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 text-red-600 mt-0.5">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-red-900">Registration Error</h4>
                        <p class="text-sm text-red-700 mt-0.5">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if ($submitted)
            {{-- Registration Success Card --}}
            <div class="bg-white rounded-3xl border border-[var(--rule)] shadow-xl p-8 sm:p-12 text-center">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-2xl bg-emerald-100 text-emerald-700 mb-6 shadow-inner">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                
                <span class="text-xs font-bold uppercase tracking-wider text-[var(--accent)]">Submission Verified</span>
                <h2 class="text-2xl sm:text-3xl font-serif font-bold text-[var(--ink)] mt-1 mb-3">Registration Submitted Successfully!</h2>
                <p class="text-sm sm:text-base text-[var(--body)] max-w-md mx-auto mb-8 leading-relaxed">
                    Thank you, <span class="font-bold text-[var(--ink)]">{{ $name }}</span>. Your teacher registration profile has been recorded in our system.
                </p>

                <div class="bg-[var(--paper)] border border-[var(--rule)] rounded-2xl p-6 sm:p-7 mb-8 text-left max-w-lg mx-auto">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 text-[var(--accent)] mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-[var(--ink)]">What happens next?</h3>
                            <p class="text-xs sm:text-sm text-[var(--body)] mt-1 leading-relaxed">
                                Your portal access is currently pending administrator approval. As soon as the school admin toggles your portal access, an automated welcome email with a direct login link will be sent to <strong>{{ $email }}</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-8 py-3.5 border border-[var(--rule)] shadow-sm text-sm font-bold uppercase tracking-wider rounded-xl text-[var(--ink)] bg-white hover:bg-[var(--paper)] focus:outline-none transition">
                        <span>Return to Homepage</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        @else
            {{-- Main Form Card --}}
            <div class="bg-white rounded-3xl border border-[var(--rule)] shadow-xl overflow-hidden">
                <form wire:submit.prevent="submit" class="p-6 sm:p-10 space-y-10">
                    
                    {{-- Profile Picture Section --}}
                    <div>
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[var(--rule)]">
                            <span class="w-8 h-8 rounded-xl bg-[var(--accent)]/15 text-[var(--accent)] font-serif font-bold inline-flex items-center justify-center text-sm">1</span>
                            <div>
                                <h2 class="text-xl font-serif font-bold text-[var(--ink)]">Personal &amp; Contact Details</h2>
                                <p class="text-xs text-[var(--support)]">Faculty member background and communication lines</p>
                            </div>
                        </div>

                        <div class="mb-8 p-5 bg-[var(--paper)] rounded-2xl border border-[var(--rule)]">
                            <label class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-3">Profile Picture (Optional)</label>
                            <div class="flex items-center space-x-6">
                                <div class="relative flex-shrink-0">
                                    @if ($profile_picture)
                                        <img src="{{ $profile_picture->temporaryUrl() }}" class="h-20 w-20 rounded-2xl object-cover border-2 border-[var(--accent)] shadow-md">
                                    @else
                                        <div class="h-20 w-20 rounded-2xl bg-white border-2 border-dashed border-[var(--rule)] flex items-center justify-center text-[var(--support)] shadow-sm">
                                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <input type="file" wire:model="profile_picture" id="profile_picture" class="hidden" accept="image/*">
                                    <label for="profile_picture" class="cursor-pointer inline-flex items-center px-4 py-2.5 border border-[var(--rule)] shadow-sm text-xs font-bold uppercase tracking-wider rounded-xl text-[var(--ink)] bg-white hover:bg-[var(--paper)] transition">
                                        <svg class="w-4 h-4 mr-2 text-[var(--accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                        </svg>
                                        Upload Photo
                                    </label>
                                    <p class="text-xs text-[var(--support)] mt-1.5">PNG, JPG up to 2MB</p>
                                    @error('profile_picture') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Personal Information Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            
                            {{-- Full Name --}}
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="name" id="name" placeholder="e.g. John Doe" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('name') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Email Address --}}
                            <div>
                                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" wire:model.defer="email" id="email" placeholder="teacher@example.com" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('email') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Phone Number --}}
                            <div>
                                <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Phone Number <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="phone" id="phone" placeholder="08012345678" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('phone') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Gender --}}
                            <div>
                                <label for="gender" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Gender <span class="text-red-500">*</span></label>
                                <select wire:model.defer="gender" id="gender" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('gender') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Date of Birth --}}
                            <div>
                                <label for="dob" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Date of Birth <span class="text-red-500">*</span></label>
                                <input type="date" wire:model.defer="dob" id="dob" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('dob') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Address --}}
                            <div class="sm:col-span-2">
                                <label for="address" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Home Address <span class="text-red-500">*</span></label>
                                <textarea wire:model.defer="address" id="address" rows="2" placeholder="Street address, City..." class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition"></textarea>
                                @error('address') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Account Password --}}
                    <div>
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[var(--rule)]">
                            <span class="w-8 h-8 rounded-xl bg-[var(--accent)]/15 text-[var(--accent)] font-serif font-bold inline-flex items-center justify-center text-sm">2</span>
                            <div>
                                <h2 class="text-xl font-serif font-bold text-[var(--ink)]">Faculty Portal Credentials</h2>
                                <p class="text-xs text-[var(--support)]">Secure login authentication password</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {{-- Password --}}
                            <div>
                                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Portal Password <span class="text-red-500">*</span></label>
                                <input type="password" wire:model.defer="password" id="password" placeholder="••••••••" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('password') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Password Confirmation --}}
                            <div>
                                <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Confirm Password <span class="text-red-500">*</span></label>
                                <input type="password" wire:model.defer="password_confirmation" id="password_confirmation" placeholder="••••••••" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('password_confirmation') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <div class="pt-4 border-t border-[var(--rule)]">
                        <button type="submit" 
                                wire:loading.attr="disabled" 
                                class="w-full inline-flex justify-center items-center py-4 px-8 border border-transparent rounded-xl shadow-lg text-sm font-bold uppercase tracking-wider text-white bg-[var(--ink)] hover:bg-[var(--accent)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--accent)] transition duration-200 disabled:opacity-50">
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

