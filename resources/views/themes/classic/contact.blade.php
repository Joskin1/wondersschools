@php
    $contactEyebrow = \App\Services\FrontendLibrary::get('contact_eyebrow', 'CAMPUS REGISTRY & VISITATION');
    $contactHeading = \App\Services\FrontendLibrary::get('contact_heading', 'Campus Registry & Admissions Inquiry');
    $contactPhone = \App\Services\FrontendLibrary::getSetting('school_phone', '+234 803 300 4567');
    $contactEmail = \App\Services\FrontendLibrary::getSetting('school_email', 'admissions@cathedralcollege.edu.ng');
    $contactAddress = \App\Services\FrontendLibrary::getSetting('school_address', 'Cathedral Grounds, Ejinrin Road, Ijebu-Ode, Ogun State, Nigeria');
    $schoolName = \App\Services\FrontendLibrary::getSetting('school_name', 'Cathedral College');
@endphp

<!-- ====== Classic Theme Contact Section ====== -->
<section id="contact" class="py-16 sm:py-24 bg-white relative">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center max-w-3xl mx-auto space-y-3 mb-12 sm:mb-16">
            <div class="text-xs font-sans uppercase tracking-[0.25em] text-accent font-bold">
                {{ $contactEyebrow }}
            </div>
            <h2 class="font-serif font-normal text-ink text-2xl sm:text-3xl lg:text-4xl leading-tight">
                {{ $contactHeading }}
            </h2>
            <div class="flex items-center justify-center gap-3 pt-1">
                <span class="w-10 h-[1px] bg-accent/40"></span>
                <span class="text-accent text-xs">◆</span>
                <span class="w-10 h-[1px] bg-accent/40"></span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
            
            <!-- Left: Formal Registry Details (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="p-8 border-2 border-accent/30 bg-paper shadow-sm space-y-6">
                    <div>
                        <div class="font-serif font-bold text-base text-ink mb-1">Campus Physical Address</div>
                        <p class="font-sans text-xs text-body/80 leading-relaxed">{{ $contactAddress }}</p>
                    </div>

                    <div class="pt-4 border-t border-rule">
                        <div class="font-serif font-bold text-base text-ink mb-1">Admissions Telephone</div>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contactPhone) }}" class="font-sans text-sm font-semibold text-accent hover:underline block">
                            {{ $contactPhone }}
                        </a>
                        <div class="text-[11px] text-slate-500 font-sans mt-0.5">Office Hours: Mon – Fri (8:00 AM – 4:00 PM)</div>
                    </div>

                    <div class="pt-4 border-t border-rule">
                        <div class="font-serif font-bold text-base text-ink mb-1">Electronic Correspondence</div>
                        <a href="mailto:{{ $contactEmail }}" class="font-sans text-xs sm:text-sm font-semibold text-accent hover:underline block break-all">
                            {{ $contactEmail }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right: Formal Inquiry Form (7 cols) -->
            <div class="lg:col-span-7 p-8 border border-rule bg-paper shadow-sm"
                 x-data="{ submitted: false, loading: false }">
                
                <h3 class="font-serif font-semibold text-lg text-ink mb-1">Direct Admissions Dispatch</h3>
                <p class="text-xs font-sans text-slate-500 mb-6">
                    Submit an inquiry to the Academic Registrar.
                </p>

                <div x-show="submitted" class="p-6 border border-accent bg-accent/10 text-ink text-center space-y-2">
                    <div class="font-serif font-bold text-base">Inquiry Dispatched Successfully</div>
                    <p class="text-xs font-sans text-body/80">Thank you for contacting {{ $schoolName }}. The Registry will respond within one working day.</p>
                </div>

                <form x-show="!submitted" @submit.prevent="loading = true; setTimeout(() => { loading = false; submitted = true; }, 800)" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] uppercase tracking-wider font-sans font-bold text-ink mb-1">Guardian Full Name *</label>
                            <input type="text" required placeholder="e.g. Mr. & Mrs. Adeleke"
                                   class="w-full px-3.5 py-2.5 border border-rule focus:border-accent text-xs font-sans bg-white shadow-none rounded-none" />
                        </div>
                        <div>
                            <label class="block text-[11px] uppercase tracking-wider font-sans font-bold text-ink mb-1">Telephone Number *</label>
                            <input type="tel" required placeholder="e.g. +234 803 000 0000"
                                   class="w-full px-3.5 py-2.5 border border-rule focus:border-accent text-xs font-sans bg-white shadow-none rounded-none" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] uppercase tracking-wider font-sans font-bold text-ink mb-1">Email Address *</label>
                            <input type="email" required placeholder="name@domain.com"
                                   class="w-full px-3.5 py-2.5 border border-rule focus:border-accent text-xs font-sans bg-white shadow-none rounded-none" />
                        </div>
                        <div>
                            <label class="block text-[11px] uppercase tracking-wider font-sans font-bold text-ink mb-1">Proposed Academic Stage *</label>
                            <select required class="w-full px-3.5 py-2.5 border border-rule focus:border-accent text-xs font-sans bg-white shadow-none rounded-none">
                                <option value="">Select Level</option>
                                <option>Early Years (Crèche / Nursery)</option>
                                <option>Primary School (Years 1 - 6)</option>
                                <option>Junior Secondary (JS1 - JS3)</option>
                                <option>Senior Secondary (SS1 - SS3)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] uppercase tracking-wider font-sans font-bold text-ink mb-1">Specific Questions or Requirements</label>
                        <textarea rows="3" placeholder="State any specific inquiry regarding boarding, curriculum, or scholarship..."
                                  class="w-full px-3.5 py-2.5 border border-rule focus:border-accent text-xs font-sans bg-white shadow-none rounded-none"></textarea>
                    </div>

                    <button type="submit"
                            :disabled="loading"
                            class="w-full py-3.5 uppercase text-xs tracking-widest font-sans font-bold bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover transition border border-accent">
                        <span x-show="!loading">Submit Dispatch to Registry</span>
                        <span x-show="loading">Transmitting...</span>
                    </button>
                </form>

            </div>

        </div>

    </div>
</section>
