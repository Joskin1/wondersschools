@extends('preview.layouts.preview')

@section('title', ($school['name'] ?? 'Apex Crown College') . ' | Admissions Prospectus 2026/2027')
@section('meta_description', 'Official admissions prospectus of ' . ($school['name'] ?? 'Apex Crown College') . '. A premier British-Nigerian secondary institution in Lagos.')

@section('content')
    {{-- Navigation --}}
    @include('preview.partials.navbar')

    <main>
        {{-- 1. Hero (Full-bleed, lower-left text block, 85vh) --}}
        @include('preview.partials.hero')

        {{-- 2. 01 — About / Head of School (Asymmetric 12-col grid) --}}
        @include('preview.partials.about')

        {{-- 3. 02 — Distinctives (Table of Contents editorial rows) --}}
        @include('preview.partials.features')

        {{-- 4. 03 — Examination Outcomes / Stats (Full-bleed ink band, 6xl gold numerals) --}}
        @include('preview.partials.results')

        {{-- 5. 04 — Curriculum & Programmes (Prospectus tracks) --}}
        @include('preview.partials.academics')

        {{-- 6. 05 — Campus Infrastructure (Uneven 2x2 first, 1x1 rest) --}}
        @include('preview.partials.facilities')

        {{-- 7. 06 — Bulletin & Announcements (Horizontal rows with left square thumbnail) --}}
        @include('preview.partials.news')

        {{-- 8. 07 — Voices / Testimonials (Editorial quote layout) --}}
        @include('preview.partials.testimonials')

        {{-- 9. Admissions CTA (Full-bleed ink, centred headline) --}}
        @include('preview.partials.admissions')

        {{-- 10. 08 — Campus Visitation & Inquiry Form --}}
        @include('preview.partials.contact')
    </main>

    {{-- Footer (Ink background, 4 columns, hairline rules) --}}
    @include('preview.partials.footer')
@endsection
