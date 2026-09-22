<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Lesson Note</title>
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

        /* ── Section Titles ───────────────────────────────────────────────────── */
        .section-heading {
            font-size: 12px;
            font-weight: bold;
            color: {{ $brand_color }};
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 16px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .objectives-list {
            padding-left: 22px;
            margin-bottom: 14px;
        }

        .objectives-list li {
            font-size: 11px;
            color: #374151;
            margin-bottom: 5px;
            line-height: 1.5;
        }

        /* ── Content ──────────────────────────────────────────────────────────── */
        .lesson-content {
            font-size: 11px;
            color: #374151;
            line-height: 1.7;
            margin-bottom: 16px;
        }

        .lesson-content p {
            margin-bottom: 10px;
        }

        .lesson-content h1 {
            font-size: 15px;
            font-weight: bold;
            color: #111827;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        .lesson-content h2 {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            margin-top: 12px;
            margin-bottom: 5px;
        }

        .lesson-content h3, .lesson-content h4 {
            font-size: 12px;
            font-weight: bold;
            color: #111827;
            margin-top: 10px;
            margin-bottom: 4px;
        }

        .lesson-content ul, .lesson-content ol {
            padding-left: 22px;
            margin-bottom: 10px;
        }

        .lesson-content li {
            margin-bottom: 4px;
        }

        .lesson-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
        }

        .lesson-content th, .lesson-content td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            font-size: 10.5px;
            text-align: left;
        }

        .lesson-content th {
            background-color: #f3f4f6;
            font-weight: bold;
        }

        .lesson-content blockquote {
            border-left: 3px solid #9ca3af;
            padding-left: 12px;
            margin: 10px 0;
            font-style: italic;
            color: #4b5563;
        }

        /* ── Images ───────────────────────────────────────────────────────────── */
        .diagram-grid {
            margin-top: 12px;
            margin-bottom: 16px;
        }

        .diagram-item {
            display: inline-block;
            width: 48%;
            margin-right: 2%;
            margin-bottom: 10px;
            vertical-align: top;
            border: 1px solid #d1d5db;
            padding: 6px;
            background-color: #f9fafb;
            text-align: center;
        }

        .diagram-item img {
            max-width: 100%;
            max-height: 180px;
        }

        .diagram-caption {
            font-size: 9.5px;
            color: #6b7280;
            margin-top: 4px;
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
        LESSON NOTE &mdash; WEEK {{ $lesson_note->week_number }}
    </div>

    <table class="meta-grid">
        <tr>
            <td class="label">Subject:</td>
            <td class="val">{{ $lesson_note->subject->name ?? '—' }}</td>
            <td class="label">Class:</td>
            <td class="val">{{ $lesson_note->classroom->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Session:</td>
            <td class="val">{{ $lesson_note->session->name ?? '—' }}</td>
            <td class="label">Term:</td>
            <td class="val">{{ $lesson_note->term->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Teacher:</td>
            <td class="val">{{ $lesson_note->teacher->name ?? '—' }}</td>
            <td class="label">Week:</td>
            <td class="val">Week {{ $lesson_note->week_number }}</td>
        </tr>
    </table>

    {{-- Topic Box --}}
    <div class="topic-box">
        <div class="topic-label">Lesson Topic</div>
        <div class="topic-title">{{ $title }}</div>
    </div>

    {{-- Learning Objectives (if present) --}}
    @if(!empty($objectives) && count($objectives) > 0)
        <div class="section-heading">Learning Objectives</div>
        <p style="font-size: 10.5px; font-style: italic; color: #4b5563; margin-bottom: 6px;">
            At the end of the lesson, students should be able to:
        </p>
        <ol class="objectives-list">
            @foreach((array)$objectives as $objective)
                @php($objText = is_array($objective) ? ($objective['objective'] ?? '') : $objective)
                @if(!empty(trim($objText)))
                    <li>{{ $objText }}</li>
                @endif
            @endforeach
        </ol>
    @endif

    {{-- Lesson Body --}}
    <div class="section-heading">Lesson Content</div>
    <div class="lesson-content">
        @if(!empty($content))
            {!! $content !!}
        @else
            <p>Please refer to the attached document for this lesson note.</p>
        @endif
    </div>

    {{-- Optional Attached Diagrams & Images --}}
    @if(!empty($image_urls) && count($image_urls) > 0)
        <div class="section-heading">Attached Diagrams & Illustrations</div>
        <div class="diagram-grid">
            @foreach($image_urls as $idx => $imgUrl)
                <div class="diagram-item">
                    <img src="{{ $imgUrl }}" alt="Figure {{ $idx + 1 }}">
                    <div class="diagram-caption">Figure {{ $idx + 1 }}</div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        {{ $school_name }} &bull; Academic Lesson Note &bull; Week {{ $lesson_note->week_number }}
    </div>

</body>
</html>
