@php
    $record = $getRecord();
    $plan = $record?->getPairedLessonPlan();

    if ($plan) {
        $plan->load(['referenceMaterials', 'instructionalMaterials', 'teachingMethods', 'reviewer']);
    }

    $vocabList = [];
    if ($plan?->key_vocabulary) {
        $vocabList = array_map('trim', explode(',', $plan->key_vocabulary));
    }
@endphp

@if($plan)
    <div class="space-y-6">
        {{-- Status bar --}}
        <div class="flex flex-wrap items-center gap-3 rounded-lg border px-4 py-3
            {{ match($plan->status) {
                'approved' => 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20',
                'rejected' => 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20',
                default    => 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20',
            } }}">
            <span class="text-xs font-semibold uppercase tracking-wide
                {{ match($plan->status) {
                    'approved' => 'text-green-700 dark:text-green-300',
                    'rejected' => 'text-red-700 dark:text-red-300',
                    default    => 'text-amber-700 dark:text-amber-300',
                } }}">
                Lesson Plan Status: {{ ucfirst($plan->status) }}
            </span>
            @if($plan->time)
                <span class="text-xs text-gray-500 dark:text-gray-400">· Duration: {{ $plan->time }}</span>
            @endif
            @if($plan->section)
                <span class="text-xs text-gray-500 dark:text-gray-400">· Section: {{ $plan->section }}</span>
            @endif
        </div>

        @php
            $sections = [
                ['label' => 'Learning Objectives', 'type' => 'objectives', 'value' => $plan->learning_objectives, 'intro' => 'At the end of the lesson, students should be able to:'],
                ['label' => 'Key Vocabulary Words', 'type' => 'vocab', 'value' => $vocabList],
                ['label' => 'Reference Materials', 'type' => 'tags', 'value' => $plan->referenceMaterials->pluck('name')->toArray()],
                ['label' => 'Instructional Materials', 'type' => 'tags', 'value' => $plan->instructionalMaterials->pluck('name')->toArray()],
                ['label' => 'Building Background / Connection to Prior Knowledge', 'type' => 'html', 'value' => $plan->prior_knowledge],
                ['label' => 'Content', 'type' => 'html', 'value' => $plan->content],
                ['label' => 'Teaching Method', 'type' => 'tags', 'value' => $plan->teachingMethods->pluck('name')->toArray()],
                ['label' => 'Presentation Steps', 'type' => 'steps', 'value' => $plan->presentation_steps],
                ['label' => 'Strategies and Activities', 'type' => 'html', 'value' => $plan->strategies_activities],
                ['label' => 'Assessment / Evaluation', 'type' => 'questions', 'value' => $plan->evaluation_questions, 'intro' => 'The teacher evaluates the students on the lesson taught by asking the following questions:'],
                ['label' => 'Conclusion', 'type' => 'html', 'value' => $plan->conclusion],
                ['label' => 'Assignment / Homework', 'type' => 'html', 'value' => $plan->assignment],
            ];
        @endphp

        @foreach($sections as $section)
            @if(!empty($section['value']))
                <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
                    <div class="border-b border-gray-100 dark:border-gray-700 px-5 py-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            {{ $section['label'] }}
                        </h3>
                        @if(!empty($section['intro']))
                            <p class="mt-0.5 text-xs italic text-gray-500 dark:text-gray-400">{{ $section['intro'] }}</p>
                        @endif
                    </div>

                    <div class="px-5 py-4">
                        @if($section['type'] === 'objectives')
                            <ol class="list-decimal list-inside space-y-1.5">
                                @foreach((array)$section['value'] as $obj)
                                    <li class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ is_array($obj) ? ($obj['objective'] ?? '') : $obj }}
                                    </li>
                                @endforeach
                            </ol>

                        @elseif($section['type'] === 'steps')
                            <ol class="list-decimal list-inside space-y-2">
                                @foreach((array)$section['value'] as $step)
                                    <li class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ is_array($step) ? ($step['step'] ?? '') : $step }}
                                    </li>
                                @endforeach
                            </ol>

                        @elseif($section['type'] === 'questions')
                            <ol class="list-decimal list-inside space-y-1.5">
                                @foreach((array)$section['value'] as $q)
                                    <li class="text-sm text-gray-700 dark:text-gray-300">
                                        {{ is_array($q) ? ($q['question'] ?? '') : $q }}
                                    </li>
                                @endforeach
                            </ol>

                        @elseif($section['type'] === 'tags' || $section['type'] === 'vocab')
                            <div class="flex flex-wrap gap-2">
                                @foreach((array)$section['value'] as $tag)
                                    @if(!empty($tag))
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                            {{ $tag }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>

                        @elseif($section['type'] === 'html')
                            <div class="prose max-w-none text-sm text-gray-700 dark:prose-invert dark:text-gray-300 leading-relaxed">
                                {!! $section['value'] !!}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach

        @if($plan->admin_comment)
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">Admin Feedback on Lesson Plan</p>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">{{ $plan->admin_comment }}</p>
            </div>
        @endif
    </div>
@else
    <div class="flex flex-col items-center justify-center rounded-lg border border-amber-200 bg-amber-50 p-8 dark:border-amber-800 dark:bg-amber-900/20">
        <x-heroicon-o-exclamation-triangle class="mb-3 h-10 w-10 text-amber-400" />
        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">No Lesson Plan Found</p>
        <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
            The teacher has not yet submitted a Lesson Plan for this submission.
            A paired Lesson Plan is required before this submission can be approved.
        </p>
    </div>
@endif
