<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terminal Academic Report Sheet</title>
    @php
        $brandColor = $data['settings']['primary_color'] ?? config('app.tenant_primary_color', '#1e3a8a');
        $settings   = $data['settings'] ?? [];
        $student    = $data['student'] ?? [];
        $termRes    = $data['term_result'] ?? [];
        $opened     = $data['times_school_opened'] ?? null;
        $present    = $termRes['times_present'] ?? null;
        $absent     = ($opened !== null && $present !== null) ? max(0, (int)$opened - (int)$present) : null;
        $attRate    = ($opened && $present !== null) ? round(($present / $opened) * 100, 1) : null;
    @endphp
    <style>
        /* ── Reset & Document Base ─────────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page {
            size: a4 portrait;
            margin: 14mm 14mm 14mm 14mm;
        }
        body {
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            color: #1e293b;
            line-height: 1.3;
            background: #ffffff;
        }

        /* ── Header Section ────────────────────────────────────────────────── */
        .school-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .header-logo-cell {
            width: 15%;
            text-align: center;
            vertical-align: middle;
        }
        .header-center-cell {
            width: 70%;
            text-align: center;
            vertical-align: middle;
            padding: 0 6px;
        }
        .header-photo-cell {
            width: 15%;
            text-align: center;
            vertical-align: middle;
        }
        .school-logo {
            max-width: 65px;
            max-height: 65px;
        }
        .student-photo {
            width: 62px;
            height: 74px;
            object-fit: cover;
            border: 2px solid {{ $brandColor }};
            border-radius: 4px;
        }
        .student-photo-placeholder {
            width: 62px;
            height: 74px;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            display: inline-block;
            line-height: 74px;
            font-size: 8.5px;
            color: #94a3b8;
            text-align: center;
            background: #f8fafc;
        }
        .school-name {
            font-size: 16.5px;
            font-weight: 900;
            color: {{ $brandColor }};
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 2px;
        }
        .school-address {
            font-size: 8.5px;
            color: #475569;
            font-weight: 600;
            margin-bottom: 1.5px;
        }
        .school-contacts {
            font-size: 8px;
            color: #64748b;
            margin-bottom: 2.5px;
        }
        .school-motto {
            font-size: 8.5px;
            font-style: italic;
            font-weight: 700;
            color: {{ $brandColor }};
            margin-bottom: 3.5px;
        }
        .report-title-badge {
            display: inline-block;
            background: {{ $brandColor }};
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 2.5px 16px;
            border-radius: 20px;
        }

        /* ── Term Ribbon ───────────────────────────────────────────────────── */
        .term-ribbon {
            background: #f1f5f9;
            border-top: 1.5px solid {{ $brandColor }};
            border-bottom: 1.5px solid {{ $brandColor }};
            text-align: center;
            padding: 3px 0;
            font-size: 9.5px;
            font-weight: 800;
            color: {{ $brandColor }};
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        /* ── Student Bio-Data & Attendance Grid ────────────────────────────── */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .info-table td {
            padding: 3px 5px;
            border: 1px solid #cbd5e1;
            font-size: 8.5px;
            vertical-align: middle;
        }
        .info-table .lbl {
            font-weight: 800;
            color: #475569;
            background: #f8fafc;
            width: 17%;
            text-transform: uppercase;
            font-size: 8px;
            letter-spacing: 0.3px;
        }
        .info-table .val {
            font-weight: 700;
            color: #0f172a;
            width: 33%;
        }
        .badge-dept {
            display: inline-block;
            background: rgba(79, 70, 229, 0.12);
            color: #4338ca;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: 800;
            font-size: 8.5px;
        }
        .badge-adm {
            display: inline-block;
            background: #e2e8f0;
            color: #1e293b;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: 800;
            font-size: 8.5px;
        }

        /* ── Section Header ────────────────────────────────────────────────── */
        .section-header {
            font-size: 8.5px;
            font-weight: 800;
            color: {{ $brandColor }};
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2.5px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 1.5px;
        }

        /* ── Academic Performance Table ────────────────────────────────────── */
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .results-table th {
            background: {{ $brandColor }};
            color: #ffffff;
            font-size: 8px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 3.5px 3px;
            text-align: center;
            border: 1px solid {{ $brandColor }};
        }
        .results-table th.th-left { text-align: left; padding-left: 5px; }
        .results-table td {
            padding: 2.8px 3px;
            border: 1px solid #cbd5e1;
            text-align: center;
            font-size: 8.5px;
        }
        .results-table td.td-left {
            text-align: left;
            padding-left: 5px;
            font-weight: 700;
            color: #0f172a;
        }
        .results-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .score-total {
            font-weight: 800;
            color: #0f172a;
        }
        .grade-a { color: #047857; font-weight: 900; }
        .grade-b { color: #1d4ed8; font-weight: 900; }
        .grade-c { color: #b45309; font-weight: 900; }
        .grade-d, .grade-e { color: #c2410c; font-weight: 900; }
        .grade-f { color: #b91c1c; font-weight: 900; }
        .remark-cell {
            text-align: left !important;
            padding-left: 5px !important;
            font-size: 8px !important;
            color: #475569;
        }

        /* ── Academic Summary Statistics ───────────────────────────────────── */
        .summary-grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .summary-grid-table td {
            padding: 3px 5px;
            border: 1px solid #cbd5e1;
            font-size: 8.5px;
        }
        .summary-grid-table .s-lbl {
            font-weight: 800;
            color: #475569;
            background: #f8fafc;
            width: 25%;
            font-size: 8px;
            text-transform: uppercase;
        }
        .summary-grid-table .s-val {
            font-weight: 800;
            color: {{ $brandColor }};
            width: 25%;
            font-size: 9px;
        }

        /* ── Affective & Psychomotor Traits ────────────────────────────────── */
        .traits-container {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .traits-col {
            width: 50%;
            vertical-align: top;
        }
        .traits-col:first-child { padding-right: 3px; }
        .traits-col:last-child { padding-left: 3px; }
        .trait-table {
            width: 100%;
            border-collapse: collapse;
        }
        .trait-table th {
            background: #e2e8f0;
            color: #334155;
            font-size: 7.5px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 2.5px 5px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        .trait-table th.th-ctr { text-align: center; width: 35%; }
        .trait-table td {
            padding: 2.2px 5px;
            border: 1px solid #cbd5e1;
            font-size: 8px;
        }
        .trait-table td.td-ctr { text-align: center; font-weight: 800; color: #0f172a; }
        .trait-table tr:nth-child(even) td { background: #f8fafc; }

        /* ── Remarks & Resumption ──────────────────────────────────────────── */
        .remark-card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 3.5px 6px;
            margin-bottom: 3.5px;
            background: #ffffff;
            page-break-inside: avoid;
        }
        .remark-title {
            font-size: 7.5px;
            font-weight: 800;
            color: {{ $brandColor }};
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 1.5px;
        }
        .remark-text {
            font-size: 8.5px;
            font-style: italic;
            color: #1e293b;
            min-height: 12px;
        }

        /* ── Next Term Resumption Ribbon ───────────────────────────────────── */
        .resumption-banner {
            background: #f8fafc;
            border: 1.2px dashed {{ $brandColor }};
            border-radius: 4px;
            padding: 3px;
            text-align: center;
            font-size: 8px;
            font-weight: 800;
            color: {{ $brandColor }};
            margin-bottom: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        /* ── Signatures Section ────────────────────────────────────────────── */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            page-break-inside: avoid;
        }
        .sig-col {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 8px;
        }
        .sig-line {
            border-top: 1px solid #64748b;
            padding-top: 2px;
            font-size: 7.5px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
        }
        .stamp-box {
            display: inline-block;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            padding: 2.5px 8px;
            font-size: 7px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Footer ────────────────────────────────────────────────────────── */
        .footer-note {
            margin-top: 3px;
            border-top: 1px solid #e2e8f0;
            padding-top: 1.5px;
            text-align: center;
            font-size: 6.5px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

    {{-- ══════════════════════════════════════════════════════════════════════
         1. SCHOOL BRANDING & HEADER SECTION
    ══════════════════════════════════════════════════════════════════════ --}}
    <table class="school-header-table">
        <tr>
            {{-- Left: School Logo --}}
            <td class="header-logo-cell">
                @if (!empty($settings['school_logo']) && file_exists(public_path('storage/' . $settings['school_logo'])))
                    <img src="{{ public_path('storage/' . $settings['school_logo']) }}" class="school-logo" alt="Logo">
                @else
                    <div style="width:64px;height:64px;border-radius:50%;background:#ffffff;border:2.5px solid {{ $brandColor }};display:inline-block;line-height:60px;text-align:center;font-size:16px;font-weight:900;color:{{ $brandColor }};letter-spacing:1px;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                        {{ strtoupper(substr($settings['school_short_name'] ?? $settings['school_name'] ?? 'SCH', 0, 3)) }}
                    </div>
                @endif
            </td>

            {{-- Center: School Identity --}}
            <td class="header-center-cell">
                <div class="school-name">{{ $settings['school_name'] ?? 'EXCELLENCE ACADEMY' }}</div>
                @if(!empty($settings['school_address']))
                    <div class="school-address">{{ $settings['school_address'] }}</div>
                @endif
                <div class="school-contacts">
                    @if(!empty($settings['school_phone'])) Tel: {{ $settings['school_phone'] }} @endif
                    @if(!empty($settings['school_email'])) &bull; Email: {{ $settings['school_email'] }} @endif
                    @if(!empty($settings['school_website'])) &bull; Web: {{ $settings['school_website'] }} @endif
                </div>
                @if(!empty($settings['school_motto']))
                    <div class="school-motto">"{{ $settings['school_motto'] }}"</div>
                @endif
                <div>
                    <span class="report-title-badge">Official Terminal Report Card</span>
                </div>
            </td>

            {{-- Right: Student Passport Photo --}}
            <td class="header-photo-cell">
                @if (!empty($student['passport_photo']) && file_exists(public_path('storage/' . $student['passport_photo'])))
                    <img src="{{ public_path('storage/' . $student['passport_photo']) }}" class="student-photo" alt="Photo">
                @else
                    <div class="student-photo-placeholder">PHOTO</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- Academic Session & Term Ribbon --}}
    <div class="term-ribbon">
        {{ $data['term_name'] ?? 'TERMINAL' }} REPORT SHEET &mdash; {{ $data['session_name'] ?? '' }} ACADEMIC SESSION
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         2. STUDENT BIO-DATA & ATTENDANCE INFORMATION
    ══════════════════════════════════════════════════════════════════════ --}}
    <table class="info-table">
        <tr>
            <td class="lbl">Student Name</td>
            <td class="val" style="font-size:10.5px;">{{ strtoupper($student['name'] ?? '-') }}</td>
            <td class="lbl">Admission No</td>
            <td class="val"><span class="badge-adm">{{ $student['admission_no'] ?? '-' }}</span></td>
        </tr>
        <tr>
            <td class="lbl">Class</td>
            <td class="val">{{ $data['classroom'] ?? '-' }}</td>
            @if(!empty($student['department']))
                <td class="lbl">Department</td>
                <td class="val"><span class="badge-dept">{{ strtoupper($student['department']) }}</span></td>
            @elseif(!empty($student['arm']))
                <td class="lbl">Class Arm</td>
                <td class="val"><span class="badge-dept">{{ strtoupper($student['arm']) }}</span></td>
            @else
                <td class="lbl">Stream</td>
                <td class="val">General</td>
            @endif
        </tr>
        <tr>
            <td class="lbl">Gender / DOB</td>
            <td class="val">{{ ucfirst($student['gender'] ?? '-') }} &bull; {{ $student['dob'] ?? '-' }}</td>
            <td class="lbl">Class Population</td>
            <td class="val">
                @if(!empty($termRes['department_population']))
                    {{ $termRes['department_population'] }} (Dept) &bull; {{ $termRes['class_population'] ?? $data['class_size'] ?? '-' }} (Overall)
                @elseif(!empty($termRes['arm_population']))
                    {{ $termRes['arm_population'] }} (Arm) &bull; {{ $termRes['class_population'] ?? $data['class_size'] ?? '-' }} (Overall)
                @else
                    {{ $termRes['class_population'] ?? $data['class_size'] ?? '-' }} Students
                @endif
            </td>
        </tr>
        <tr>
            <td class="lbl">Attendance</td>
            <td class="val">
                @if($opened !== null && $present !== null)
                    <strong>{{ $present }}</strong> of <strong>{{ $opened }}</strong> Days Opened ({{ $attRate }}%)
                @else
                    {{ $present !== null ? $present . ' Days Present' : '—' }}
                @endif
            </td>
            <td class="lbl">Absence / Punctuality</td>
            <td class="val">
                @if($absent !== null)
                    {{ $absent }} Days Absent
                @else
                    Good
                @endif
            </td>
        </tr>
    </table>

    {{-- ══════════════════════════════════════════════════════════════════════
         3. ACADEMIC PERFORMANCE BREAKDOWN TABLE
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="section-header">Academic Performance &amp; Subject Assessments</div>
    <table class="results-table">
        <thead>
            <tr>
                <th class="th-left" style="width:26%;">Subject</th>
                @foreach (($data['score_heads'] ?? []) as $sh)
                    <th>{{ $sh['name'] }}</th>
                @endforeach
                @if ($data['is_cumulative'] ?? false)
                    <th>3rd Raw</th>
                    <th>1st Term</th>
                    <th>2nd Term</th>
                    <th>Cum. Avg</th>
                @else
                    <th>Total</th>
                @endif
                <th style="width:7%;">Examined</th>
                <th style="width:7%;">Grade</th>
                <th style="width:6%;">Pos</th>
                <th class="th-left" style="width:23%;">Subject Teacher's Remark</th>
            </tr>
        </thead>
        <tbody>
            @forelse (($data['subjects'] ?? []) as $row)
                @php
                    $g = strtoupper($row['grade'] ?? '-');
                    $gClass = match($g) {
                        'A' => 'grade-a',
                        'B' => 'grade-b',
                        'C' => 'grade-c',
                        'D', 'E' => 'grade-d',
                        'F' => 'grade-f',
                        default => '',
                    };
                @endphp
                <tr>
                    <td class="td-left">{{ $row['subject'] }}</td>
                    @foreach (($data['score_heads'] ?? []) as $sh)
                        <td>{{ isset($row['scores'][$sh['id']]) ? (is_numeric($row['scores'][$sh['id']]) ? (float)$row['scores'][$sh['id']] : $row['scores'][$sh['id']]) : '-' }}</td>
                    @endforeach
                    @if ($data['is_cumulative'] ?? false)
                        <td class="score-total">{{ (float) $row['term3_raw'] }}</td>
                        <td>{{ (float) $row['term1'] }}</td>
                        <td>{{ (float) $row['term2'] }}</td>
                        <td class="score-total" style="background:rgba(30,58,138,0.06);">{{ (float) $row['average'] }}</td>
                    @else
                        <td class="score-total" style="background:rgba(30,58,138,0.06);">{{ (float) $row['total'] }}</td>
                    @endif
                    <td>{{ $row['students_examined'] ?? '-' }}</td>
                    <td class="{{ $gClass }}">{{ $g }}</td>
                    <td>{{ $row['position'] ?? '-' }}</td>
                    <td class="remark-cell">{{ $row['remark'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="padding:10px;text-align:center;color:#94a3b8;">No subject records found for this term.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ══════════════════════════════════════════════════════════════════════
         4. TERMINAL SUMMARY STATISTICS
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="section-header">Terminal Performance Summary</div>
    <table class="summary-grid-table">
        <tr>
            <td class="s-lbl">Total Marks Obtained</td>
            <td class="s-val">{{ isset($termRes['grand_total']) ? (float)$termRes['grand_total'] : '-' }} / {{ $data['total_obtainable'] ?? '-' }}</td>
            <td class="s-lbl">Term Percentage Average</td>
            <td class="s-val">{{ isset($termRes['average']) ? (float)$termRes['average'] : '-' }}%</td>
        </tr>
        <tr>
            @if(!empty($termRes['department_position']))
                <td class="s-lbl">Department Position</td>
                <td class="s-val">{{ $termRes['department_position'] }} <span style="font-size:8px;color:#64748b;font-weight:normal;">of {{ $termRes['department_population'] ?? '-' }} ({{ $student['department'] ?? 'Dept' }})</span></td>
            @elseif(!empty($termRes['arm_position']))
                <td class="s-lbl">Arm Position</td>
                <td class="s-val">{{ $termRes['arm_position'] }} <span style="font-size:8px;color:#64748b;font-weight:normal;">of {{ $termRes['arm_population'] ?? '-' }} ({{ $student['arm'] ?? 'Arm' }})</span></td>
            @else
                <td class="s-lbl">Overall Position in Class</td>
                <td class="s-val">{{ $termRes['overall_position'] ?? '-' }} <span style="font-size:8px;color:#64748b;font-weight:normal;">of {{ $termRes['class_population'] ?? $data['class_size'] ?? '-' }}</span></td>
            @endif
            <td class="s-lbl">Final Academic Grade</td>
            <td class="s-val">{{ $termRes['grade'] ?? '-' }} &mdash; {{ $termRes['remark'] ?? '-' }}</td>
        </tr>
        @if(!empty($termRes['department_position']) || !empty($termRes['arm_position']))
        <tr>
            <td class="s-lbl">Overall Set Position</td>
            <td class="s-val">{{ $termRes['overall_position'] ?? '-' }} <span style="font-size:8px;color:#64748b;font-weight:normal;">of {{ $termRes['class_population'] ?? $data['class_size'] ?? '-' }}</span></td>
            <td class="s-lbl">Set Cohort</td>
            <td class="s-val" style="font-size:8px;color:#475569;">{{ $data['classroom'] ?? '-' }} (Entire Set)</td>
        </tr>
        @endif
    </table>

    {{-- ══════════════════════════════════════════════════════════════════════
         5. AFFECTIVE DOMAIN & PSYCHOMOTOR SKILLS
    ══════════════════════════════════════════════════════════════════════ --}}
    <table class="traits-container">
        <tr>
            {{-- Affective Domain --}}
            <td class="traits-col">
                <div class="section-header">Affective Domain (Character &amp; Conduct)</div>
                <table class="trait-table">
                    <thead>
                        <tr>
                            <th>Behavioral Trait</th>
                            <th class="th-ctr">Rating (1 - 5)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Punctuality &amp; Regularity</td><td class="td-ctr">5</td></tr>
                        <tr><td>Neatness &amp; Personal Hygiene</td><td class="td-ctr">5</td></tr>
                        <tr><td>Attentiveness in Class</td><td class="td-ctr">4</td></tr>
                        <tr><td>Honesty &amp; Integrity</td><td class="td-ctr">5</td></tr>
                        <tr><td>Relationship with Peers</td><td class="td-ctr">4</td></tr>
                        <tr><td>Leadership &amp; Initiative</td><td class="td-ctr">5</td></tr>
                    </tbody>
                </table>
            </td>

            {{-- Psychomotor Skills --}}
            <td class="traits-col">
                <div class="section-header">Psychomotor Skills &amp; Activities</div>
                <table class="trait-table">
                    <thead>
                        <tr>
                            <th>Practical Skill / Activity</th>
                            <th class="th-ctr">Rating (1 - 5)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Handwriting &amp; Presentation</td><td class="td-ctr">4</td></tr>
                        <tr><td>Sports, Games &amp; Athletics</td><td class="td-ctr">4</td></tr>
                        <tr><td>Craft, Design &amp; Projects</td><td class="td-ctr">4</td></tr>
                        <tr><td>Verbal Fluency &amp; Communication</td><td class="td-ctr">5</td></tr>
                        <tr><td>Musical &amp; Creative Arts</td><td class="td-ctr">4</td></tr>
                        <tr><td>Laboratory / Practical Skills</td><td class="td-ctr">5</td></tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
    <div style="font-size:7.5px;color:#64748b;margin-bottom:8px;text-align:right;">
        <strong>Rating Scale:</strong> 5 - Excellent &bull; 4 - Very Good &bull; 3 - Good &bull; 2 - Fair &bull; 1 - Poor
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         6. REMARKS & COMMENTS
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="remark-card">
        <div class="remark-title">Class Teacher's Remark:</div>
        <div class="remark-text">
            {{ $termRes['class_teacher_comment'] ?? 'A diligent, polite, and hardworking student who exhibits great academic promise.' }}
        </div>
    </div>

    <div class="remark-card">
        <div class="remark-title">Principal's Executive Comment:</div>
        <div class="remark-text">
            {{ $termRes['principal_comment'] ?? 'An exceptional and commendable performance. Keep maintaining this exemplary standard!' }}
        </div>
    </div>

    {{-- Next Term Resumption Banner --}}
    @if(!empty($data['next_term_begins']))
        <div class="resumption-banner">
            <strong>NEXT TERM RESUMPTION DATE:</strong> {{ strtoupper($data['next_term_begins']) }}
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         7. SIGNATURES & OFFICIAL VERIFICATION
    ══════════════════════════════════════════════════════════════════════ --}}
    <table class="sig-table">
        <tr>
            <td class="sig-col">
                <div class="sig-line">Class Teacher's Signature</div>
            </td>
            <td class="sig-col">
                <div class="stamp-box">
                    &bull; OFFICIAL SCHOOL SEAL &bull;
                </div>
            </td>
            <td class="sig-col">
                <div class="sig-line">Principal's Signature &amp; Date</div>
            </td>
        </tr>
    </table>

    {{-- Document Footer --}}
    <div class="footer-note">
        This is an official computer-generated student terminal report issued by {{ $settings['school_name'] ?? 'the School' }}. &bull; Generated on: {{ now()->format('d M Y, h:i A') }}
    </div>

</body>
</html>
