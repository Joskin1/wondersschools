<div class="min-h-screen py-12 sm:py-16 px-4 sm:px-6 lg:px-8 bg-[var(--paper)]">
    <div class="max-w-3xl mx-auto w-full">
        
        {{-- Header Card --}}
        <div class="text-center mb-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold tracking-wider uppercase bg-[var(--accent)]/10 text-[var(--accent)] mb-4 border border-[var(--accent)]/20">
                <span class="w-1.5 h-1.5 rounded-full bg-[var(--accent)]"></span>
                Official Admissions Protocol
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-serif font-black text-[var(--ink)] tracking-tight">
                Student Online Registration
            </h1>
            <p class="mt-3 text-base sm:text-lg text-[var(--support)] max-w-xl mx-auto">
                Enroll a student at <span class="font-bold text-[var(--ink)]">{{ $appName }}</span>. Fill out the application form below.
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
                        <h4 class="text-sm font-bold text-red-900">Application Interrupted</h4>
                        <p class="text-sm text-red-700 mt-0.5">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if ($submitted)
            {{-- Success Completion Card --}}
            <div class="bg-white rounded-3xl shadow-xl p-8 sm:p-12 text-center border border-[var(--rule)]">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-2xl bg-emerald-100 text-emerald-700 mb-6 shadow-inner">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                
                <span class="text-xs font-bold uppercase tracking-wider text-[var(--accent)]">Application Confirmed</span>
                <h2 class="text-2xl sm:text-3xl font-serif font-bold text-[var(--ink)] mt-1 mb-2">Student Registration Received!</h2>
                <p class="text-sm sm:text-base text-[var(--body)] max-w-md mx-auto mb-8 leading-relaxed">
                    Thank you! The student application for <span class="font-bold text-[var(--ink)]">{{ $full_name }}</span> has been registered successfully.
                </p>

                <div class="bg-[var(--paper)] border border-[var(--rule)] rounded-2xl p-6 sm:p-7 mb-8 text-left max-w-lg mx-auto space-y-4">
                    <div class="flex justify-between items-center pb-3 border-b border-[var(--rule)]">
                        <span class="text-xs font-bold uppercase tracking-wider text-[var(--support)]">Admission Number</span>
                        <span class="text-sm font-mono font-bold text-[var(--ink)] bg-white px-3 py-1 rounded-lg border border-[var(--rule)] shadow-sm">{{ $generatedAdmissionNumber }}</span>
                    </div>
                    <div class="flex justify-between items-center pb-3 border-b border-[var(--rule)]">
                        <span class="text-xs font-bold uppercase tracking-wider text-[var(--support)]">System Internal Email</span>
                        <span class="text-sm font-mono text-[var(--ink)]">{{ $generatedEmail }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold uppercase tracking-wider text-[var(--support)]">Parent / Guardian</span>
                        <span class="text-sm font-medium text-[var(--ink)]">{{ $parent_name }} ({{ $parent_phone }})</span>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-8 text-left max-w-lg mx-auto">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 text-amber-600 mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-amber-900">Portal Access Verification</h3>
                            <p class="text-xs sm:text-sm text-amber-800 mt-1 leading-relaxed">
                                The student portal account is currently pending administrator activation. Once the school administration reviews the record and activates the portal, the student will be able to log in using their admission details.
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
            {{-- Registration Form Card --}}
            <div class="bg-white rounded-3xl shadow-xl border border-[var(--rule)] overflow-hidden">
                <form wire:submit.prevent="submit" class="p-6 sm:p-10 space-y-10">
                    
                    {{-- Section 1: Student Details --}}
                    <div>
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[var(--rule)]">
                            <span class="w-8 h-8 rounded-xl bg-[var(--accent)]/15 text-[var(--accent)] font-serif font-bold inline-flex items-center justify-center text-sm">1</span>
                            <div>
                                <h2 class="text-xl font-serif font-bold text-[var(--ink)]">Student Details</h2>
                                <p class="text-xs text-[var(--support)]">Scholastic candidate identification and grade placement</p>
                            </div>
                        </div>
                        
                        {{-- Photo Upload --}}
                        <div class="mb-8 p-5 bg-[var(--paper)] rounded-2xl border border-[var(--rule)]">
                            <label class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-3">Passport Photograph (Optional)</label>
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
                                        Upload Student Photo
                                    </label>
                                    <p class="text-xs text-[var(--support)] mt-1.5">PNG, JPG up to 2MB</p>
                                    @error('profile_picture') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {{-- Full Name --}}
                            <div class="sm:col-span-2">
                                <label for="full_name" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Student Full Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="full_name" id="full_name" placeholder="First Name Middle Name Last Name" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('full_name') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Classroom / Grade --}}
                            <div>
                                <label for="classroom_id" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Classroom / Grade <span class="text-red-500">*</span></label>
                                <select wire:model.defer="classroom_id" id="classroom_id" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                    <option value="">-- Select Classroom --</option>
                                    @foreach($classrooms as $classroom)
                                        <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                                    @endforeach
                                </select>
                                @error('classroom_id') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Gender --}}
                            <div>
                                <label for="gender" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Gender <span class="text-red-500">*</span></label>
                                <select wire:model.defer="gender" id="gender" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                @error('gender') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Date of Birth --}}
                            <div>
                                <label for="date_of_birth" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Date of Birth <span class="text-red-500">*</span></label>
                                <input type="date" wire:model.defer="date_of_birth" id="date_of_birth" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('date_of_birth') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Previous School --}}
                            <div>
                                <label for="previous_school" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Previous School Attended (Optional)</label>
                                <input type="text" wire:model.defer="previous_school" id="previous_school" placeholder="e.g. St. Paul Nursery School" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('previous_school') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Address --}}
                            <div class="sm:col-span-2">
                                <label for="address" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Home Address <span class="text-red-500">*</span></label>
                                <textarea wire:model.defer="address" id="address" rows="2" placeholder="Street Address, City..." class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition"></textarea>
                                @error('address') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Parent / Guardian Details --}}
                    <div>
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[var(--rule)]">
                            <span class="w-8 h-8 rounded-xl bg-[var(--accent)]/15 text-[var(--accent)] font-serif font-bold inline-flex items-center justify-center text-sm">2</span>
                            <div>
                                <h2 class="text-xl font-serif font-bold text-[var(--ink)]">Parent / Guardian Contact</h2>
                                <p class="text-xs text-[var(--support)]">Primary familial contact and correspondence details</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {{-- Parent Name --}}
                            <div class="sm:col-span-2">
                                <label for="parent_name" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Parent / Guardian Full Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="parent_name" id="parent_name" placeholder="Mr. & Mrs. John Doe" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('parent_name') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Parent Phone --}}
                            <div>
                                <label for="parent_phone" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Parent Phone Number <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="parent_phone" id="parent_phone" placeholder="08012345678" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('parent_phone') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>

                            {{-- Parent Email --}}
                            <div>
                                <label for="parent_email" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Parent Email Address (Optional)</label>
                                <input type="email" wire:model.defer="parent_email" id="parent_email" placeholder="parent@example.com" class="block w-full rounded-xl border border-[var(--rule)] bg-[var(--paper)] shadow-sm focus:border-transparent focus:ring-2 focus:ring-[var(--accent)] text-sm py-3.5 px-4 text-[var(--ink)] transition">
                                @error('parent_email') <span class="text-xs text-red-600 mt-1 block font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Section 3: Student Portal Account Password --}}
                    <div>
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[var(--rule)]">
                            <span class="w-8 h-8 rounded-xl bg-[var(--accent)]/15 text-[var(--accent)] font-serif font-bold inline-flex items-center justify-center text-sm">3</span>
                            <div>
                                <h2 class="text-xl font-serif font-bold text-[var(--ink)]">Student Portal Password</h2>
                                <p class="text-xs text-[var(--support)]">Set an access password for future portal logins</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            {{-- Password --}}
                            <div>
                                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">Create Password <span class="text-red-500">*</span></label>
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
                            <span>Submit Student Registration</span>
                        </button>
                    </div>

                </form>
            </div>
        @endif

    </div>
</div>

