@php
    $contactEyebrow = \App\Services\FrontendLibrary::get('contact_eyebrow', 'CAMPUS REGISTRY & VISITATION');
    $contactHeading = \App\Services\FrontendLibrary::get('contact_heading', 'Connect with Our Admissions Registry');
    $contactPhone = \App\Services\FrontendLibrary::getSetting('school_phone', '+234 803 300 4567');
    $contactEmail = \App\Services\FrontendLibrary::getSetting('school_email', 'admissions@cathedralcollege.edu.ng');
    $contactAddress = \App\Services\FrontendLibrary::getSetting('school_address', 'Cathedral Grounds, Ejinrin Road, Ijebu-Ode, Ogun State, Nigeria');
    $schoolName = \App\Services\FrontendLibrary::getSetting('school_name', 'Cathedral College');
@endphp

<!-- ====== Modern Theme Contact Section ====== -->
<section id="contact" class="py-16 sm:py-24 bg-white relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-4 mb-12 sm:mb-16">
            <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-accent/15 text-accent text-xs font-sans font-bold uppercase tracking-wider">
                <span>{{ $contactEyebrow }}</span>
            </div>
            <h2 class="font-sans font-extrabold text-ink text-2xl sm:text-3xl lg:text-4xl tracking-tight leading-tight">
                {{ $contactHeading }}
            </h2>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-12 items-start">
            
            <!-- Left Column: Modern Campus Details (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                
                <!-- Address Card -->
                <div class="p-6 rounded-3xl bg-slate-50 border border-slate-100 flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-accent/15 text-accent flex items-center justify-center text-xl shrink-0">
                        📍
                    </div>
                    <div class="space-y-1">
                        <div class="font-sans font-bold text-sm text-ink uppercase tracking-wide">Campus Address</div>
                        <p class="font-sans text-xs sm:text-sm text-slate-600 leading-relaxed">
                            {{ $contactAddress }}
                        </p>
                    </div>
                </div>

                <!-- Phone Card -->
                <div class="p-6 rounded-3xl bg-slate-50 border border-slate-100 flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-accent/15 text-accent flex items-center justify-center text-xl shrink-0">
                        📞
                    </div>
                    <div class="space-y-1">
                        <div class="font-sans font-bold text-sm text-ink uppercase tracking-wide">Registry Telephone</div>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contactPhone) }}" class="font-sans text-sm font-semibold text-accent hover:underline block">
                            {{ $contactPhone }}
                        </a>
                        <div class="text-[11px] text-slate-400 font-sans">Monday – Friday: 8:00 AM – 4:00 PM</div>
                    </div>
                </div>

                <!-- Email Card -->
                <div class="p-6 rounded-3xl bg-slate-50 border border-slate-100 flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-accent/15 text-accent flex items-center justify-center text-xl shrink-0">
                        ✉️
                    </div>
                    <div class="space-y-1">
                        <div class="font-sans font-bold text-sm text-ink uppercase tracking-wide">Admissions Email</div>
                        <a href="mailto:{{ $contactEmail }}" class="font-sans text-sm font-semibold text-accent hover:underline block break-all">
                            {{ $contactEmail }}
                        </a>
                        <div class="text-[11px] text-slate-400 font-sans">Official response within 24 business hours</div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Interactive Inquiry Form (7 cols) -->
            <div class="lg:col-span-7 bg-slate-50 p-8 sm:p-10 rounded-3xl border border-slate-100 shadow-sm"
                 x-data="{ submitted: false, loading: false }">
                
                <h3 class="font-sans font-bold text-xl text-ink mb-2">Send an Admissions Inquiry</h3>
                <p class="text-xs sm:text-sm text-slate-500 font-sans mb-6">
                    Fill out the form below and our Admissions Registry will contact you promptly.
                </p>

                <div x-show="submitted" class="p-6 rounded-2xl bg-green-50 border border-green-200 text-green-800 text-center space-y-2">
                    <div class="text-2xl">✅</div>
                    <div class="font-sans font-bold text-base">Inquiry Submitted Successfully!</div>
                    <p class="text-xs font-sans text-green-700">Thank you for reaching out to {{ $schoolName }}. Our Admissions Officer will contact you shortly.</p>
                </div>

                <form x-show="!submitted" @submit.prevent="loading = true; setTimeout(() => { loading = false; submitted = true; }, 800)" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-sans font-semibold text-slate-700 mb-1.5">Parent / Guardian Name *</label>
                            <input type="text" required placeholder="e.g. Mr. & Mrs. Adeleke"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-accent focus:border-accent text-sm font-sans bg-white shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-sans font-semibold text-slate-700 mb-1.5">Phone Number *</label>
                            <input type="tel" required placeholder="e.g. +234 803 000 0000"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-accent focus:border-accent text-sm font-sans bg-white shadow-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-sans font-semibold text-slate-700 mb-1.5">Email Address *</label>
                            <input type="email" required placeholder="name@domain.com"
                                   class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-accent focus:border-accent text-sm font-sans bg-white shadow-sm" />
                        </div>
                        <div>
                            <label class="block text-xs font-sans font-semibold text-slate-700 mb-1.5">Class of Interest *</label>
                            <select required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-accent focus:border-accent text-sm font-sans bg-white shadow-sm">
                                <option value="">Select Class / Stage</option>
                                <option>Early Years (Crèche / Nursery)</option>
                                <option>Primary School (Years 1 - 6)</option>
                                <option>Junior Secondary (JS1 - JS3)</option>
                                <option>Senior Secondary (SS1 - SS3)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-sans font-semibold text-slate-700 mb-1.5">Message / Inquiry Details</label>
                        <textarea rows="3" placeholder="Tell us any specific questions regarding admission, boarding, or curriculum..."
                                  class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:ring-2 focus:ring-accent focus:border-accent text-sm font-sans bg-white shadow-sm"></textarea>
                    </div>

                    <button type="submit"
                            :disabled="loading"
                            class="w-full py-4 rounded-xl font-sans font-bold text-sm bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition-all shadow-md flex items-center justify-center gap-2">
                        <span x-show="!loading">Submit Inquiry to Registry</span>
                        <span x-show="loading">Submitting...</span>
                    </button>
                </form>

            </div>

        </div>

    </div>
</section>
