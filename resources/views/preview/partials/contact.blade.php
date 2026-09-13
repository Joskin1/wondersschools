@if(!empty($school['contact']))
<!-- ====== 08 — Campus Visitation & Inquiry (Editorial Form) ====== -->
<section id="contact" class="py-24 md:py-32 bg-[#FAF8F4]" x-data="{ sent: false }">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Section Header Row: Eyebrow + Hairline -->
    <div class="mb-16">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-[#C8A951]">
          {{ $school['contact']['number'] ?? '08' }} &mdash; {{ $school['contact']['eyebrow'] ?? 'CONTACT' }}
        </span>
        <span class="flex-grow h-[1px] bg-[#E5E0D8]"></span>
      </div>
    </div>

    <!-- 12-Column Grid: Visitation info (Cols 1-5), Inquiry form (Cols 7-12) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8">
      
      <!-- Left: Visitation Details (Cols 1-5) -->
      <div class="lg:col-span-5 space-y-8">
        <div>
          <h2 class="font-serif font-semibold text-[#0B2545] tracking-tight leading-[1.15]" style="font-size: clamp(2rem, 4vw, 3rem);">
            {{ $school['contact']['heading'] }}
          </h2>
          <p class="mt-4 text-xs sm:text-sm text-[#2D3748]/80 font-sans leading-[1.7] max-w-[62ch]">
            Our Admissions Registry receives families for private consultations and campus walkthroughs by appointment.
          </p>
        </div>

        <div class="space-y-6 pt-4 border-t border-[#E5E0D8] text-xs sm:text-sm font-sans">
          
          <!-- Campus Address -->
          <div>
            <span class="text-[10px] uppercase tracking-widest text-[#C8A951] font-bold block mb-1">Campus Address</span>
            <p class="text-[#0B2545] font-medium leading-relaxed">{{ $school['contact']['address'] }}</p>
          </div>

          <!-- Phone Lines -->
          <div>
            <span class="text-[10px] uppercase tracking-widest text-[#C8A951] font-bold block mb-1">Telephone</span>
            <div class="space-y-1">
              @foreach($school['contact']['phones'] as $phone)
                <p><a href="tel:{{ $phone }}" class="text-[#0B2545] hover:text-[#C8A951] transition font-medium">{{ $phone }}</a></p>
              @endforeach
            </div>
          </div>

          <!-- Email Desks -->
          <div>
            <span class="text-[10px] uppercase tracking-widest text-[#C8A951] font-bold block mb-1">Registry Email</span>
            <div class="space-y-1">
              @foreach($school['contact']['emails'] as $email)
                <p><a href="mailto:{{ $email }}" class="text-[#0B2545] hover:text-[#C8A951] transition font-medium">{{ $email }}</a></p>
              @endforeach
            </div>
          </div>

          <!-- Visiting Hours -->
          <div>
            <span class="text-[10px] uppercase tracking-widest text-[#C8A951] font-bold block mb-1">Admissions Hours</span>
            <p class="text-[#0B2545] font-medium leading-relaxed">{{ $school['contact']['visiting_hours'] }}</p>
          </div>

        </div>
      </div>

      <!-- Right: Inquiry Form (Cols 7-12) -->
      <div class="lg:col-span-6 lg:col-start-7">
        <div class="bg-white border border-[#E5E0D8] p-8 sm:p-12 shadow-none rounded-none">
          
          <div x-show="!sent">
            <h3 class="font-serif text-xl sm:text-2xl font-semibold text-[#0B2545] mb-2">
              Admissions Prospectus Inquiry
            </h3>
            <p class="text-xs text-[#2D3748]/70 font-sans mb-8">
              Submit your inquiry and our admissions counsellor will respond within one business day.
            </p>

            <form @submit.prevent="sent = true" class="space-y-6 text-xs font-sans">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <label class="block uppercase tracking-wider text-[#0B2545] font-bold mb-2">Parent / Guardian Name *</label>
                  <input type="text" required placeholder="e.g. Dr. Babatunde Williams"
                         class="w-full bg-[#FAF8F4] border border-[#E5E0D8] p-3 text-xs text-[#0B2545] focus:outline-none focus:border-[#0B2545] rounded-none" />
                </div>
                <div>
                  <label class="block uppercase tracking-wider text-[#0B2545] font-bold mb-2">Telephone Number *</label>
                  <input type="tel" required placeholder="e.g. 0803 123 4567"
                         class="w-full bg-[#FAF8F4] border border-[#E5E0D8] p-3 text-xs text-[#0B2545] focus:outline-none focus:border-[#0B2545] rounded-none" />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <label class="block uppercase tracking-wider text-[#0B2545] font-bold mb-2">Email Address *</label>
                  <input type="email" required placeholder="williams@example.com"
                         class="w-full bg-[#FAF8F4] border border-[#E5E0D8] p-3 text-xs text-[#0B2545] focus:outline-none focus:border-[#0B2545] rounded-none" />
                </div>
                <div>
                  <label class="block uppercase tracking-wider text-[#0B2545] font-bold mb-2">Class Level of Interest *</label>
                  <select required class="w-full bg-[#FAF8F4] border border-[#E5E0D8] p-3 text-xs text-[#0B2545] focus:outline-none focus:border-[#0B2545] rounded-none">
                    <option value="">Select Candidate Grade</option>
                    <option value="jss1">Junior Secondary 1 (Entry)</option>
                    <option value="jss2">Junior Secondary 2 (Transfer)</option>
                    <option value="sss1">Senior Secondary 1 (Sciences)</option>
                    <option value="sss1-arts">Senior Secondary 1 (Arts &amp; Commercial)</option>
                  </select>
                </div>
              </div>

              <div>
                <label class="block uppercase tracking-wider text-[#0B2545] font-bold mb-2">Prospective Scholar Notes / Questions</label>
                <textarea rows="3" placeholder="Candidate's current school, specific questions, or boarding preferences..."
                          class="w-full bg-[#FAF8F4] border border-[#E5E0D8] p-3 text-xs text-[#0B2545] focus:outline-none focus:border-[#0B2545] rounded-none"></textarea>
              </div>

              <button type="submit"
                      class="w-full py-4 uppercase text-xs tracking-widest font-sans font-semibold bg-[#0B2545] text-white hover:bg-[#C8A951] hover:text-[#0B2545] transition rounded-none">
                Submit Admissions Inquiry
              </button>
            </form>
          </div>

          <!-- Submission Confirmation -->
          <div x-show="sent" x-cloak class="py-12 text-center space-y-4">
            <div class="w-12 h-12 border border-[#C8A951] text-[#C8A951] font-serif text-xl flex items-center justify-center mx-auto">
              ✓
            </div>
            <h4 class="font-serif text-xl font-semibold text-[#0B2545]">Inquiry Received</h4>
            <p class="text-xs text-[#2D3748]/80 font-sans max-w-sm mx-auto leading-relaxed">
              Thank you for inquiring about Apex Crown College. The Admissions Office has received your details and will get in touch shortly.
            </p>
            <button @click="sent = false" type="button" class="mt-4 text-xs uppercase tracking-wider font-semibold text-[#0B2545] underline">
              Submit Another Inquiry
            </button>
          </div>

        </div>
      </div>

    </div>

  </div>
</section>
@endif
