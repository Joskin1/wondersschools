<div class="mb-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden"
     x-data="{
        primary: @entangle('data.primary_color'),
        secondary: @entangle('data.secondary_color'),
        accent: @entangle('data.accent_color'),
        schoolName: @entangle('data.school_name'),
        activeView: 'both',
        
        getEffectiveColor(color, fallback) {
            return (color && color.trim() !== '') ? color : fallback;
        },

        getContrastColor(hex, darkText = '#0B2545', lightText = '#FFFFFF') {
            if (!hex) return lightText;
            hex = hex.replace('#', '');
            if (hex.length === 3) {
                hex = hex.split('').map(c => c + c).join('');
            }
            if (hex.length !== 6) return lightText;
            
            const r = parseInt(hex.substr(0, 2), 16) / 255;
            const g = parseInt(hex.substr(2, 2), 16) / 255;
            const b = parseInt(hex.substr(4, 2), 16) / 255;
            
            const rLin = (r <= 0.04045) ? r / 12.92 : Math.pow((r + 0.055) / 1.055, 2.4);
            const gLin = (g <= 0.04045) ? g / 12.92 : Math.pow((g + 0.055) / 1.055, 2.4);
            const bLin = (b <= 0.04045) ? b / 12.92 : Math.pow((b + 0.055) / 1.055, 2.4);
            
            const luminance = 0.2126 * rLin + 0.7152 * gLin + 0.0722 * bLin;
            return luminance > 0.4 ? darkText : lightText;
        },

        init() {
            // Listen to direct DOM inputs on color pickers for real-time reactivity
            const form = this.$el.closest('form');
            if (form) {
                form.addEventListener('input', (e) => {
                    const target = e.target;
                    const name = target.getAttribute('name') || target.getAttribute('wire:model') || target.id || '';
                    if (name.includes('primary_color') && target.value) {
                        this.primary = target.value;
                    } else if (name.includes('secondary_color') && target.value) {
                        this.secondary = target.value;
                    } else if (name.includes('accent_color') && target.value) {
                        this.accent = target.value;
                    } else if (name.includes('school_name') && target.value) {
                        this.schoolName = target.value;
                    }
                });
            }
        }
     }">

    <!-- Mockup Browser Bar -->
    <div class="px-4 py-3 bg-gray-100 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-rose-400"></span>
            <span class="w-3 h-3 rounded-full bg-amber-400"></span>
            <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
            <span class="text-xs font-mono font-medium text-gray-500 dark:text-gray-400 ml-2 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                </svg>
                https://<span x-text="(schoolName || 'yourschool').toLowerCase().replace(/[^a-z0-9]/g, '')"></span>.edu.ng/
            </span>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                Live Preview
            </span>
        </div>
    </div>

    <!-- Live Website Simulation -->
    <div class="space-y-0 text-left font-sans">

        <!-- 1. Top Announcement Bar -->
        <div class="px-6 py-2.5 text-[11px] sm:text-xs transition-colors duration-300 flex items-center justify-between border-b"
             :style="{
                 backgroundColor: getEffectiveColor(primary, '#0B2545'),
                 color: getContrastColor(getEffectiveColor(primary, '#0B2545')),
                 borderColor: 'rgba(255,255,255,0.1)'
             }">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full transition-colors duration-300"
                      :style="{ backgroundColor: getEffectiveColor(accent, '#C8A951') }"></span>
                <span class="uppercase font-bold tracking-wider transition-colors duration-300"
                      :style="{ color: getEffectiveColor(accent, '#C8A951') }">
                    Admissions 2026/2027
                </span>
                <span class="opacity-80 hidden sm:inline">&mdash; Entrance examination and transfer enrollment now open.</span>
            </div>
            <div class="opacity-80 text-[11px]">
                +234 800 123 4567
            </div>
        </div>

        <!-- 2. Header & Navigation -->
        <div class="px-6 py-3.5 bg-[#FAF8F4] border-b border-[#E5E0D8] flex items-center justify-between transition-colors duration-300">
            <div class="flex items-center gap-3">
                <div class="w-7 h-7 rounded-none flex items-center justify-center font-serif font-bold text-xs border transition-colors duration-300"
                     :style="{
                         borderColor: getEffectiveColor(primary, '#0B2545'),
                         color: getEffectiveColor(primary, '#0B2545')
                     }"
                     x-text="(schoolName || 'Living Spring').substring(0, 2).toUpperCase()">
                </div>
                <span class="font-serif text-sm font-semibold tracking-tight transition-colors duration-300"
                      :style="{ color: getEffectiveColor(primary, '#0B2545') }"
                      x-text="schoolName || 'Living Spring'">
                </span>
            </div>

            <div class="hidden md:flex items-center gap-6 text-xs font-sans text-[#2D3748]/80 font-medium">
                <span class="hover:underline cursor-pointer">About</span>
                <span class="hover:underline cursor-pointer">Curriculum</span>
                <span class="hover:underline cursor-pointer">Outcomes</span>
                <span class="hover:underline cursor-pointer">Campus</span>
                <span class="hover:underline cursor-pointer">Bulletin</span>
            </div>

            <div>
                <span class="px-3 py-1.5 text-[11px] uppercase tracking-widest font-semibold text-white transition-colors duration-300"
                      :style="{
                          backgroundColor: getEffectiveColor(primary, '#0B2545'),
                          color: getContrastColor(getEffectiveColor(primary, '#0B2545'))
                      }">
                    Admissions
                </span>
            </div>
        </div>

        <!-- 3. Dual Section Simulation: Hero Cover (Dark Ink) + Editorial Card (Paper) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 divide-y lg:divide-y-0 lg:divide-x divide-gray-200 dark:divide-gray-700">

            <!-- Hero Simulation (Dark Band / Ink Role) -->
            <div class="p-6 sm:p-8 flex flex-col justify-between relative overflow-hidden transition-colors duration-300 min-h-[260px]"
                 :style="{
                     backgroundColor: getEffectiveColor(primary, '#0B2545'),
                     color: getContrastColor(getEffectiveColor(primary, '#0B2545'))
                 }">
                <!-- Background subtle gradient overlay -->
                <div class="absolute inset-0 bg-gradient-to-tr from-black/30 via-transparent to-white/5 pointer-events-none"></div>

                <div class="relative z-10 space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] uppercase tracking-[0.25em] font-bold font-sans transition-colors duration-300"
                              :style="{ color: getEffectiveColor(accent, '#C8A951') }">
                            ★ 2026 / 2027 Academic Session
                        </span>
                    </div>

                    <h3 class="font-serif text-xl sm:text-2xl font-semibold tracking-tight leading-tight"
                        :style="{ color: getContrastColor(getEffectiveColor(primary, '#0B2545')) }">
                        Nurturing Intellectual Depth &amp; Moral Leadership
                    </h3>

                    <p class="text-xs font-sans opacity-80 leading-relaxed max-w-sm">
                        An accredited institution committed to scholastic rigor, scientific inquiry, and character formation.
                    </p>
                </div>

                <div class="relative z-10 flex flex-wrap items-center gap-3 pt-5 mt-4 border-t"
                     :style="{ borderColor: 'rgba(255,255,255,0.15)' }">
                    <button type="button"
                            class="px-4 py-2 text-[11px] uppercase tracking-wider font-semibold font-sans transition-colors duration-300 shadow-sm"
                            :style="{
                                backgroundColor: getEffectiveColor(accent, '#C8A951'),
                                color: getContrastColor(getEffectiveColor(accent, '#C8A951'), '#0B2545', '#FFFFFF')
                            }">
                        Apply for Admission &rarr;
                    </button>

                    <button type="button"
                            class="px-4 py-2 text-[11px] uppercase tracking-wider font-semibold font-sans border bg-transparent transition-colors duration-300"
                            :style="{
                                borderColor: 'rgba(255,255,255,0.4)',
                                color: getContrastColor(getEffectiveColor(primary, '#0B2545'))
                            }">
                        Explore Prospectus
                    </button>
                </div>
            </div>

            <!-- Prospectus Editorial Simulation (Light Paper Role) -->
            <div class="p-6 sm:p-8 bg-[#FAF8F4] flex flex-col justify-between space-y-6 transition-colors duration-300 min-h-[260px]">
                <div class="space-y-3">
                    <!-- Eyebrow & Hairline -->
                    <div class="flex items-center gap-2.5">
                        <span class="text-[10px] uppercase tracking-[0.25em] font-bold font-sans transition-colors duration-300"
                              :style="{ color: getEffectiveColor(accent, '#C8A951') }">
                            01 &mdash; Academic Distinctives
                        </span>
                        <span class="flex-grow h-[1px] bg-[#E5E0D8]"></span>
                    </div>

                    <!-- Heading -->
                    <h4 class="font-serif text-lg sm:text-xl font-semibold tracking-tight transition-colors duration-300 leading-snug"
                        :style="{ color: getEffectiveColor(primary, '#0B2545') }">
                        A Tradition of Uncompromising Standard
                    </h4>

                    <!-- Paragraph -->
                    <p class="text-xs font-sans leading-relaxed text-[#2D3748]/90">
                        Our dedicated tutorial masters, modern science laboratories, and immersive pastoral mentorship ensure every student discovers their latent gifts.
                    </p>
                </div>

                <!-- Live Stat Card Preview -->
                <div class="p-3.5 bg-white border border-[#E5E0D8] flex items-center justify-between gap-4">
                    <div>
                        <span class="block font-serif text-xl sm:text-2xl font-bold tracking-tight transition-colors duration-300"
                              :style="{ color: getEffectiveColor(accent, '#C8A951') }">
                            100%
                        </span>
                        <span class="block text-[10px] uppercase tracking-wider font-semibold text-[#2D3748]">
                            WAEC Distinction Rate
                        </span>
                    </div>

                    <div class="text-right">
                        <span class="inline-block px-2 py-0.5 text-[9px] uppercase tracking-widest font-mono font-bold rounded"
                              :style="{
                                  backgroundColor: getEffectiveColor(secondary, '#1E293B') + '15',
                                  color: getEffectiveColor(secondary, '#1E293B')
                              }">
                            Support Token
                        </span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- Color Token Summary Pill Bar -->
    <div class="p-4 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700 flex flex-wrap items-center justify-between gap-4 text-xs font-sans">
        <div class="flex flex-wrap items-center gap-4">
            <!-- Primary Token -->
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full border border-gray-300 dark:border-gray-600 shadow-xs"
                      :style="{ backgroundColor: getEffectiveColor(primary, '#0B2545') }"></span>
                <div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Primary (Ink):</span>
                    <span class="font-mono text-gray-500 dark:text-gray-400" x-text="getEffectiveColor(primary, '#0B2545')"></span>
                </div>
            </div>

            <!-- Secondary Token -->
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full border border-gray-300 dark:border-gray-600 shadow-xs"
                      :style="{ backgroundColor: getEffectiveColor(secondary, '#1E293B') }"></span>
                <div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Secondary (Support):</span>
                    <span class="font-mono text-gray-500 dark:text-gray-400" x-text="getEffectiveColor(secondary, '#1E293B')"></span>
                </div>
            </div>

            <!-- Accent Token -->
            <div class="flex items-center gap-2">
                <span class="w-4 h-4 rounded-full border border-gray-300 dark:border-gray-600 shadow-xs"
                      :style="{ backgroundColor: getEffectiveColor(accent, '#C8A951') }"></span>
                <div>
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Accent (Gold):</span>
                    <span class="font-mono text-gray-500 dark:text-gray-400" x-text="getEffectiveColor(accent, '#C8A951')"></span>
                </div>
            </div>
        </div>

        <div class="text-[11px] text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
            </svg>
            Auto-contrasting &amp; theme token injection active
        </div>
    </div>
</div>

