<div>
    <!-- Editorial Page Header -->
    <div class="relative overflow-hidden py-20 lg:py-24 border-b border-[var(--rule)] bg-[var(--paper)]">
        <div class="absolute inset-0 opacity-[0.03] pointer-events-none" style="background-image: radial-gradient(var(--ink) 1px, transparent 1px); background-size: 24px 24px;"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold tracking-wider uppercase bg-[var(--accent)]/10 text-[var(--accent)] mb-4 border border-[var(--accent)]/20">
                <span class="w-1.5 h-1.5 rounded-full bg-[var(--accent)]"></span>
                Admissions &amp; Campus Registry
            </div>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-serif font-black tracking-tight text-[var(--ink)] leading-tight">
                Contact Us
            </h1>
            <p class="mt-4 text-lg sm:text-xl font-medium text-[var(--support)] max-w-2xl mx-auto leading-relaxed">
                We'd love to hear from you.
            </p>
        </div>
    </div>

    <!-- Contact Info & Form Section -->
    <div class="py-16 sm:py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-start">
                
                <!-- Left Column: Contact Information & Map -->
                <div class="lg:col-span-5 space-y-8">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-[var(--accent)]">Direct Inquiries</span>
                        <h2 class="mt-2 text-3xl font-serif font-bold text-[var(--ink)]">Get in Touch</h2>
                        <p class="mt-3 text-base text-[var(--body)] leading-relaxed">
                            Whether you have a question about admissions, academics, or campus tours, our administrative team is ready to assist you.
                        </p>
                    </div>

                    <!-- Contact Details Cards -->
                    <div class="space-y-4">
                        <!-- Campus Address -->
                        <div class="flex items-start gap-4 p-5 rounded-2xl bg-[var(--paper)] border border-[var(--rule)]">
                            <div class="w-10 h-10 rounded-xl bg-[var(--accent)]/10 text-[var(--accent)] flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[var(--support)]">Campus Address</h4>
                                <p class="mt-1 text-sm font-medium text-[var(--ink)]">
                                    {{ \App\Models\Setting::where('key', 'school_address')->value('value') ?? '123 School Lane, City, Country' }}
                                </p>
                            </div>
                        </div>

                        <!-- Phone Lines -->
                        <div class="flex items-start gap-4 p-5 rounded-2xl bg-[var(--paper)] border border-[var(--rule)]">
                            <div class="w-10 h-10 rounded-xl bg-[var(--accent)]/10 text-[var(--accent)] flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[var(--support)]">Telephone Lines</h4>
                                <p class="mt-1 text-sm font-medium text-[var(--ink)]">
                                    {{ \App\Models\Setting::where('key', 'school_phone')->value('value') ?? '+123 456 7890' }}
                                </p>
                                <p class="text-xs text-[var(--support)] mt-0.5">Mon – Fri, 7:30 AM – 4:30 PM WAT</p>
                            </div>
                        </div>

                        <!-- Email Desk -->
                        <div class="flex items-start gap-4 p-5 rounded-2xl bg-[var(--paper)] border border-[var(--rule)]">
                            <div class="w-10 h-10 rounded-xl bg-[var(--accent)]/10 text-[var(--accent)] flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-[var(--support)]">Electronic Mail</h4>
                                <p class="mt-1 text-sm font-medium text-[var(--ink)]">
                                    {{ \App\Models\Setting::where('key', 'school_email')->value('value') ?? 'info@wkfs.com' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Campus Location Map Frame -->
                    <div class="rounded-2xl overflow-hidden border border-[var(--rule)] shadow-sm bg-gray-100">
                        <div class="p-3 bg-[var(--paper)] border-b border-[var(--rule)] flex items-center justify-between text-xs font-bold text-[var(--support)]">
                            <span>Campus Coordinates</span>
                            <span class="inline-flex items-center gap-1 text-[var(--accent)]">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Live Location
                            </span>
                        </div>
                        <div class="h-64 sm:h-72 w-full">
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3963.952912260219!2d3.375295414770757!3d6.527638695278928!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x103b8b2ae68280c1%3A0xdc9e87a367c3d9cb!2sLagos!5e0!3m2!1sen!2sng!4v1622212345678!5m2!1sen!2sng" 
                                width="100%" 
                                height="100%" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Interactive Inquiry Form -->
                <div class="lg:col-span-7">
                    <div class="bg-[var(--paper)] p-8 sm:p-10 rounded-3xl border border-[var(--rule)] shadow-sm">
                        <div class="mb-8">
                            <h2 class="text-2xl sm:text-3xl font-serif font-bold text-[var(--ink)]">Send a Message</h2>
                            <p class="mt-2 text-sm text-[var(--body)]">
                                Complete this dispatch form and our admissions desk will respond within one business day.
                            </p>
                        </div>
                        
                        @if (session()->has('message'))
                            <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5 mb-8">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 text-emerald-600 mt-0.5">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-emerald-900">Message Transmitted</h4>
                                        <p class="text-sm text-emerald-800 mt-1">
                                            {{ session('message') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <form wire:submit.prevent="submit" class="space-y-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">
                                    Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       wire:model="name" 
                                       id="name" 
                                       placeholder="e.g. Dr. Adeyemi Adeleke"
                                       class="w-full rounded-xl border border-[var(--rule)] bg-white px-4 py-3.5 text-sm text-[var(--ink)] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[var(--accent)] focus:border-transparent transition-all shadow-sm">
                                @error('name') 
                                    <span class="text-red-600 text-xs font-medium mt-1.5 block flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </span> 
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" 
                                       wire:model="email" 
                                       id="email" 
                                       placeholder="e.g. adeyemi@example.com"
                                       class="w-full rounded-xl border border-[var(--rule)] bg-white px-4 py-3.5 text-sm text-[var(--ink)] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[var(--accent)] focus:border-transparent transition-all shadow-sm">
                                @error('email') 
                                    <span class="text-red-600 text-xs font-medium mt-1.5 block flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </span> 
                                @enderror
                            </div>

                            <!-- Message -->
                            <div>
                                <label for="message" class="block text-xs font-bold uppercase tracking-wider text-[var(--ink)] mb-2">
                                    Message <span class="text-red-500">*</span>
                                </label>
                                <textarea wire:model="message" 
                                          id="message" 
                                          rows="5" 
                                          placeholder="Please write your questions regarding admissions, curriculum, tuition, or scheduling a campus visit..."
                                          class="w-full rounded-xl border border-[var(--rule)] bg-white px-4 py-3.5 text-sm text-[var(--ink)] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[var(--accent)] focus:border-transparent transition-all shadow-sm"></textarea>
                                @error('message') 
                                    <span class="text-red-600 text-xs font-medium mt-1.5 block flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ $message }}
                                    </span> 
                                @enderror
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-2">
                                <button type="submit" 
                                        wire:loading.attr="disabled"
                                        class="w-full inline-flex items-center justify-center gap-2 py-4 px-8 border border-transparent rounded-xl shadow-md text-sm font-bold uppercase tracking-wider text-white bg-[var(--ink)] hover:bg-[var(--accent)] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[var(--accent)] transition-all duration-200 disabled:opacity-50">
                                    <svg wire:loading class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Send Message</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

