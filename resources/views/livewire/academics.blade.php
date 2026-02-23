<div>
    <!-- Header -->
    <div class="bg-dark-green py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-extrabold text-white sm:text-5xl lg:text-6xl">{{ $site->get('academics_heading', 'Our Academic Programmes') }}</h1>
            <p class="mt-4 text-xl text-lime-green">{{ $site->get('academics_tagline') }}</p>
        </div>
    </div>

    <!-- Intro -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-lg mx-auto text-gray-500">
                {!! $site->get('academics_intro') !!}
            </div>
        </div>
    </div>

    <!-- Structured Learning Levels -->
    <div class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">Structured Learning Levels</h2>
                <p class="mt-4 text-xl text-gray-500">Tailored approaches for every stage of development.</p>
            </div>
            @php
                $levelBorderColors = ['border-lime-green', 'border-light-blue', 'border-brand-red', 'border-dark-green'];
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($site->get('academics_levels', []) as $level)
                @php $borderColor = $levelBorderColors[$loop->index % count($levelBorderColors)]; @endphp
                <div class="bg-white overflow-hidden shadow-lg rounded-xl border-t-4 {{ $borderColor }}">
                    <div class="px-6 py-8">
                        <h3 class="text-2xl font-bold text-dark-green mb-4">{{ $level['title'] }}</h3>
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Focus</h4>
                                <p class="text-gray-600">{{ $level['focus'] }}</p>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Key Outcome</h4>
                                <p class="text-gray-600">{{ $level['outcome'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Subject Highlights -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">Subject Highlights: Building Mastery</h2>
                <p class="mt-4 text-xl text-gray-500">Our approach to key subject areas.</p>
            </div>
            @php
                $subjectStyles = [
                    ['bg' => 'bg-lime-green', 'text' => 'text-dark-green', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />'],
                    ['bg' => 'bg-light-blue', 'text' => 'text-white', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />'],
                    ['bg' => 'bg-brand-red', 'text' => 'text-white', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />'],
                    ['bg' => 'bg-dark-green', 'text' => 'text-white', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />'],
                ];
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                @foreach($site->get('academics_subjects', []) as $subject)
                @php $style = $subjectStyles[$loop->index % count($subjectStyles)]; @endphp
                <div class="flex p-6 bg-gray-50 rounded-lg hover:shadow-md transition">
                    <div class="flex-shrink-0">
                        <div class="flex items-center justify-center h-12 w-12 rounded-md {{ $style['bg'] }} {{ $style['text'] }}">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                {!! $style['icon'] !!}
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-900">{{ $subject['title'] }}</h3>
                        <p class="mt-2 text-base text-gray-600">
                            {{ $subject['description'] }}
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
