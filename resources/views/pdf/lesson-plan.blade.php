<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $plan->topic ?: $plan->title ?: 'Lesson Plan' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @page {
            margin: 16mm 14mm 16mm 14mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            line-height: 1.6;
            position: relative;
        }

        /* ── Watermark ────────────────────────────────────────────────────────── */
        .watermark {
            position: fixed;
            top: 35%;
            left: 5%;
            width: 90%;
            text-align: center;
            opacity: 0.05;
            transform: rotate(-35deg);
            font-size: 48px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 4px;
            z-index: -1000;
        }

        /* ── Header ───────────────────────────────────────────────────────────── */
        .header {
            width: 100%;
            border-bottom: 2px solid {{ $brand_color }};
            padding-bottom: 12px;
            margin-bottom: 14px;
            display: table;
        }

        .header-logo-cell {
            display: table-cell;
            width: 15%;
            vertical-align: middle;
            text-align: center;
        }

        .header-text-cell {
            display: table-cell;
            width: 85%;
            vertical-align: middle;
            text-align: center;
            padding-right: 15%;
        }

        .school-name {
            font-size: 18px;
            font-weight: bold;
            color: {{ $brand_color }};
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .school-motto {
            font-size: 10px;
            font-style: italic;
            color: #4b5563;
            margin-bottom: 2px;
        }

        .school-address {
            font-size: 9px;
            color: #6b7280;
        }

        /* ── Meta Banner ──────────────────────────────────────────────────────── */
        .meta-bar {
            background-color: {{ $brand_color }};
            color: #ffffff;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 10px;
            border-radius: 3px;
        }

        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            background-color: #f8fafc;
            border: 1px solid #d1d5db;
        }

        .meta-grid td {
            padding: 6px 10px;
            font-size: 10.5px;
            border-bottom: 1px solid #e5e7eb;
        }

        .meta-grid td.label {
            font-weight: bold;
            color: #4b5563;
            width: 18%;
        }

        .meta-grid td.val {
            color: #111827;
            width: 32%;
        }

        /* ── Topic Banner ─────────────────────────────────────────────────────── */
        .topic-box {
            background-color: #f3f4f6;
            border-left: 4px solid {{ $brand_color }};
            padding: 10px 14px;
            margin-bottom: 14px;
        }

        .topic-label {
            font-size: 9px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .topic-title {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
        }

        .topic-sub {
            font-size: 11px;
            color: #4b5563;
            margin-top: 4px;
        }

        /* ── Sections ─────────────────────────────────────────────────────────── */
        .section-block {
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .section-heading {
            font-size: 11.5px;
            font-weight: bold;
            color: {{ $brand_color }};
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .section-intro {
            font-size: 10.5px;
            font-style: italic;
            color: #4b5563;
            margin-bottom: 6px;
        }

        .list-items {
            padding-left: 22px;
            margin-bottom: 8px;
        }

        .list-items li {
            font-size: 11px;
            color: #374151;
            margin-bottom: 4px;
            line-height: 1.5;
        }

        .tag-list {
            margin-bottom: 8px;
        }

        .tag-item {
            display: inline-block;
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 3px 7px;
            font-size: 10px;
            color: #334155;
            margin-right: 4px;
            margin-bottom: 4px;
        }

        .rich-text {
            font-size: 11px;
            color: #374151;
            line-height: 1.65;
            margin-bottom: 8px;
        }

        .rich-text p {
            margin-bottom: 6px;
        }

        .rich-text ul, .rich-text ol {
            padding-left: 22px;
            margin-bottom: 6px;
        }

        .feedback-box {
            background-color: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 4px;
            padding: 10px 14px;
            margin-top: 14px;
            margin-bottom: 14px;
        }

        .feedback-title {
            font-size: 10px;
            font-weight: bold;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .feedback-body {
            font-size: 11px;
            color: #78350f;
        }

        /* ── Footer ───────────────────────────────────────────────────────────── */
        .footer {
            margin-top: 24px;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

    @php
        $rich = function ($value) {
            if ($value === null || $value === '') return '';
            $str = (string) $value;
            return $str !== strip_tags($str) ? $str : nl2br(e($str));
        };

        $vocabList = $plan->key_vocabulary
            ? array_filter(array_map('trim', explode(',', $plan->key_vocabulary)))
            : [];

        $references = $plan->referenceMaterials ? $plan->referenceMaterials->pluck('name')->all() : [];
        $instructionalMaterials = $plan->instructionalMaterials ? $plan->instructionalMaterials->pluck('name')->all() : [];
        $teachingMethods = $plan->teachingMethods ? $plan->teachingMethods->pluck('name')->all() : [];

        $objectives = (array) ($plan->learning_objectives ?? []);
        $steps = (array) ($plan->presentation_steps ?? []);
        $questions = (array) ($plan->evaluation_questions ?? []);
    @endphp

    {{-- Diagonal watermark --}}
    <div class="watermark">
        {{ $school_name }}
    </div>

    {{-- School Header --}}
    <div class="header">
        @if(!empty($school_logo) && file_exists(public_path('storage/' . $school_logo)))
            <div class="header-logo-cell">
                <img src="{{ public_path('storage/' . $school_logo) }}" alt="Logo" style="max-height: 55px; max-width: 55px;">
            </div>
        @endif
        <div class="header-text-cell" style="{{ empty($school_logo) || !file_exists(public_path('storage/' . $school_logo)) ? 'padding-right: 0; width: 100%;' : '' }}">
            <div class="school-name">{{ $school_name }}</div>
            @if(!empty($school_motto))
                <div class="school-motto">&ldquo;{{ $school_motto }}&rdquo;</div>
            @endif
            @if(!empty($school_address))
                <div class="school-address">{{ $school_address }}</div>
            @endif
        </div>
    </div>

    {{-- Academic Context Bar --}}
    <div class="meta-bar">
        LESSON PLAN &mdash; WEEK {{ $plan->week_number }}
    </div>

    <table class="meta-grid">
        <tr>
            <td class="label">Subject:</td>
            <td class="val">{{ $plan->subject?->name ?? '—' }}</td>
            <td class="label">Class:</td>
            <td class="val">{{ $plan->classroom?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Session:</td>
            <td class="val">{{ $plan->session?->name ?? '—' }}</td>
            <td class="label">Term:</td>
            <td class="val">{{ $plan->term?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Teacher:</td>
            <td class="val">{{ $plan->teacher?->name ?? '—' }}</td>
            <td class="label">Duration:</td>
            <td class="val">{{ $plan->time ?: '—' }}</td>
        </tr>
    </table>

    {{-- Topic Box --}}
    <div class="topic-box">
        <div class="topic-label">Lesson Topic</div>
        <div class="topic-title">{{ $plan->topic ?: $plan->title ?: ($plan->subject?->name . ' Lesson Plan') }}</div>
        @if(!empty($plan->sub_topic))
            <div class="topic-sub"><strong>Sub-Topic:</strong> {{ $plan->sub_topic }}</div>
        @endif
    </div>

    {{-- 1. Learning Objectives --}}
    @if(!empty($objectives) && count(array_filter($objectives)) > 0)
        <div class="section-heading">1. Learning Objectives</div>
        <p class="section-intro">At the end of the lesson, students should be able to:</p>
        <ol class="list-items">
            @foreach($objectives as $item)
                @php($itemText = is_array($item) ? ($item['objective'] ?? '') : $item)
                @if(!empty(trim($itemText)))
                    <li>{{ $itemText }}</li>
                @endif
            @endforeach
        </ol>
    @endif

    {{-- 2. Key Vocabulary --}}
    @if(!empty($vocabList) && count($vocabList) > 0)
        <div class="section-heading">2. Key Vocabulary</div>
        <div class="tag-list">
            @foreach($vocabList as $word)
                <span class="tag-item">{{ $word }}</span>
            @endforeach
        </div>
    @endif

    {{-- 3. Reference Materials --}}
    @if(!empty($references) && count($references) > 0)
        <div class="section-heading">3. Reference Materials</div>
        <div class="tag-list">
            @foreach($references as $item)
                <span class="tag-item">{{ $item }}</span>
            @endforeach
        </div>
    @endif

    {{-- 4. Instructional Materials --}}
    @if(!empty($instructionalMaterials) && count($instructionalMaterials) > 0)
        <div class="section-heading">4. Instructional Materials</div>
        <div class="tag-list">
            @foreach($instructionalMaterials as $item)
                <span class="tag-item">{{ $item }}</span>
            @endforeach
        </div>
    @endif

    {{-- 5. Background / Prior Knowledge --}}
    @if(!empty($plan->prior_knowledge))
        <div class="section-heading">5. Background / Prior Knowledge</div>
        <div class="rich-text">{!! $rich($plan->prior_knowledge) !!}</div>
    @endif

    {{-- 6. Lesson Content --}}
    @if(!empty($plan->content))
        <div class="section-heading">6. Content Outline</div>
        <div class="rich-text">{!! $rich($plan->content) !!}</div>
    @endif

    {{-- 7. Teaching Method --}}
    @if(!empty($teachingMethods) && count($teachingMethods) > 0)
        <div class="section-heading">7. Teaching Method</div>
        <div class="tag-list">
            @foreach($teachingMethods as $item)
                <span class="tag-item">{{ $item }}</span>
            @endforeach
        </div>
    @endif

    {{-- 8. Presentation Steps --}}
    @if(!empty($steps) && count(array_filter($steps)) > 0)
        <div class="section-heading">8. Presentation Steps</div>
        <ol class="list-items">
            @foreach($steps as $item)
                @php($stepText = is_array($item) ? ($item['step'] ?? '') : $item)
                @if(!empty(trim($stepText)))
                    <li>{{ $stepText }}</li>
                @endif
            @endforeach
        </ol>
    @endif

    {{-- 9. Strategies & Activities --}}
    @if(!empty($plan->strategies_activities))
        <div class="section-heading">9. Strategies and Activities</div>
        <div class="rich-text">{!! $rich($plan->strategies_activities) !!}</div>
    @endif

    {{-- 10. Assessment / Evaluation --}}
    @if(!empty($questions) && count(array_filter($questions)) > 0)
        <div class="section-heading">10. Assessment / Evaluation Questions</div>
        <p class="section-intro">The teacher evaluates students by asking the following questions:</p>
        <ol class="list-items">
            @foreach($questions as $item)
                @php($qText = is_array($item) ? ($item['question'] ?? '') : $item)
                @if(!empty(trim($qText)))
                    <li>{{ $qText }}</li>
                @endif
            @endforeach
        </ol>
    @endif

    {{-- 11. Conclusion --}}
    @if(!empty($plan->conclusion))
        <div class="section-heading">11. Conclusion</div>
        <div class="rich-text">{!! $rich($plan->conclusion) !!}</div>
    @endif

    {{-- 12. Assignment / Homework --}}
    @if(!empty($plan->assignment))
        <div class="section-heading">12. Assignment / Homework</div>
        <div class="rich-text">{!! $rich($plan->assignment) !!}</div>
    @endif

    {{-- Admin Feedback (if present) --}}
    @if(!empty($plan->admin_comment))
        <div class="feedback-box">
            <div class="feedback-title">Admin Feedback</div>
            <div class="feedback-body">{{ $plan->admin_comment }}</div>
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        {{ $school_name }} &bull; Academic Lesson Plan &bull; Week {{ $plan->week_number }}
    </div>

</body>
</html>
