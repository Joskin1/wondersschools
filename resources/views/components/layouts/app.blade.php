<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Wonders Kiddies Foundation Schools' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-white">
    <div class="flex flex-col min-h-screen">
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="bg-dark-green text-white sticky top-0 z-50 shadow-md">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-20 items-center">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ route('home') }}" class="font-bold text-2xl tracking-wider">
                            WKFS
                        </a>
                    </div>

                    <!-- Desktop Menu -->
                    <div class="hidden md:flex space-x-8">
                        <x-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-nav-link>
                        <x-nav-link href="{{ route('about') }}" :active="request()->routeIs('about')">About Us</x-nav-link>
                        <x-nav-link href="{{ route('academics') }}" :active="request()->routeIs('academics')">Academics</x-nav-link>
                        <x-nav-link href="{{ route('admissions') }}" :active="request()->routeIs('admissions')">Admissions</x-nav-link>
                        <x-nav-link href="{{ route('news') }}" :active="request()->routeIs('news')">News</x-nav-link>
                        <x-nav-link href="{{ route('gallery') }}" :active="request()->routeIs('gallery')">Gallery</x-nav-link>
                        <x-nav-link href="{{ route('contact') }}" :active="request()->routeIs('contact')">Contact</x-nav-link>
                    </div>

                    <!-- Mobile Menu Button -->
                    <div class="flex items-center md:hidden">
                        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-white hover:text-lime-green focus:outline-none transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div :class="{'block': open, 'hidden': ! open}" class="hidden md:hidden bg-dark-green border-t border-lime-green/20">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                    <x-mobile-nav-link href="{{ route('home') }}" :active="request()->routeIs('home')">Home</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('about') }}" :active="request()->routeIs('about')">About Us</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('academics') }}" :active="request()->routeIs('academics')">Academics</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('admissions') }}" :active="request()->routeIs('admissions')">Admissions</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('news') }}" :active="request()->routeIs('news')">News</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('gallery') }}" :active="request()->routeIs('gallery')">Gallery</x-mobile-nav-link>
                    <x-mobile-nav-link href="{{ route('contact') }}" :active="request()->routeIs('contact')">Contact</x-mobile-nav-link>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="flex-grow">
            {{ $slot }}
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white pt-12 pb-8">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- About -->
                    <div>
                        <h3 class="text-xl font-bold mb-4 text-lime-green">WKFS</h3>
                        <p class="text-gray-400 text-sm leading-relaxed">
                            Wonders Kiddies Foundation Schools is dedicated to providing a nurturing and stimulating environment for children to learn and grow.
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4 text-white">Quick Links</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><a href="{{ route('about') }}" class="hover:text-lime-green transition">About Us</a></li>
                            <li><a href="{{ route('admissions') }}" class="hover:text-lime-green transition">Admissions</a></li>
                            <li><a href="{{ route('academics') }}" class="hover:text-lime-green transition">Academics</a></li>
                            <li><a href="{{ route('contact') }}" class="hover:text-lime-green transition">Contact Us</a></li>
                        </ul>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4 text-white">Contact Us</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li class="flex items-start">
                                <svg class="h-5 w-5 mr-2 text-lime-green" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ \App\Models\Setting::where('key', 'school_address')->value('value') ?? '123 School Lane, City, Country' }}</span>
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 mr-2 text-lime-green" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>{{ \App\Models\Setting::where('key', 'school_phone')->value('value') ?? '+123 456 7890' }}</span>
                            </li>
                            <li class="flex items-center">
                                <svg class="h-5 w-5 mr-2 text-lime-green" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span>{{ \App\Models\Setting::where('key', 'school_email')->value('value') ?? 'info@wkfs.com' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-800 text-center text-sm text-gray-500">
                    &copy; {{ date('Y') }} Wonders Kiddies Foundation Schools. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
