<div>
    <!-- Header -->
    <div class="bg-dark-green py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-extrabold text-white sm:text-5xl lg:text-6xl">
                {{ \App\Services\FrontendLibrary::get('advantage_hero_title', 'The WKFS Advantage') }}
            </h1>
            <p class="mt-4 text-xl text-lime-green">
                {{ \App\Services\FrontendLibrary::get('advantage_hero_subtitle', 'A Foundation That Outlasts Trends.') }}
            </p>
        </div>
    </div>

    <!-- Intro -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-lg mx-auto text-gray-500">
                <p>
                    {!! \App\Services\FrontendLibrary::get(
                        'advantage_intro',
                        'A child\'s future is defined by the quality of their foundation. At Wonders Kiddies Foundation Schools (WKFS), our curriculum is strategically designed not just to meet required standards, but to <strong>exceed them</strong> by cultivating critical thinking, creativity, and essential life skills. We combine a solid core curriculum with modern, integrated learning methods to ensure every student is prepared not just for the next class, but for a fast-changing world.'
                    ) !!}
                </p>
            </div>
        </div>
    </div>

    <!-- Structured Learning Levels -->
    <div class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    {{ \App\Services\FrontendLibrary::get('learning_levels_title', 'Structured Learning Levels') }}
                </h2>
                <p class="mt-4 text-xl text-gray-500">
                    {{ \App\Services\FrontendLibrary::get('learning_levels_subtitle', 'Tailored approaches for every stage of development.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <!-- EYFS -->
                <div class="bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-lime-green">
                    <div class="px-6 py-8">
                        <h3 class="text-2xl font-bold text-dark-green mb-4">
                            {{ \App\Services\FrontendLibrary::get('eyfs_title', 'Early Years Foundation Stage (EYFS)') }}
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide">
                                    {{ \App\Services\FrontendLibrary::get('eyfs_focus_label', 'Focus') }}
                                </h4>
                                <p class="text-gray-600">
                                    {{ \App\Services\FrontendLibrary::get('eyfs_focus_text', 'Play-based learning, sensory exploration, and developing early literacy and numeracy.') }}
                                </p>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide">
                                    {{ \App\Services\FrontendLibrary::get('eyfs_outcome_label', 'Key Outcome') }}
                                </h4>
                                <p class="text-gray-600">
                                    {{ \App\Services\FrontendLibrary::get('eyfs_outcome_text', 'Building curiosity, fine motor skills, and social-emotional readiness.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Primary -->
                <div class="bg-white overflow-hidden shadow-lg rounded-xl border-t-4 border-light-blue">
                    <div class="px-6 py-8">
                        <h3 class="text-2xl font-bold text-dark-green mb-4">
                            {{ \App\Services\FrontendLibrary::get('primary_title', 'Primary School Programme') }}
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide">
                                    {{ \App\Services\FrontendLibrary::get('primary_focus_label', 'Focus') }}
                                </h4>
                                <p class="text-gray-600">
                                    {{ \App\Services\FrontendLibrary::get('primary_focus_text', 'Mastery of core subjects (Numeracy, Literacy, Science) combined with integrated studies (STEM, Coding Introduction).') }}
                                </p>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wide">
                                    {{ \App\Services\FrontendLibrary::get('primary_outcome_label', 'Key Outcome') }}
                                </h4>
                                <p class="text-gray-600">
                                    {{ \App\Services\FrontendLibrary::get('primary_outcome_text', 'Fostering independence, research skills, and strong problem-solving abilities.') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Subject Highlights -->
    <div class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    {{ \App\Services\FrontendLibrary::get('subjects_title', 'Subject Highlights: Building Mastery') }}
                </h2>
                <p class="mt-4 text-xl text-gray-500">
                    {{ \App\Services\FrontendLibrary::get('subjects_subtitle', 'Our approach to key subject areas.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <!-- Literacy -->
                <div class="flex p-6 bg-gray-50 rounded-lg hover:shadow-md transition">
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ \App\Services\FrontendLibrary::get('subject_literacy_title', 'Literacy & Communication') }}
                        </h3>
                        <p class="mt-2 text-base text-gray-600">
                            {!! \App\Services\FrontendLibrary::get(
                                'subject_literacy_text',
                                'We emphasize reading for comprehension and creative writing. Students learn not just <em>what</em> to read, but <em>how</em> to analyze, articulate, and present their ideas confidently.'
                            ) !!}
                        </p>
                    </div>
                </div>

                <!-- Numeracy -->
                <div class="flex p-6 bg-gray-50 rounded-lg hover:shadow-md transition">
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ \App\Services\FrontendLibrary::get('subject_numeracy_title', 'Numeracy & Logic') }}
                        </h3>
                        <p class="mt-2 text-base text-gray-600">
                            {{ \App\Services\FrontendLibrary::get(
                                'subject_numeracy_text',
                                'Moving beyond rote arithmetic, we use hands-on, conceptual learning to build strong mathematical reasoning. Our students learn to apply logic to real-world problems.'
                            ) }}
                        </p>
                    </div>
                </div>

                <!-- STEM -->
                <div class="flex p-6 bg-gray-50 rounded-lg hover:shadow-md transition">
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ \App\Services\FrontendLibrary::get('subject_stem_title', 'Integrated Science (STEM)') }}
                        </h3>
                        <p class="mt-2 text-base text-gray-600">
                            {{ \App\Services\FrontendLibrary::get(
                                'subject_stem_text',
                                'Science is taught through practical experimentation and inquiry, preparing students for future tech and engineering fields.'
                            ) }}
                        </p>
                    </div>
                </div>

                <!-- Character -->
                <div class="flex p-6 bg-gray-50 rounded-lg hover:shadow-md transition">
                    <div class="ml-4">
                        <h3 class="text-lg font-bold text-gray-900">
                            {{ \App\Services\FrontendLibrary::get('subject_character_title', 'Character & Ethics') }}
                        </h3>
                        <p class="mt-2 text-base text-gray-600">
                            {{ \App\Services\FrontendLibrary::get(
                                'subject_character_text',
                                'Robust training in core values, empathy, leadership, and responsibility, ensuring your child grows into a well-rounded and compassionate individual.'
                            ) }}
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>