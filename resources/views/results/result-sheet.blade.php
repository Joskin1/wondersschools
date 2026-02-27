<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Sheet</title>
    <style>
        /* ── Reset & Base ──────────────────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.4;
            padding: 15mm 12mm 15mm 12mm;
        }

        /* ── A. HEADER ─────────────────────────────────────────────────────── */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            border-bottom: 3px double #1a365d;
            padding-bottom: 10px;
        }
        .header-left, .header-center, .header-right {
            display: table-cell;
            vertical-align: middle;
        }
        .header-left { width: 15%; text-align: center; }
        .header-center { width: 70%; text-align: center; }
        .header-right { width: 15%; text-align: center; }
        .header-logo { max-width: 70px; max-height: 70px; }
        .header-passport { max-width: 65px; max-height: 80px; border: 1px solid #ccc; }
        .school-name {
            font-size: 20px;
            font-weight: bold;
            color: #1a365d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .school-detail {
            font-size: 10px;
            color: #444;
            margin-top: 2px;
        }
        .school-motto {
            font-style: italic;
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }
        .session-term-bar {
            background: #1a365d;
            color: #fff;
            text-align: center;
            padding: 5px 0;
            font-weight: bold;
            font-size: 12px;
            margin: 8px 0;
            letter-spacing: 0.5px;
        }

        /* ── B. STUDENT INFO ───────────────────────────────────────────────── */
        .student-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .student-info td {
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            font-size: 10.5px;
        }
        .student-info .label {
            font-weight: bold;
            background: #f3f4f6;
            width: 22%;
            color: #374151;
        }
        .student-info .value { width: 28%; }

        /* ── C. RESULT TABLE ───────────────────────────────────────────────── */
        .result-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .result-table th {
            background: #1a365d;
            color: #fff;
            padding: 6px 5px;
            font-size: 10px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .result-table th:first-child { text-align: left; }
        .result-table td {
            padding: 5px;
            border: 1px solid #d1d5db;
            text-align: center;
            font-size: 10.5px;
        }
        .result-table td:first-child { text-align: left; font-weight: 500; }
        .result-table tr:nth-child(even) { background: #f9fafb; }
        .result-table .total-col { font-weight: bold; }
        .grade-a { color: #065f46; font-weight: bold; }
        .grade-b { color: #1e40af; font-weight: bold; }
        .grade-c { color: #92400e; font-weight: bold; }
        .grade-f { color: #991b1b; font-weight: bold; }

        /* ── D. SUMMARY ────────────────────────────────────────────────────── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .summary-table td {
            padding: 5px 8px;
            border: 1px solid #d1d5db;
            font-size: 10.5px;
        }
        .summary-table .label {
            font-weight: bold;
            background: #f3f4f6;
            width: 30%;
        }
        .summary-table .value { font-weight: bold; color: #1a365d; }

        /* ── E. AFFECTIVE/PSYCHOMOTOR ──────────────────────────────────────── */
        .traits-table {
            width: 48%;
            border-collapse: collapse;
            margin-bottom: 12px;
            display: inline-table;
        }
        .traits-table th {
            background: #e5e7eb;
            padding: 4px 8px;
            font-size: 10px;
            text-align: left;
            font-weight: bold;
        }
        .traits-table td {
            padding: 4px 8px;
            border: 1px solid #d1d5db;
            font-size: 10px;
        }

        /* ── F. REMARKS ────────────────────────────────────────────────────── */
        .remark-box {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            margin-bottom: 8px;
            min-height: 40px;
        }
        .remark-label {
            font-weight: bold;
            font-size: 10px;
            color: #374151;
            margin-bottom: 3px;
        }
        .remark-value { font-size: 10.5px; color: #555; }

        /* ── G. SIGNATURES ─────────────────────────────────────────────────── */
        .signatures {
            display: table;
            width: 100%;
            margin-top: 20px;
        }
        .sig-left, .sig-right {
            display: table-cell;
            width: 45%;
            text-align: center;
        }
        .sig-line {
            border-top: 1px solid #999;
            margin-top: 30px;
            padding-top: 4px;
            font-size: 10px;
            color: #555;
        }

        /* ── H. FOOTER ─────────────────────────────────────────────────────── */
        .footer {
            margin-top: 15px;
            border-top: 1px solid #d1d5db;
            padding-top: 6px;
            text-align: center;
            font-size: 9px;
            color: #888;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
    @php
        $settings = $data['settings'] ?? [];
        $student  = $data['student'] ?? [];
        $termRes  = $data['term_result'] ?? [];
    @endphp

    {{-- ══════════════════════════════════════════════════════════════════════
         A. HEADER SECTION
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="header">
        <div class="header-left">
            @if (!empty($settings['school_logo']))
                <img src="{{ public_path('storage/' . $settings['school_logo']) }}" class="header-logo" alt="Logo">
            @endif
        </div>
        <div class="header-center">
            <div class="school-name">{{ $settings['school_name'] ?? 'School Name' }}</div>
            <div class="school-detail">{{ $settings['school_address'] ?? '' }}</div>
            <div class="school-detail">
                @if (!empty($settings['school_phone']))Tel: {{ $settings['school_phone'] }}@endif
                @if (!empty($settings['school_email'])) | {{ $settings['school_email'] }}@endif
            </div>
            @if (!empty($settings['school_website']))
                <div class="school-detail">{{ $settings['school_website'] }}</div>
            @endif
            @if (!empty($settings['school_motto']))
                <div class="school-motto">"{{ $settings['school_motto'] }}"</div>
            @endif
        </div>
        <div class="header-right">
            {{-- Student passport photo placeholder --}}
            <div style="width:65px;height:80px;border:1px dashed #ccc;display:inline-block;line-height:80px;font-size:8px;color:#aaa;">Photo</div>
        </div>
    </div>

    <div class="session-term-bar">
        {{ $data['term_name'] ?? '' }} &mdash; {{ $data['session_name'] ?? '' }} ACADEMIC SESSION
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         B. STUDENT INFORMATION GRID
    ══════════════════════════════════════════════════════════════════════ --}}
    <table class="student-info">
        <tr>
            <td class="label">Student Name</td>
            <td class="value">{{ $student['name'] ?? '-' }}</td>
            <td class="label">Number in Class</td>
            <td class="value">{{ $data['class_size'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Class</td>
            <td class="value">{{ $data['classroom'] ?? '-' }}</td>
            <td class="label">Overall Position</td>
            <td class="value">{{ $termRes['overall_position'] ?? '-' }} of {{ $data['class_size'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Gender</td>
            <td class="value">{{ ucfirst($student['gender'] ?? '-') }}</td>
            <td class="label">Average</td>
            <td class="value">{{ $termRes['average'] ?? '-' }}%</td>
        </tr>
        <tr>
            <td class="label">Date of Birth</td>
            <td class="value">{{ $student['dob'] ?? '-' }}</td>
            <td class="label">Next Term Begins</td>
            <td class="value">—</td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════════════════════════════
         C. RESULT TABLE
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="section-title">Academic Performance</div>
    <table class="result-table">
        <thead>
            <tr>
                <th style="text-align:left;min-width:120px;">Subject</th>
                @foreach (($data['score_heads'] ?? []) as $sh)
                    <th>{{ $sh['name'] }}</th>
                @endforeach
                <th>Total</th>
                <th>Grade</th>
                <th>Pos</th>
                <th>Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach (($data['subjects'] ?? []) as $row)
                <tr>
                    <td>{{ $row['subject'] }}</td>
                    @foreach (($data['score_heads'] ?? []) as $sh)
                        <td>{{ $row['scores'][$sh['id']] ?? '-' }}</td>
                    @endforeach
                    <td class="total-col">{{ $row['total'] }}</td>
                    <td class="{{ $row['grade'] === 'A' ? 'grade-a' : ($row['grade'] === 'B' ? 'grade-b' : ($row['grade'] === 'F' ? 'grade-f' : 'grade-c')) }}">
                        {{ $row['grade'] }}
                    </td>
                    <td>{{ $row['position'] }}</td>
                    <td style="text-align:left;font-size:9.5px;">{{ $row['remark'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ══════════════════════════════════════════════════════════════════════
         D. SUMMARY PANEL
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="section-title">Summary</div>
    <table class="summary-table">
        <tr>
            <td class="label">Total Obtainable Marks</td>
            <td class="value">{{ $data['total_obtainable'] ?? '-' }}</td>
            <td class="label">Grand Total</td>
            <td class="value">{{ $termRes['grand_total'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Average</td>
            <td class="value">{{ $termRes['average'] ?? '-' }}%</td>
            <td class="label">Overall Grade</td>
            <td class="value">{{ $termRes['grade'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Overall Position</td>
            <td class="value">{{ $termRes['overall_position'] ?? '-' }} of {{ $data['class_size'] ?? '-' }}</td>
            <td class="label">Number of Subjects</td>
            <td class="value">{{ $termRes['subjects_count'] ?? '-' }}</td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════════════════════════════
         E. AFFECTIVE & PSYCHOMOTOR (Future-Ready)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="section-title">Affective Domain &amp; Psychomotor Skills</div>
    <table class="traits-table">
        <thead>
            <tr><th>Trait</th><th>Rating</th></tr>
        </thead>
        <tbody>
            <tr><td>Punctuality</td><td>—</td></tr>
            <tr><td>Neatness</td><td>—</td></tr>
            <tr><td>Attentiveness</td><td>—</td></tr>
            <tr><td>Leadership</td><td>—</td></tr>
        </tbody>
    </table>
    <table class="traits-table" style="margin-left:4%;">
        <thead>
            <tr><th>Skill</th><th>Rating</th></tr>
        </thead>
        <tbody>
            <tr><td>Handwriting</td><td>—</td></tr>
            <tr><td>Games/Sports</td><td>—</td></tr>
            <tr><td>Craft</td><td>—</td></tr>
            <tr><td>Verbal Fluency</td><td>—</td></tr>
        </tbody>
    </table>

    {{-- ══════════════════════════════════════════════════════════════════════
         F. REMARKS
    ══════════════════════════════════════════════════════════════════════ --}}
    <div style="margin-top:12px;">
        <div class="remark-box">
            <div class="remark-label">Class Teacher's Comment:</div>
            <div class="remark-value">—</div>
        </div>
        <div class="remark-box">
            <div class="remark-label">Principal's Comment:</div>
            <div class="remark-value">—</div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         G. SIGNATURES
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="signatures">
        <div class="sig-left">
            <div class="sig-line">Class Teacher's Signature</div>
        </div>
        <div class="sig-right">
            <div class="sig-line">Principal's Signature</div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         H. FOOTER
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="footer">
        <div style="margin-bottom:4px;">🏫 School Stamp</div>
        <div>Result generated on: {{ now()->format('d M Y, h:i A') }}</div>
    </div>
</body>
</html>
