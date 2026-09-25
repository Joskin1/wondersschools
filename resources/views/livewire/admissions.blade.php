@php
    $tenantName = function_exists('tenant') && tenant('name') ? tenant('name') : null;
    $defaultSchoolName = $tenantName ?? config('app.name', 'Wonders Kiddies Foundation Schools');
@endphp

<div>
    <!-- ====== 01 — Subpage Header Banner ====== -->
    <section class="relative bg-ink text-paper py-20 sm:py-28 overflow-hidden border-b border-rule">
        <div class="absolute inset-0 z-0 opacity-15">
            <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?auto=format&fit=crop&w=1920&q=80"
                 alt="Admissions Cover"
                 class="w-full h-full object-cover object-center" />
            <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/90 to-ink/70"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl space-y-4">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-[1px] bg-accent"></span>
                    <span class="text-xs uppercase tracking-[0.25em] text-accent font-sans font-semibold">
                        Enrollment 2026 / 2027 Academic Session
                    </span>
                </div>
                <h1 class="font-serif font-semibold text-white tracking-tight leading-[1.1]" style="font-size: clamp(2.5rem, 5vw, 4rem);">
                    Admissions
                </h1>
                <p class="text-base sm:text-lg text-paper/80 font-sans leading-relaxed max-w-[62ch]">
                    Start your child's journey with excellence, character, and purpose.
                </p>
            </div>
        </div>
    </section>

    <!-- ====== 02 — 3-Step Admissions Protocol ====== -->
    <section class="py-20 sm:py-28 bg-paper">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <div class="flex items-center gap-3">
                    <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
                        01 &mdash; ENROLLMENT PROTOCOL
                    </span>
                    <span class="flex-grow h-[1px] bg-rule"></span>
                </div>
            </div>

            <div class="mb-12 max-w-2xl">
                <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
                    Our Admission Process
                </h2>
                <p class="mt-3 text-sm sm:text-base text-body/80 font-sans leading-relaxed">
                    We’ve designed a simple and transparent process to make enrollment smooth and stress-free.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="border border-rule bg-white p-8 space-y-4 hover:border-ink transition relative">
                    <span class="font-serif text-2xl font-bold text-accent block">01</span>
                    <h3 class="font-serif text-xl font-semibold text-ink">
                        Make an Inquiry
                    </h3>
                    <p class="text-sm text-body/80 font-sans leading-relaxed">
                        Complete the inquiry form below or visit our campus to obtain an application form and speak with our admissions team.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="border border-rule bg-white p-8 space-y-4 hover:border-ink transition relative">
                    <span class="font-serif text-2xl font-bold text-accent block">02</span>
                    <h3 class="font-serif text-xl font-semibold text-ink">
                        Child Assessment
                    </h3>
                    <p class="text-sm text-body/80 font-sans leading-relaxed">
                        Your child will participate in a brief assessment to help us determine the appropriate class placement and support needs.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="border border-rule bg-white p-8 space-y-4 hover:border-ink transition relative">
                    <span class="font-serif text-2xl font-bold text-accent block">03</span>
                    <h3 class="font-serif text-xl font-semibold text-ink">
                        Complete Enrollment
                    </h3>
                    <p class="text-sm text-body/80 font-sans leading-relaxed">
                        Finalize documentation and payment, and officially join the WKFS learning community.
                    </p>
                </div>
            </div>

            <!-- Fast-Track Registration Action Box -->
            <div class="mt-12 p-6 sm:p-8 bg-ink text-paper flex flex-col sm:flex-row items-center justify-between gap-6 border border-accent/30">
                <div>
                    <span class="text-xs uppercase tracking-widest text-accent font-sans font-bold block">Direct Registration Available</span>
                    <h4 class="font-serif text-xl font-semibold text-white mt-1">Ready to complete the full application form?</h4>
                </div>
                <a href="{{ route('public.student.register') }}"
                   class="px-8 py-3.5 bg-accent text-[color:var(--accent-contrast)] text-xs font-sans uppercase tracking-widest font-bold hover:bg-accent-hover transition flex-shrink-0">
                    Register Student Online &rarr;
                </a>
            </div>
        </div>
    </section>

    <!-- ====== 03 — School Fees & Schedule ====== -->
    <section class="py-20 sm:py-28 bg-white border-t border-b border-rule">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12">
                <div class="flex items-center gap-3">
                    <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
                        02 &mdash; TUITION & VALUE
                    </span>
                    <span class="flex-grow h-[1px] bg-rule"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-start">
                <div class="lg:col-span-5 space-y-4">
                    <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
                        School Fees
                    </h2>
                    <p class="text-sm sm:text-base text-body/85 font-sans leading-relaxed">
                        Our fees are structured to provide excellent value while maintaining high academic and developmental standards.
                    </p>
                    <div class="pt-4">
                        @if(\App\Models\Setting::where('key', 'fee_schedule_link')->value('value'))
                            <a href="{{ \App\Models\Setting::where('key', 'fee_schedule_link')->value('value') }}" 
                               target="_blank" 
                               class="inline-flex items-center px-6 py-3 border border-ink text-xs uppercase tracking-widest font-sans font-semibold text-ink hover:bg-ink hover:text-white transition">
                                Download Full Fee Schedule (PDF)
                            </a>
                        @endif
                    </div>
                </div>

                <div class="lg:col-span-7">
                    <div class="border border-rule bg-paper overflow-hidden">
                        <div class="px-6 py-4 border-b border-rule bg-white flex items-center justify-between">
                            <h3 class="font-serif text-lg font-semibold text-ink">
                                Current Term Fee Categories
                            </h3>
                            <span class="text-xs font-sans text-accent font-bold uppercase tracking-wider">Academic Session 2026/2027</span>
                        </div>
                        <div class="divide-y divide-rule font-sans text-sm">
                            <div class="px-6 py-4 flex justify-between items-center hover:bg-white/60 transition">
                                <span class="font-medium text-ink">Creche / Playgroup</span>
                                <span class="text-body/70 text-xs uppercase tracking-wider font-semibold">Contact Admissions</span>
                            </div>
                            <div class="px-6 py-4 flex justify-between items-center hover:bg-white/60 transition">
                                <span class="font-medium text-ink">Nursery</span>
                                <span class="text-body/70 text-xs uppercase tracking-wider font-semibold">Contact Admissions</span>
                            </div>
                            <div class="px-6 py-4 flex justify-between items-center hover:bg-white/60 transition">
                                <span class="font-medium text-ink">Primary</span>
                                <span class="text-body/70 text-xs uppercase tracking-wider font-semibold">Contact Admissions</span>
                            </div>
                            <div class="px-6 py-4 flex justify-between items-center hover:bg-white/60 transition">
                                <span class="font-medium text-ink">Junior Secondary (JSS 1 &mdash; 3)</span>
                                <span class="text-body/70 text-xs uppercase tracking-wider font-semibold">Contact Admissions</span>
                            </div>
                            <div class="px-6 py-4 flex justify-between items-center hover:bg-white/60 transition">
                                <span class="font-medium text-ink">Senior Secondary (SSS 1 &mdash; 3)</span>
                                <span class="text-body/70 text-xs uppercase tracking-wider font-semibold">Contact Admissions</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== 04 — Interactive Admissions Inquiry Form ====== -->
    <section class="py-20 sm:py-28 bg-paper">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-12 text-center max-w-2xl mx-auto">
                <span class="text-xs uppercase tracking-[0.25em] text-accent font-sans font-bold block mb-2">Direct Inquiry</span>
                <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
                    Admission Inquiry Form
                </h2>
                <p class="mt-3 text-sm text-body/80 font-sans leading-relaxed">
                    Complete the form below and our admissions team will contact you shortly.
                </p>
            </div>

            @if (session()->has('message'))
                <div class="mb-8 border border-green-300 bg-green-50 p-6 text-center space-y-2">
                    <span class="font-serif text-lg font-bold text-green-900 block">Inquiry Registered</span>
                    <p class="text-sm font-medium text-green-800 font-sans">
                        {{ session('message') }}
                    </p>
                </div>
            @endif

            <div class="border border-rule bg-white p-8 sm:p-12 shadow-sm">
                <form wire:submit.prevent="submit" class="space-y-6 font-sans">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs uppercase tracking-wider font-bold text-ink mb-2">
                                Parent's Full Name *
                            </label>
                            <input type="text" wire:model="name"
                                   placeholder="e.g. Dr. & Mrs. Adeleke"
                                   class="py-3 px-4 block w-full text-sm border-rule bg-paper focus:bg-white focus:ring-1 focus:ring-accent focus:border-accent rounded-none transition">
                            @error('name') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs uppercase tracking-wider font-bold text-ink mb-2">
                                Email Address *
                            </label>
                            <input type="email" wire:model="email"
                                   placeholder="parent@example.com"
                                   class="py-3 px-4 block w-full text-sm border-rule bg-paper focus:bg-white focus:ring-1 focus:ring-accent focus:border-accent rounded-none transition">
                            @error('email') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs uppercase tracking-wider font-bold text-ink mb-2">
                                Phone Number *
                            </label>
                            <input type="text" wire:model="phone"
                                   placeholder="+234 800 000 0000"
                                   class="py-3 px-4 block w-full text-sm border-rule bg-paper focus:bg-white focus:ring-1 focus:ring-accent focus:border-accent rounded-none transition">
                            @error('phone') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs uppercase tracking-wider font-bold text-ink mb-2">
                                Child’s Age / Intended Class *
                            </label>
                            <input type="text" wire:model="child_age"
                                   placeholder="e.g. 10 Years / JSS 1"
                                   class="py-3 px-4 block w-full text-sm border-rule bg-paper focus:bg-white focus:ring-1 focus:ring-accent focus:border-accent rounded-none transition">
                            @error('child_age') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs uppercase tracking-wider font-bold text-ink mb-2">
                            Additional Information (Optional)
                        </label>
                        <textarea wire:model="message" rows="4"
                                  placeholder="Any special inquiries, boarder / day preference, or candidate details..."
                                  class="py-3 px-4 block w-full text-sm border-rule bg-paper focus:bg-white focus:ring-1 focus:ring-accent focus:border-accent rounded-none transition"></textarea>
                        @error('message') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit"
                                class="w-full py-4 px-6 text-xs uppercase tracking-widest font-sans font-bold bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition rounded-none">
                            Submit Admission Inquiry &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>