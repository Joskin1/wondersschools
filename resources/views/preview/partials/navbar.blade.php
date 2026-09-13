<!-- ====== Prospectus Header / Navigation ====== -->
<header x-data="{ scrolled: false, mobileOpen: false, portalsOpen: false }"
        @scroll.window="scrolled = (window.pageYOffset > 30)"
        :class="scrolled ? 'bg-[#FAF8F4]/95 backdrop-blur-md shadow-sm border-b border-[#E5E0D8]' : 'bg-[#FAF8F4] border-b border-[#E5E0D8]'"
        class="sticky top-0 z-50 transition-all duration-300">
  
  <!-- Top Academic Session Notice -->
  <div class="bg-[#0B2545] text-[#FAF8F4] text-[11px] sm:text-xs py-2 px-4 border-b border-[#0B2545]/20">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-1">
      <div class="flex items-center gap-2">
        <span class="inline-block w-1.5 h-1.5 rounded-full bg-[#C8A951]"></span>
        <span class="tracking-wider uppercase font-semibold text-[#C8A951]">Admissions 2026/2027</span>
        <span class="text-white/80 hidden sm:inline">&mdash; Entrance examination and transfer enrollment now open.</span>
      </div>
      <div class="flex items-center gap-4 text-white/90">
        @if(!empty($school['contact_phone']))
          <a href="tel:{{ $school['contact_phone'] }}" class="hover:text-[#C8A951] transition">{{ $school['contact_phone'] }}</a>
        @endif
        @if(!empty($school['contact_email']))
          <span class="text-white/30 hidden sm:inline">|</span>
          <a href="mailto:{{ $school['contact_email'] }}" class="hover:text-[#C8A951] transition hidden sm:inline">{{ $school['contact_email'] }}</a>
        @endif
      </div>
    </div>
  </div>

  <!-- Main Navigation Bar -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between py-4 lg:py-5">
      
      <!-- Crest & School Name -->
      <a href="#home" class="flex items-center gap-3.5 group">
        <div class="w-10 h-10 border border-[#0B2545] bg-[#0B2545] text-[#C8A951] flex items-center justify-center font-serif text-lg font-semibold tracking-wider">
          AC
        </div>
        <div>
          <span class="block font-serif text-lg sm:text-xl font-semibold tracking-tight text-[#0B2545] leading-none">
            {{ $school['name'] ?? 'Apex Crown College' }}
          </span>
          <span class="block text-[10px] sm:text-[11px] uppercase tracking-[0.2em] text-[#C8A951] mt-1 font-sans font-medium">
            Lagos &bull; Est. {{ $school['established'] ?? '2001' }}
          </span>
        </div>
      </a>

      <!-- Desktop Navigation Links -->
      <nav class="hidden lg:flex items-center gap-8 text-xs font-sans font-medium tracking-wider uppercase text-[#0B2545]/80">
        <a href="#about" class="hover:text-[#0B2545] hover:underline underline-offset-8 transition">About</a>
        <a href="#features" class="hover:text-[#0B2545] hover:underline underline-offset-8 transition">Distinctives</a>
        <a href="#academics" class="hover:text-[#0B2545] hover:underline underline-offset-8 transition">Curriculum</a>
        <a href="#stats" class="hover:text-[#0B2545] hover:underline underline-offset-8 transition">Outcomes</a>
        <a href="#facilities" class="hover:text-[#0B2545] hover:underline underline-offset-8 transition">Campus</a>
        <a href="#news" class="hover:text-[#0B2545] hover:underline underline-offset-8 transition">Bulletin</a>
        <a href="#contact" class="hover:text-[#0B2545] hover:underline underline-offset-8 transition">Contact</a>
      </nav>

      <!-- Action Buttons -->
      <div class="hidden sm:flex items-center gap-3">
        <!-- Portals Dropdown -->
        <div class="relative" @click.outside="portalsOpen = false">
          <button @click="portalsOpen = !portalsOpen"
                  type="button"
                  class="px-4 py-2.5 text-xs font-sans uppercase tracking-wider font-semibold border border-[#0B2545]/30 text-[#0B2545] hover:border-[#0B2545] transition flex items-center gap-1.5 rounded-none">
            <span>Portals</span>
            <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
          </button>
          <div x-show="portalsOpen" x-cloak
               class="absolute right-0 mt-1 w-48 bg-white border border-[#E5E0D8] shadow-md py-1 z-50 rounded-none">
            <a href="/student/login" class="block px-4 py-2 text-xs font-sans text-[#0B2545] hover:bg-[#FAF8F4] transition">Student Portal</a>
            <a href="/teacher/login" class="block px-4 py-2 text-xs font-sans text-[#0B2545] hover:bg-[#FAF8F4] transition">Faculty Portal</a>
            <a href="/admin/login" class="block px-4 py-2 text-xs font-sans text-[#0B2545] hover:bg-[#FAF8F4] transition">Administration</a>
          </div>
        </div>

        <!-- Primary CTA -->
        <a href="#admissions"
           class="px-6 py-2.5 text-xs font-sans uppercase tracking-widest font-semibold bg-[#C8A951] text-[#0B2545] hover:bg-[#b89840] transition rounded-none">
          Admissions
        </a>
      </div>

      <!-- Mobile Menu Toggle -->
      <button @click="mobileOpen = !mobileOpen"
              type="button"
              class="lg:hidden p-2 text-[#0B2545] focus:outline-none"
              aria-label="Toggle Menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"/>
          <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>

    </div>
  </div>

  <!-- Mobile Drawer -->
  <div x-show="mobileOpen" x-cloak
       class="lg:hidden bg-[#FAF8F4] border-b border-[#E5E0D8] px-6 py-6 space-y-4">
    <nav class="flex flex-col space-y-3 text-xs uppercase tracking-widest text-[#0B2545] font-semibold">
      <a @click="mobileOpen = false" href="#about" class="py-1">About</a>
      <a @click="mobileOpen = false" href="#features" class="py-1">Distinctives</a>
      <a @click="mobileOpen = false" href="#academics" class="py-1">Curriculum</a>
      <a @click="mobileOpen = false" href="#stats" class="py-1">Outcomes</a>
      <a @click="mobileOpen = false" href="#facilities" class="py-1">Campus</a>
      <a @click="mobileOpen = false" href="#news" class="py-1">Bulletin</a>
      <a @click="mobileOpen = false" href="#contact" class="py-1">Contact</a>
    </nav>
    <div class="pt-4 border-t border-[#E5E0D8] flex flex-col gap-2">
      <div class="grid grid-cols-3 gap-2 text-center text-[11px] uppercase tracking-wider font-semibold">
        <a href="/student/login" class="py-2 border border-[#E5E0D8] text-[#0B2545]">Student</a>
        <a href="/teacher/login" class="py-2 border border-[#E5E0D8] text-[#0B2545]">Faculty</a>
        <a href="/admin/login" class="py-2 border border-[#E5E0D8] text-[#0B2545]">Admin</a>
      </div>
      <a @click="mobileOpen = false" href="#admissions" class="w-full text-center py-3 bg-[#C8A951] text-[#0B2545] text-xs uppercase tracking-widest font-bold">
        Apply for Admission
      </a>
    </div>
  </div>
</header>
