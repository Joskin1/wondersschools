<div class="mb-6 p-6 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900/50"
     x-data="{
        primary: @entangle('data.primary_color'),
        secondary: @entangle('data.secondary_color'),
        accent: @entangle('data.accent_color'),
     }">
    <div class="text-xs uppercase tracking-widest font-semibold text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        Live Website Branding Preview
    </div>

    <!-- Preview Box simulating public website design tokens -->
    <div class="p-8 border transition-colors duration-300"
         :style="{
            backgroundColor: '#FAF8F4',
            borderColor: '#E5E0D8'
         }">
        
        <!-- Eyebrow & Hairline -->
        <div class="flex items-center gap-3 mb-4">
            <span class="text-xs uppercase tracking-[0.2em] font-bold font-sans transition-colors duration-300"
                  :style="{ color: accent || '#C8A951' }">
                01 — Academic Distinction
            </span>
            <span class="flex-grow h-[1px]" style="background-color: #E5E0D8;"></span>
        </div>

        <!-- Heading -->
        <h3 class="font-serif text-2xl sm:text-3xl font-semibold tracking-tight leading-snug mb-3 transition-colors duration-300"
            :style="{ color: primary || '#0B2545', fontFamily: 'Fraunces, Georgia, serif' }">
            Nurturing Intellectual Depth & Moral Leadership
        </h3>

        <!-- Paragraph -->
        <p class="text-sm font-sans leading-relaxed mb-6 max-w-xl transition-colors duration-300"
           :style="{ color: secondary || '#1E293B' }">
            An accredited secondary institution committed to scholastic rigor, scientific inquiry, and the formation of character.
        </p>

        <!-- CTA Buttons & Hairline -->
        <div class="flex flex-wrap items-center gap-4 pt-4 border-t" style="border-color: #E5E0D8;">
            <button type="button"
                    class="px-6 py-3 text-xs uppercase tracking-widest font-semibold font-sans transition-colors duration-300 shadow-sm"
                    :style="{
                        backgroundColor: accent || '#C8A951',
                        color: '#0B2545'
                    }">
                Apply for Admission &rarr;
            </button>

            <button type="button"
                    class="px-6 py-3 text-xs uppercase tracking-widest font-semibold font-sans border transition-colors duration-300 bg-transparent"
                    :style="{
                        borderColor: primary || '#0B2545',
                        color: primary || '#0B2545'
                    }">
                Explore Prospectus
            </button>
        </div>
    </div>
</div>
