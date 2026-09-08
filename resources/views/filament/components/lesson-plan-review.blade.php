@php
    $record = $getRecord();
    $plan = $record?->getPairedLessonPlan();

    if ($plan) {
        $plan->load(['referenceMaterials', 'instructionalMaterials', 'teachingMethods', 'reviewer']);
    }

    $vocabList = $plan?->key_vocabulary
        ? array_filter(array_map('trim', explode(',', $plan->key_vocabulary)))
        : [];

    $statusStyles = match ($plan?->status) {
        'approved' => ['background: #ecfdf5', 'color: #166534', 'label' => 'Approved'],
        'rejected' => ['background: #fef2f2', 'color: #991b1b', 'label' => 'Rejected'],
        default => ['background: #fffbeb', 'color: #92400e', 'label' => 'Pending'],
    };

    $sections = $plan ? [
        ['label' => 'Learning Objectives', 'type' => 'objectives', 'value' => $plan->learning_objectives, 'intro' => 'At the end of the lesson, students should be able to:'],
        ['label' => 'Key Vocabulary', 'type' => 'tags', 'value' => $vocabList],
        ['label' => 'Reference Materials', 'type' => 'tags', 'value' => $plan->referenceMaterials->pluck('name')->toArray()],
        ['label' => 'Instructional Materials', 'type' => 'tags', 'value' => $plan->instructionalMaterials->pluck('name')->toArray()],
        ['label' => 'Background / Prior Knowledge', 'type' => 'html', 'value' => $plan->prior_knowledge],
        ['label' => 'Content', 'type' => 'html', 'value' => $plan->content],
        ['label' => 'Teaching Method', 'type' => 'tags', 'value' => $plan->teachingMethods->pluck('name')->toArray()],
        ['label' => 'Presentation Steps', 'type' => 'steps', 'value' => $plan->presentation_steps],
        ['label' => 'Strategies and Activities', 'type' => 'html', 'value' => $plan->strategies_activities],
        ['label' => 'Assessment / Evaluation', 'type' => 'questions', 'value' => $plan->evaluation_questions, 'intro' => 'The teacher evaluates the students by asking the following questions:'],
        ['label' => 'Conclusion', 'type' => 'html', 'value' => $plan->conclusion],
        ['label' => 'Assignment / Homework', 'type' => 'html', 'value' => $plan->assignment],
    ] : [];
@endphp

<style>
    .review-document { background: #fff; border: 1px solid #d1d5db; border-radius: 4px; box-shadow: 0 12px 30px rgba(15, 23, 42, .12); color: #1f2937; margin: 0 auto; max-width: 900px; padding: 56px 72px 72px; }
    .review-document__masthead { border-bottom: 2px solid #1f2937; margin-bottom: 32px; padding-bottom: 24px; text-align: center; }
    .review-document__eyebrow { color: #6b7280; font-size: 11px; font-weight: 700; letter-spacing: .16em; margin: 0 0 10px; text-transform: uppercase; }
    .review-document__title { color: #111827; font-size: 25px; font-weight: 800; line-height: 1.2; margin: 0; }
    .review-document__meta { color: #4b5563; display: flex; flex-wrap: wrap; font-size: 13px; gap: 8px 20px; justify-content: center; margin-top: 16px; }
    .review-document__status { border-radius: 999px; display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: .05em; margin-top: 16px; padding: 5px 12px; text-transform: uppercase; }
    .review-document__section { border-bottom: 1px solid #e5e7eb; margin: 0 0 28px; padding: 0 0 24px; }
    .review-document__heading { color: #111827; font-size: 16px; font-weight: 800; line-height: 1.35; margin: 0 0 10px; }
    .review-document__intro { color: #6b7280; font-size: 13px; font-style: italic; margin: -3px 0 13px; }
    .review-document__rich-text, .review-document__list { color: #374151; font-size: 15px; line-height: 1.8; }
    .review-document__rich-text p { margin: 0 0 14px; }
    .review-document__rich-text p:last-child { margin-bottom: 0; }
    .review-document__rich-text h1, .review-document__rich-text h2, .review-document__rich-text h3, .review-document__rich-text h4 { color: #111827; font-weight: 800; line-height: 1.35; margin: 22px 0 9px; }
    .review-document__rich-text h1 { font-size: 21px; }
    .review-document__rich-text h2 { font-size: 18px; }
    .review-document__rich-text h3, .review-document__rich-text h4 { font-size: 16px; }
    .review-document__rich-text strong, .review-document__rich-text b { color: #111827; font-weight: 800; }
    .review-document__rich-text em, .review-document__rich-text i { font-style: italic; }
    .review-document__rich-text ul, .review-document__rich-text ol { margin: 10px 0 16px; padding-left: 28px; }
    .review-document__rich-text ul { list-style: disc; }
    .review-document__rich-text ol { list-style: decimal; }
    .review-document__rich-text li { margin: 4px 0; padding-left: 4px; }
    .review-document__rich-text blockquote { border-left: 3px solid #9ca3af; color: #4b5563; font-style: italic; margin: 16px 0; padding-left: 16px; }
    .review-document__rich-text table { border-collapse: collapse; margin: 18px 0; width: 100%; }
    .review-document__rich-text th, .review-document__rich-text td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
    .review-document__rich-text th { background: #f3f4f6; font-weight: 800; }
    .review-document__list { margin: 0; padding-left: 28px; }
    .review-document__list--ordered { list-style: decimal; }
    .review-document__list li { margin: 6px 0; padding-left: 5px; }
    .review-document__tags { display: flex; flex-wrap: wrap; gap: 8px; }
    .review-document__tag { background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 3px; color: #374151; font-size: 13px; padding: 5px 9px; }
    .review-document__feedback { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 4px; color: #92400e; font-size: 14px; line-height: 1.7; padding: 16px; }
    .review-document__feedback strong { display: block; font-size: 12px; letter-spacing: .06em; margin-bottom: 5px; text-transform: uppercase; }
    @media (max-width: 700px) { .review-document { padding: 32px 22px 42px; } .review-document__title { font-size: 21px; } }
</style>

@if($plan)
    <article class="review-document">
        <header class="review-document__masthead">
            <p class="review-document__eyebrow">Lesson Plan</p>
            <h1 class="review-document__title">{{ $plan->title ?: ($record?->subject?->name . ' Lesson Plan') }}</h1>
            <div class="review-document__meta">
                <span><strong>Subject:</strong> {{ $record?->subject?->name }}</span>
                <span><strong>Class:</strong> {{ $record?->classroom?->name }}</span>
                <span><strong>Week:</strong> {{ $record?->week_number }}</span>
                @if($plan->time)<span><strong>Duration:</strong> {{ $plan->time }}</span>@endif
                @if($plan->section)<span><strong>Section:</strong> {{ $plan->section }}</span>@endif
            </div>
            <span class="review-document__status" style="background: {{ $statusStyles[0] }}; color: {{ $statusStyles[1] }};">{{ $statusStyles['label'] }}</span>
        </header>

        @foreach($sections as $section)
            @if(!empty($section['value']))
                <section class="review-document__section">
                    <h2 class="review-document__heading">{{ $section['label'] }}</h2>
                    @if(!empty($section['intro']))<p class="review-document__intro">{{ $section['intro'] }}</p>@endif

                    @if(in_array($section['type'], ['objectives', 'steps', 'questions']))
                        <ol class="review-document__list review-document__list--ordered">
                            @foreach((array) $section['value'] as $item)
                                <li>{{ is_array($item) ? ($item['objective'] ?? $item['step'] ?? $item['question'] ?? '') : $item }}</li>
                            @endforeach
                        </ol>
                    @elseif($section['type'] === 'tags')
                        <div class="review-document__tags">
                            @foreach((array) $section['value'] as $tag)
                                @if(!empty($tag))<span class="review-document__tag">{{ $tag }}</span>@endif
                            @endforeach
                        </div>
                    @else
                        @php($sectionValue = (string) $section['value'])
                        <div class="review-document__rich-text">
                            @if($sectionValue !== strip_tags($sectionValue))
                                {!! $sectionValue !!}
                            @else
                                {!! nl2br(e($sectionValue)) !!}
                            @endif
                        </div>
                    @endif
                </section>
            @endif
        @endforeach

        @if($plan->admin_comment)
            <div class="review-document__feedback"><strong>Admin Feedback</strong>{{ $plan->admin_comment }}</div>
        @endif
    </article>
@else
    <div class="rounded border border-amber-200 bg-amber-50 p-8 text-center text-amber-800">
        <x-heroicon-o-exclamation-triangle class="mx-auto mb-3 h-10 w-10 text-amber-400" />
        <p class="font-semibold">No Lesson Plan Found</p>
        <p class="mt-1 text-sm">A paired lesson plan is required before this submission can be approved.</p>
    </div>
@endif
