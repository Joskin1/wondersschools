@php
    $contactEyebrow = \App\Services\FrontendLibrary::get('contact_eyebrow', 'CAMPUS VISITATION & INQUIRY');
    $contactHeading = \App\Services\FrontendLibrary::get('contact_heading', 'Schedule a Guided Tour or Speak with Admissions');
    $contactIntro = \App\Services\FrontendLibrary::get('contact_intro', 'Our Admissions Registry receives families for private consultations and campus walkthroughs by appointment.');
    $contactAddressLabel = \App\Services\FrontendLibrary::get('contact_address_label', 'Campus Address');
    $contactAddress = \App\Services\FrontendLibrary::get('contact_address', \App\Services\FrontendLibrary::getSetting('school_address', 'Plot 14 - 18, Apex Boulevard, Lekki Phase 1, Lagos State, Nigeria'));
    
    $contactPhoneLabel = \App\Services\FrontendLibrary::get('contact_phone_label', 'Telephone');
    $primaryPhone = \App\Services\FrontendLibrary::getSetting('school_phone', '+234 800 123 4567');
    $additionalPhones = \App\Services\FrontendLibrary::getJson('contact_additional_phones', ['+234 812 345 6789']);
    $allPhones = array_filter(array_unique(array_merge([$primaryPhone], $additionalPhones)));

    $contactEmailLabel = \App\Services\FrontendLibrary::get('contact_email_label', 'Registry Email');
    $primaryEmail = \App\Services\FrontendLibrary::getSetting('school_email', 'admissions@apexcrown.edu.ng');
    $additionalEmails = \App\Services\FrontendLibrary::getJson('contact_additional_emails', ['info@apexcrown.edu.ng']);
    $allEmails = array_filter(array_unique(array_merge([$primaryEmail], $additionalEmails)));

    $contactVisitingHoursLabel = \App\Services\FrontendLibrary::get('contact_visiting_hours_label', 'Admissions Hours');
    $contactVisitingHours = \App\Services\FrontendLibrary::get('contact_visiting_hours', 'Monday – Friday: 8:00 AM – 4:00 PM | Saturday: 9:00 AM – 1:00 PM');

    $contactFormTitle = \App\Services\FrontendLibrary::get('contact_form_title', 'Admissions Prospectus Inquiry');
    $contactFormDesc = \App\Services\FrontendLibrary::get('contact_form_desc', 'Submit your inquiry and our admissions counsellor will respond within one business day.');
    $contactFormNameLabel = \App\Services\FrontendLibrary::get('contact_form_name_label', 'Parent / Guardian Name *');
    $contactFormPhoneLabel = \App\Services\FrontendLibrary::get('contact_form_phone_label', 'Telephone Number *');
    $contactFormEmailLabel = \App\Services\FrontendLibrary::get('contact_form_email_label', 'Email Address *');
    $contactFormGradeLabel = \App\Services\FrontendLibrary::get('contact_form_grade_label', 'Class Level of Interest *');
    $contactFormGradePlaceholder = \App\Services\FrontendLibrary::get('contact_form_grade_placeholder', 'Select Candidate Grade');
    $contactFormClasses = \App\Services\FrontendLibrary::getJson('contact_form_classes', [
        ['value' => 'jss1', 'label' => 'Junior Secondary 1 (Entry)'],
        ['value' => 'jss2', 'label' => 'Junior Secondary 2 (Transfer)'],
        ['value' => 'sss1', 'label' => 'Senior Secondary 1 (Sciences)'],
        ['value' => 'sss1-arts', 'label' => 'Senior Secondary 1 (Arts & Commercial)'],
    ]);
    $contactFormNotesLabel = \App\Services\FrontendLibrary::get('contact_form_notes_label', 'Prospective Scholar Notes / Questions');
    $contactFormSuccessTitle = \App\Services\FrontendLibrary::get('contact_form_success_title', 'Inquiry Received');
    $contactFormSuccessDesc = \App\Services\FrontendLibrary::get('contact_form_success_desc', 'Thank you for inquiring about Apex Crown College. The Admissions Office has received your details and will get in touch shortly.');
@endphp

<!-- ====== 08 — Campus Visitation & Inquiry (Editorial Form) ====== -->
<section id="contact" class="py-24 md:py-32 bg-paper" x-data="{ sent: false }">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-accent">
          {{ $sectionNumber ?? '08' }} &mdash; {{ $contactEyebrow }}
        </span>
        <span class="flex-grow h-[1px] bg-rule"></span>
      </div>
    </div>

    <!-- 12-Column Grid: Visitation info (Cols 1-5), Inquiry form (Cols 7-12) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8">
      
      <!-- Left: Visitation Details (Cols 1-5) -->
      <div class="lg:col-span-5 space-y-8">
        <div>
          <h2 class="font-serif font-semibold text-ink tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
            {{ $contactHeading }}
          </h2>
          @if(!empty($contactIntro))
            <p class="mt-4 text-xs sm:text-sm text-body/80 font-sans leading-[1.7] max-w-[62ch]">
              {{ $contactIntro }}
            </p>
          @endif
        </div>

        <div class="space-y-6 pt-4 border-t border-rule text-xs sm:text-sm font-sans">
          
          <!-- Campus Address -->
          <div>
            <span class="text-[10px] uppercase tracking-widest text-accent font-bold block mb-1">{{ $contactAddressLabel }}</span>
            <p class="text-ink font-medium leading-relaxed">{{ $contactAddress }}</p>
          </div>

          <!-- Phone Lines -->
          <div>
            <span class="text-[10px] uppercase tracking-widest text-accent font-bold block mb-1">{{ $contactPhoneLabel }}</span>
            <div class="space-y-1">
              @foreach($allPhones as $phone)
                <p><a href="tel:{{ $phone }}" class="text-ink hover:text-accent transition font-medium">{{ $phone }}</a></p>
              @endforeach
            </div>
          </div>

          <!-- Email Desks -->
          <div>
            <span class="text-[10px] uppercase tracking-widest text-accent font-bold block mb-1">{{ $contactEmailLabel }}</span>
            <div class="space-y-1">
              @foreach($allEmails as $email)
                <p><a href="mailto:{{ $email }}" class="text-ink hover:text-accent transition font-medium">{{ $email }}</a></p>
              @endforeach
            </div>
          </div>

          <!-- Visiting Hours -->
          <div>
            <span class="text-[10px] uppercase tracking-widest text-accent font-bold block mb-1">{{ $contactVisitingHoursLabel }}</span>
            <p class="text-ink font-medium leading-relaxed">{{ $contactVisitingHours }}</p>
          </div>

        </div>
      </div>

      <!-- Right: Inquiry Form (Cols 7-12) -->
      <div class="lg:col-span-6 lg:col-start-7">
        <div class="bg-white border border-rule p-8 sm:p-12 shadow-none rounded-none">
          
          <div x-show="!sent">
            <h3 class="font-serif text-xl sm:text-2xl font-semibold text-ink mb-2">
              {{ $contactFormTitle }}
            </h3>
            @if(!empty($contactFormDesc))
              <p class="text-xs text-body/70 font-sans mb-8">
                {{ $contactFormDesc }}
              </p>
            @endif

            <form @submit.prevent="sent = true" class="space-y-6 text-xs font-sans">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <label class="block uppercase tracking-wider text-ink font-bold mb-2">{{ $contactFormNameLabel }}</label>
                  <input type="text" required placeholder="e.g. Dr. Babatunde Williams"
                         class="w-full bg-paper border border-rule p-3 text-xs text-ink focus:outline-none focus:border-ink rounded-none" />
                </div>
                <div>
                  <label class="block uppercase tracking-wider text-ink font-bold mb-2">{{ $contactFormPhoneLabel }}</label>
                  <input type="tel" required placeholder="e.g. 0803 123 4567"
                         class="w-full bg-paper border border-rule p-3 text-xs text-ink focus:outline-none focus:border-ink rounded-none" />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <label class="block uppercase tracking-wider text-ink font-bold mb-2">{{ $contactFormEmailLabel }}</label>
                  <input type="email" required placeholder="williams@example.com"
                         class="w-full bg-paper border border-rule p-3 text-xs text-ink focus:outline-none focus:border-ink rounded-none" />
                </div>
                <div>
                  <label class="block uppercase tracking-wider text-ink font-bold mb-2">{{ $contactFormGradeLabel }}</label>
                  <select required class="w-full bg-paper border border-rule p-3 text-xs text-ink focus:outline-none focus:border-ink rounded-none">
                    <option value="">{{ $contactFormGradePlaceholder }}</option>
                    @foreach($contactFormClasses as $grade)
                      @if(is_array($grade))
                        <option value="{{ $grade['value'] ?? '' }}">{{ $grade['label'] ?? '' }}</option>
                      @else
                        <option value="{{ $grade }}">{{ $grade }}</option>
                      @endif
                    @endforeach
                  </select>
                </div>
              </div>

              <div>
                <label class="block uppercase tracking-wider text-ink font-bold mb-2">{{ $contactFormNotesLabel }}</label>
                <textarea rows="3" placeholder="Candidate's current school, specific questions, or boarding preferences..."
                          class="w-full bg-paper border border-rule p-3 text-xs text-ink focus:outline-none focus:border-ink rounded-none"></textarea>
              </div>

              <button type="submit"
                      class="w-full py-4 uppercase text-xs tracking-widest font-sans font-semibold bg-ink text-white hover:bg-accent hover:text-ink transition rounded-none">
                Submit Admissions Inquiry
              </button>
            </form>
          </div>

          <!-- Submission Confirmation -->
          <div x-show="sent" x-cloak class="py-12 text-center space-y-4">
            <div class="w-12 h-12 border border-accent text-accent font-serif text-xl flex items-center justify-center mx-auto">
              ✓
            </div>
            <h4 class="font-serif text-xl font-semibold text-ink">{{ $contactFormSuccessTitle }}</h4>
            <p class="text-xs text-body/80 font-sans max-w-sm mx-auto leading-relaxed">
              {{ $contactFormSuccessDesc }}
            </p>
            <button @click="sent = false" type="button" class="mt-4 text-xs uppercase tracking-wider font-semibold text-ink underline">
              Submit Another Inquiry
            </button>
          </div>

        </div>
      </div>

    </div>

  </div>
</section>
