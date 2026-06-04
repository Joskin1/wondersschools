<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? \App\Services\FrontendLibrary::getSetting('school_name', 'Our School') }}</title>
    <meta name="description" content="{{ \App\Services\FrontendLibrary::get('meta_description', 'A premium educational institution dedicated to academic excellence and holistic development.') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Montserrat:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --color-tenant-primary: {{ config('app.tenant_primary_color', '#0B2545') }};
            --color-tenant-secondary: {{ config('app.tenant_secondary_color', '#1e293b') }};
            --color-tenant-accent: {{ config('app.tenant_accent_color', '#EEB902') }};
        }
        .text-tenant-primary { color: var(--color-tenant-primary); }
        .text-tenant-accent { color: var(--color-tenant-accent); }
        .hover\:text-tenant-accent:hover { color: var(--color-tenant-accent); }
        .hover\:text-tenant-primary:hover { color: var(--color-tenant-primary); }
        .bg-tenant-primary { background-color: var(--color-tenant-primary); }
        .bg-tenant-secondary { background-color: var(--color-tenant-secondary); }
        .bg-tenant-accent { background-color: var(--color-tenant-accent); }
        .bg-tenant-accent\/20 { background-color: color-mix(in srgb, var(--color-tenant-accent) 20%, transparent); }
        .bg-tenant-primary\/20 { background-color: color-mix(in srgb, var(--color-tenant-primary) 20%, transparent); }
        .hover\:bg-tenant-primary:hover { background-color: var(--color-tenant-primary); }
        .border-tenant-accent { border-color: var(--color-tenant-accent); }
        .border-tenant-primary { border-color: var(--color-tenant-primary); }
        .border-tenant-accent\/20 { border-color: color-mix(in srgb, var(--color-tenant-accent) 20%, transparent); }
        .border-tenant-accent\/30 { border-color: color-mix(in srgb, var(--color-tenant-accent) 30%, transparent); }
        .focus\:border-tenant-accent:focus { border-color: var(--color-tenant-accent); }
        .focus\:ring-tenant-accent:focus { --tw-ring-color: var(--color-tenant-accent); }
        .from-tenant-primary { --tw-gradient-from: var(--color-tenant-primary); }
        .via-tenant-primary\/80 { --tw-gradient-stops: var(--tw-gradient-from), color-mix(in srgb, var(--color-tenant-primary) 80%, transparent), var(--tw-gradient-to, transparent); }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-white">
    <div class="flex flex-col min-h-screen">

        {{-- ── Navigation ──────────────────────────────────────────── --}}
        <nav x-data="{ open: false, scrolled: false }"
             x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 60 })"
             :style="scrolled ? 'background-color: var(--color-tenant-primary); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1)' : 'background-color: transparent; box-shadow: none'"
             class="fixed top-0 left-0 right-0 z-50" style="transition: background-color 0.35s ease, box-shadow 0.35s ease;">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20 items-center">

                    {{-- Logo + Brand --}}
                    <div class="flex-shrink-0 flex items-center space-x-3">
                        <a href="{{ route('home') }}" class="flex items-center space-x-3">
                            @if(\App\Services\FrontendLibrary::getSetting('school_logo'))
                                <img src="{{ Storage::url(\App\Services\FrontendLibrary::getSetting('school_logo')) }}"
                                     alt="{{ \App\Services\FrontendLibrary::getSetting('school_name', 'School') }} Logo"
                                     class="h-12 w-auto">
                            @endif
                            <span class="font-bold text-lg sm:text-xl text-white tracking-wide inline-block truncate max-w-[200px] sm:max-w-none">
                                {{ \App\Services\FrontendLibrary::getSetting('school_name', 'Our School') }}
                            </span>
                        </a>
                    </div>

                    {{-- Center: Desktop Nav Links --}}
                    <div class="hidden md:flex items-center space-x-8">
                        <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-nav-link>
                        <x-nav-link href="{{ route('about') }}" :active="request()->routeIs('about')">About Us</x-nav-link>
                        <x-nav-link href="{{ route('academics') }}" :active="request()->routeIs('academics')">Academics</x-nav-link>
                        <x-nav-link href="{{ route('gallery') }}" :active="request()->routeIs('gallery')">Gallery</x-nav-link>
                        <x-nav-link href="{{ route('contact') }}" :active="request()->routeIs('contact')">Contact Us</x-nav-link>
                    </div>

                    {{-- Right: Portal Buttons (Desktop) --}}
                    <div class="hidden md:flex items-center space-x-2">
                        @if(\App\Services\FrontendLibrary::get('student_portal_url'))
                            <a href="{{ \App\Services\FrontendLibrary::get('student_portal_url') }}" class="inline-flex items-center px-4 py-1.5 rounded-md text-sm font-semibold transition" style="background-color: var(--color-tenant-accent); color: #1D2A44;">Student Portal</a>
                        @endif
                        @if(\App\Services\FrontendLibrary::get('staff_portal_url'))
                            <a href="{{ \App\Services\FrontendLibrary::get('staff_portal_url') }}" class="inline-flex items-center px-4 py-1.5 rounded-md text-sm font-semibold text-white transition" style="border: 1.5px solid rgba(255,255,255,0.5);">Staff Portal</a>
                        @endif
                    </div>

                    {{-- Hamburger (Mobile) --}}
                    <div class="flex items-center md:hidden">
                        <button @click="open = !open" class="inline-flex items-center justify-center p-2 rounded-md text-white focus:outline-none transition" aria-label="Toggle navigation">
                            <svg class="h-7 w-7" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mobile Drawer --}}
            <div x-show="open" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-4"
                 class="md:hidden border-t border-white/20" style="background-color: var(--color-tenant-primary); border-top: 1px solid rgba(255,255,255,0.2);">
                <div class="px-4 pt-4 pb-6 space-y-1">
                    <x-mobile-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('about') }}" :active="request()->routeIs('about')">About Us</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('academics') }}" :active="request()->routeIs('academics')">Academics</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('gallery') }}" :active="request()->routeIs('gallery')">Gallery</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('contact') }}" :active="request()->routeIs('contact')">Contact Us</x-mobile-nav-link>

                    <div class="pt-4 mt-4 border-t border-white/20 space-y-2">
                        @if(\App\Services\FrontendLibrary::get('student_portal_url'))
                            <a href="{{ \App\Services\FrontendLibrary::get('student_portal_url') }}" class="block w-full text-center portal-btn portal-btn-accent">Student Portal</a>
                        @endif
                        @if(\App\Services\FrontendLibrary::get('staff_portal_url'))
                            <a href="{{ \App\Services\FrontendLibrary::get('staff_portal_url') }}" class="block w-full text-center portal-btn portal-btn-outline">Staff Portal</a>
                        @endif
                    </div>
                </div>
            </div>
        </nav>

        {{-- Page Content --}}
        <main class="flex-grow">
            {{ $slot }}
        </main>

        {{-- ── Footer ──────────────────────────────────────────────── --}}
        <footer class="pt-16 pb-8" style="background-color: var(--color-tenant-primary);">
            <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 text-white">

                    {{-- Column 1: Brand + Social --}}
                    <div>
                        @if(\App\Services\FrontendLibrary::getSetting('school_logo'))
                            <img src="{{ Storage::url(\App\Services\FrontendLibrary::getSetting('school_logo')) }}"
                                 alt="{{ \App\Services\FrontendLibrary::getSetting('school_name') }}"
                                 class="h-10 w-auto mb-4">
                        @else
                            <h3 class="text-xl font-bold mb-4" style="color: var(--color-tenant-accent);">
                                {{ \App\Services\FrontendLibrary::getSetting('school_name', 'Our School') }}
                            </h3>
                        @endif
                        <p class="text-white/60 text-sm leading-relaxed mb-6">
                            {{ \App\Services\FrontendLibrary::get('footer_description', 'Dedicated to providing a nurturing and stimulating environment for children to learn, grow, and achieve their full potential.') }}
                        </p>
                        <div class="flex space-x-3">
                            @if(\App\Services\FrontendLibrary::get('footer_social_facebook'))
                                <a href="{{ \App\Services\FrontendLibrary::get('footer_social_facebook') }}" target="_blank" class="w-10 h-10 rounded-full inline-flex items-center justify-center text-white transition" style="background: rgba(255,255,255,0.1);" aria-label="Facebook">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                            @endif
                            @if(\App\Services\FrontendLibrary::get('footer_social_instagram'))
                                <a href="{{ \App\Services\FrontendLibrary::get('footer_social_instagram') }}" target="_blank" class="w-10 h-10 rounded-full inline-flex items-center justify-center text-white transition" style="background: rgba(255,255,255,0.1);" aria-label="Instagram">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                                </a>
                            @endif
                            @if(\App\Services\FrontendLibrary::get('footer_social_linkedin'))
                                <a href="{{ \App\Services\FrontendLibrary::get('footer_social_linkedin') }}" target="_blank" class="w-10 h-10 rounded-full inline-flex items-center justify-center text-white transition" style="background: rgba(255,255,255,0.1);" aria-label="LinkedIn">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </a>
                            @endif
                            @if(\App\Services\FrontendLibrary::get('footer_social_x'))
                                <a href="{{ \App\Services\FrontendLibrary::get('footer_social_x') }}" target="_blank" class="w-10 h-10 rounded-full inline-flex items-center justify-center text-white transition" style="background: rgba(255,255,255,0.1);" aria-label="X (Twitter)">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Column 2: Quick Links --}}
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-wider mb-5" style="color: var(--color-tenant-accent);">Quick Links</h4>
                        <ul class="space-y-3 text-sm text-white/70">
                            <li><a href="{{ route('home') }}" class="hover:text-white transition">Home</a></li>
                            <li><a href="{{ route('about') }}" class="hover:text-white transition">About Us</a></li>
                            <li><a href="{{ route('academics') }}" class="hover:text-white transition">Academics</a></li>
                            <li><a href="{{ route('gallery') }}" class="hover:text-white transition">Gallery</a></li>
                            <li><a href="{{ route('contact') }}" class="hover:text-white transition">Contact Us</a></li>
                        </ul>
                    </div>

                    {{-- Column 3: Portals --}}
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-wider mb-5" style="color: var(--color-tenant-accent);">Portals</h4>
                        <ul class="space-y-3 text-sm text-white/70">
                            @if(\App\Services\FrontendLibrary::get('student_portal_url'))
                                <li><a href="{{ \App\Services\FrontendLibrary::get('student_portal_url') }}" class="hover:text-white transition">Student Portal</a></li>
                            @endif
                            @if(\App\Services\FrontendLibrary::get('staff_portal_url'))
                                <li><a href="{{ \App\Services\FrontendLibrary::get('staff_portal_url') }}" class="hover:text-white transition">Staff Portal</a></li>
                            @endif
                            @if(\App\Services\FrontendLibrary::get('common_entrance_url'))
                                <li><a href="{{ \App\Services\FrontendLibrary::get('common_entrance_url') }}" class="hover:text-white transition">Common Entrance</a></li>
                            @endif
                            <li><a href="{{ route('admissions') }}" class="hover:text-white transition">Admissions</a></li>
                            <li><a href="{{ route('news') }}" class="hover:text-white transition">News & Updates</a></li>
                        </ul>
                    </div>

                    {{-- Column 4: Contact --}}
                    <div>
                        <h4 class="text-sm font-bold uppercase tracking-wider mb-5" style="color: var(--color-tenant-accent);">Contact Us</h4>
                        <ul class="space-y-4 text-sm text-white/70">
                            <li class="flex items-start">
                                <svg class="h-5 w-5 mr-3 mt-0.5 flex-shrink-0" style="color: var(--color-tenant-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ \App\Services\FrontendLibrary::getSetting('school_address', '123 School Lane, City, Country') }}</span>
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 mr-3 flex-shrink-0" style="color: var(--color-tenant-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <a href="mailto:{{ \App\Services\FrontendLibrary::getSetting('school_email', 'info@school.com') }}" class="hover:text-white transition">
                                    {{ \App\Services\FrontendLibrary::getSetting('school_email', 'info@school.com') }}
                                </a>
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 mr-3 flex-shrink-0" style="color: var(--color-tenant-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <a href="tel:{{ \App\Services\FrontendLibrary::getSetting('school_phone', '+123 456 7890') }}" class="hover:text-white transition">
                                    {{ \App\Services\FrontendLibrary::getSetting('school_phone', '+123 456 7890') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Bottom Bar --}}
                <div class="mt-12 pt-8 border-t border-white/15 flex flex-col sm:flex-row justify-between items-center text-sm" style="color: rgba(255, 255, 255, 0.8);">
                    <span>&copy; {{ date('Y') }} {{ \App\Services\FrontendLibrary::getSetting('school_name', 'Our School') }}. All rights reserved.</span>
                    <span class="mt-2 sm:mt-0">Powered by <a href="#" class="hover:text-white transition">Wonders Platform</a></span>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
