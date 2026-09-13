<!-- ====== Prospectus Footer (Ink Background, Hairline Column Borders) ====== -->
<footer class="bg-[#0B2545] text-[#FAF8F4] pt-20 pb-12 border-t border-[#C8A951]/30">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- 4 Columns with Hairline Rules Between Them -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 border-b border-white/10 pb-16">
      
      <!-- Col 1: Crest & Mission -->
      <div class="lg:pr-8 lg:border-r border-white/10 space-y-4 pb-8 lg:pb-0">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 border border-[#C8A951] bg-transparent text-[#C8A951] flex items-center justify-center font-serif text-lg font-semibold tracking-wider">
            AC
          </div>
          <div>
            <span class="block font-serif text-base font-semibold tracking-tight text-white leading-none">
              {{ $school['name'] ?? 'Apex Crown College' }}
            </span>
            <span class="block text-[10px] uppercase tracking-[0.2em] text-[#C8A951] mt-1 font-sans">
              Prospectus Edition
            </span>
          </div>
        </div>

        <p class="text-xs text-white/70 font-sans leading-[1.7] max-w-xs">
          {{ $school['footer']['description'] ?? 'An accredited British-Nigerian secondary school dedicated to academic brilliance, moral character, and global leadership.' }}
        </p>

        <p class="text-[11px] text-[#C8A951] font-sans">
          {{ $school['footer']['accreditations'] ?? 'Accredited by WAEC, NECO & Cambridge International.' }}
        </p>
      </div>

      <!-- Col 2: Prospectus Chapters -->
      <div class="lg:px-8 lg:border-r border-white/10 space-y-4 py-8 lg:py-0 border-t md:border-t-0 border-white/10">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-[#C8A951] block">
          Prospectus
        </span>
        <ul class="space-y-2.5 text-xs font-sans text-white/80">
          <li><a href="#about" class="hover:text-[#C8A951] transition">01 &mdash; About the College</a></li>
          <li><a href="#features" class="hover:text-[#C8A951] transition">02 &mdash; Distinctives &amp; Mentorship</a></li>
          <li><a href="#stats" class="hover:text-[#C8A951] transition">03 &mdash; Examination Outcomes</a></li>
          <li><a href="#academics" class="hover:text-[#C8A951] transition">04 &mdash; Curriculum Tracks</a></li>
          <li><a href="#facilities" class="hover:text-[#C8A951] transition">05 &mdash; Campus Infrastructure</a></li>
        </ul>
      </div>

      <!-- Col 3: Admissions & Registry -->
      <div class="lg:px-8 lg:border-r border-white/10 space-y-4 py-8 lg:py-0 border-t md:border-t-0 border-white/10">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-[#C8A951] block">
          Registry &amp; Portals
        </span>
        <ul class="space-y-2.5 text-xs font-sans text-white/80">
          <li><a href="#admissions" class="hover:text-[#C8A951] transition">Entrance Examination Dates</a></li>
          <li><a href="#admissions" class="hover:text-[#C8A951] transition">Tuition &amp; Scholarships</a></li>
          <li><a href="/student/login" class="hover:text-[#C8A951] transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-[#C8A951]"></span>Student Portal</a></li>
          <li><a href="/teacher/login" class="hover:text-[#C8A951] transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-[#C8A951]"></span>Faculty Portal</a></li>
          <li><a href="/admin/login" class="hover:text-[#C8A951] transition flex items-center gap-2"><span class="w-1.5 h-1.5 bg-[#C8A951]"></span>Administration</a></li>
        </ul>
      </div>

      <!-- Col 4: Dispatch & Location -->
      <div class="lg:pl-8 space-y-4 pt-8 lg:pt-0 border-t md:border-t-0 border-white/10">
        <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold text-[#C8A951] block">
          Campus Registry
        </span>
        <p class="text-xs text-white/80 font-sans leading-relaxed">
          {{ $school['location'] ?? 'Lekki Phase 1, Lagos, Nigeria' }}
        </p>
        <p class="text-xs text-white/80 font-sans">
          Tel: {{ $school['contact_phone'] ?? '+234 800 123 4567' }}
        </p>
        <p class="text-xs text-white/80 font-sans">
          Email: {{ $school['contact_email'] ?? 'admissions@apexcrown.edu.ng' }}
        </p>
      </div>

    </div>

    <!-- Bottom Copyright & Legal Bar -->
    <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-sans text-white/60">
      <div>
        &copy; {{ date('Y') }} {{ $school['name'] ?? 'Apex Crown College' }}. All Rights Reserved.
      </div>
      <div class="flex items-center gap-6">
        <a href="#about" class="hover:text-white transition">Privacy Policy</a>
        <a href="#about" class="hover:text-white transition">Terms of Enrollment</a>
        <a href="#contact" class="hover:text-white transition">Campus Directions</a>
      </div>
    </div>

  </div>
</footer>
