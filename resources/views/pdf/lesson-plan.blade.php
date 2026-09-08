<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $plan->title ?: 'Lesson Plan' }}</title>
    <style>
        * { box-sizing: border-box; }
        @page { margin: 18mm 15mm; }
        body { color: #243044; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; line-height: 1.55; position: relative; }
        .watermark { color: #000; font-size: 48px; font-weight: bold; left: 5%; opacity: .055; position: fixed; text-align: center; text-transform: uppercase; top: 40%; transform: rotate(-35deg); width: 90%; z-index: -1000; }
        .header { border-bottom: 2px solid {{ $brand_color }}; display: table; margin-bottom: 14px; padding-bottom: 10px; width: 100%; }
        .logo-cell, .school-cell { display: table-cell; vertical-align: middle; }
        .logo-cell { text-align: center; width: 15%; }
        .school-cell { padding-left: 8px; text-align: center; width: 85%; }
        .school-name { color: {{ $brand_color }}; font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .school-motto { color: #64748b; font-style: italic; margin-top: 2px; }
        .school-address { color: #64748b; font-size: 9px; margin-top: 2px; }
        .bar { background: {{ $brand_color }}; color: #fff; font-size: 11px; font-weight: bold; margin-bottom: 10px; padding: 6px 10px; }
        .meta { background: #f8fafc; border: 1px solid #dbe3ed; border-collapse: collapse; margin-bottom: 14px; width: 100%; }
        .meta td { border-bottom: 1px solid #e8edf3; padding: 5px 8px; }
        .meta .label { color: #475569; font-weight: bold; width: 18%; }
        .title { border-left: 4px solid {{ $brand_color }}; color: #172033; font-size: 15px; font-weight: bold; margin-bottom: 16px; padding: 8px 12px; }
        .section { border-bottom: 1px solid #dbe3ed; margin-bottom: 12px; padding-bottom: 10px; }
        .heading { border-bottom: 1px solid #cbd5e1; color: {{ $brand_color }}; font-size: 11px; font-weight: bold; margin-bottom: 6px; padding-bottom: 3px; text-transform: uppercase; }
        .content { color: #334155; }
        .content p { margin: 0 0 7px; }
        .content h1, .content h2, .content h3, .content h4 { color: #172033; margin: 8px 0 4px; }
        .content strong, .content b { font-weight: bold; }
        .content em, .content i { font-style: italic; }
        .content ul, .content ol { margin: 4px 0 8px; padding-left: 18px; }
        .content li { margin-bottom: 3px; }
        .items { margin: 4px 0 8px; padding-left: 20px; }
        .items li { margin-bottom: 3px; }
        .tags { color: #334155; }
        .tag { background: #f1f5f9; border: 1px solid #cbd5e1; display: inline-block; margin: 0 4px 4px 0; padding: 3px 6px; }
        .footer { border-top: 1px solid #dbe3ed; color: #94a3b8; font-size: 8px; margin-top: 18px; padding-top: 7px; text-align: center; }
    </style>
</head>
<body>
    @php
        $rich = fn ($value) => $value !== null && $value !== ''
            ? ($value !== strip_tags((string) $value) ? $value : nl2br(e((string) $value)))
            : '';
        $objectives = (array) $plan->learning_objectives;
        $steps = (array) $plan->presentation_steps;
        $questions = (array) $plan->evaluation_questions;
        $vocabulary = $plan->key_vocabulary ? array_filter(array_map('trim', explode(',', $plan->key_vocabulary))) : [];
        $references = $plan->referenceMaterials->pluck('name')->all();
        $instructionalMaterials = $plan->instructionalMaterials->pluck('name')->all();
        $teachingMethods = $plan->teachingMethods->pluck('name')->all();
    @endphp

    <div class="watermark">{{ $school_name }}</div>

    <div class="header">
        @if($school_logo)
            <div class="logo-cell"><img src="{{ public_path('storage/' . $school_logo) }}" alt="Logo" style="max-height: 55px; max-width: 55px;"></div>
        @endif
        <div class="school-cell" style="{{ $school_logo ? '' : 'width: 100%;' }}">
            <div class="school-name">{{ $school_name }}</div>
            @if($school_motto)<div class="school-motto">&ldquo;{{ $school_motto }}&rdquo;</div>@endif
            @if($school_address)<div class="school-address">{{ $school_address }}</div>@endif
        </div>
    </div>

    <div class="bar">APPROVED LESSON PLAN &mdash; WEEK {{ $plan->week_number }}</div>
    <table class="meta">
        <tr><td class="label">Subject:</td><td>{{ $plan->subject?->name ?? '—' }}</td><td class="label">Class:</td><td>{{ $plan->classroom?->name ?? '—' }}</td></tr>
        <tr><td class="label">Session:</td><td>{{ $plan->session?->name ?? '—' }}</td><td class="label">Term:</td><td>{{ $plan->term?->name ?? '—' }}</td></tr>
        <tr><td class="label">Teacher:</td><td>{{ $plan->teacher?->name ?? '—' }}</td><td class="label">Duration:</td><td>{{ $plan->time ?: '—' }}</td></tr>
    </table>

    <div class="title">{{ $plan->title ?: ($plan->subject?->name . ' Lesson Plan') }}</div>

    @if($objectives)
        <div class="section"><div class="heading">Learning Objectives</div><em>At the end of the lesson, students should be able to:</em><ol class="items">@foreach($objectives as $item)<li>{{ is_array($item) ? ($item['objective'] ?? '') : $item }}</li>@endforeach</ol></div>
    @endif
    @if($vocabulary)<div class="section"><div class="heading">Key Vocabulary</div><div class="tags">@foreach($vocabulary as $item)<span class="tag">{{ $item }}</span>@endforeach</div></div>@endif
    @foreach(['Reference Materials' => $references, 'Instructional Materials' => $instructionalMaterials, 'Teaching Method' => $teachingMethods] as $heading => $items)
        @if($items)<div class="section"><div class="heading">{{ $heading }}</div><div class="tags">@foreach($items as $item)<span class="tag">{{ $item }}</span>@endforeach</div></div>@endif
    @endforeach
    @foreach([
        'Background / Prior Knowledge' => $plan->prior_knowledge,
        'Content' => $plan->content,
        'Strategies and Activities' => $plan->strategies_activities,
        'Conclusion' => $plan->conclusion,
        'Assignment / Homework' => $plan->assignment,
    ] as $heading => $value)
        @if($value)<div class="section"><div class="heading">{{ $heading }}</div><div class="content">{!! $rich($value) !!}</div></div>@endif
    @endforeach
    @if($steps)<div class="section"><div class="heading">Presentation Steps</div><ol class="items">@foreach($steps as $item)<li>{{ is_array($item) ? ($item['step'] ?? '') : $item }}</li>@endforeach</ol></div>@endif
    @if($questions)<div class="section"><div class="heading">Assessment / Evaluation</div><ol class="items">@foreach($questions as $item)<li>{{ is_array($item) ? ($item['question'] ?? '') : $item }}</li>@endforeach</ol></div>@endif
    @if($plan->admin_comment)<div class="section"><div class="heading">Admin Feedback</div><div class="content">{{ $plan->admin_comment }}</div></div>@endif

    <div class="footer">{{ $school_name }} &bull; Approved Academic Lesson Plan</div>
</body>
</html>
