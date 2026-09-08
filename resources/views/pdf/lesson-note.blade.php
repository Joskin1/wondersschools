<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Lesson Note</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @page {
            margin: 18mm 15mm 18mm 15mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #222222;
            line-height: 1.5;
            position: relative;
        }

        /* ── Watermark ────────────────────────────────────────────────────────── */
        .watermark {
            position: fixed;
            top: 35%;
            left: 5%;
            width: 90%;
            text-align: center;
            opacity: 0.06;
            transform: rotate(-35deg);
            font-size: 52px;
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
            color: #555555;
            margin-bottom: 2px;
        }

        .school-address {
            font-size: 9px;
            color: #777777;
        }

        /* ── Meta Banner ──────────────────────────────────────────────────────── */
        .meta-bar {
            background-color: {{ $brand_color }};
            color: #ffffff;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 12px;
            border-radius: 3px;
        }

        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
        }

        .meta-grid td {
            padding: 6px 10px;
            font-size: 10px;
            border-bottom: 1px solid #edf2f7;
        }

        .meta-grid td.label {
            font-weight: bold;
            color: #4a5568;
            width: 20%;
        }

        .meta-grid td.val {
            color: #1a202c;
            width: 30%;
        }

        /* ── Topic Banner ─────────────────────────────────────────────────────── */
        .topic-box {
            background-color: #f1f5f9;
            border-left: 4px solid {{ $brand_color }};
            padding: 8px 12px;
            margin-bottom: 14px;
        }

        .topic-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
        }

        /* ── Section Titles ───────────────────────────────────────────────────── */
        .section-heading {
            font-size: 12px;
            font-weight: bold;
            color: {{ $brand_color }};
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .objectives-list {
            padding-left: 20px;
            margin-bottom: 12px;
        }

        .objectives-list li {
            font-size: 10.5px;
            color: #334155;
            margin-bottom: 4px;
        }

        /* ── Content ──────────────────────────────────────────────────────────── */
        .lesson-content {
            font-size: 10.5px;
            color: #334155;
            line-height: 1.6;
            margin-bottom: 14px;
        }

        .lesson-content p {
            margin-bottom: 8px;
        }

        .lesson-content h1, .lesson-content h2, .lesson-content h3 {
            color: #1e293b;
            margin-top: 10px;
            margin-bottom: 4px;
        }

        .lesson-content ul, .lesson-content ol {
            padding-left: 20px;
            margin-bottom: 8px;
        }

        /* ── Footer ───────────────────────────────────────────────────────────── */
        .footer {
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            font-size: 9px;
            color: #94a3b8;
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
        @if(!empty($school_logo))
            <div class="header-logo-cell">
                <img src="{{ public_path('storage/' . $school_logo) }}" alt="Logo" style="max-height: 55px; max-width: 55px;">
            </div>
        @endif
        <div class="header-text-cell" style="{{ empty($school_logo) ? 'padding-right: 0; width: 100%;' : '' }}">
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
            <td class="label">Academic Session:</td>
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
        <div class="topic-title">{{ $title }}</div>
    </div>

    {{-- Learning Objectives (if present) --}}
    @if(!empty($objectives) && count($objectives) > 0)
        <div class="section-heading">Learning Objectives</div>
        <p style="font-size: 10px; font-style: italic; color: #64748b; margin-bottom: 6px;">
            At the end of the lesson, students should be able to:
        </p>
        <ol class="objectives-list">
            @foreach((array)$objectives as $objective)
                <li>{{ is_array($objective) ? ($objective['objective'] ?? '') : $objective }}</li>
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

    {{-- Footer --}}
    <div class="footer">
        Generated from {{ $school_name }} Student Portal &bull; Approved Academic Lesson Note
    </div>

</body>
</html>
