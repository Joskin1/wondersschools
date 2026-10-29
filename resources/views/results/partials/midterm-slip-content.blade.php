@php
    $brandColor = $data['settings']['primary_color'] ?? config('app.tenant_primary_color', '#1e3a8a');
    $settings   = $data['settings'] ?? [];
    $student    = $data['student'] ?? [];
    $termRes    = $data['term_result'] ?? [];

    $resolveImage = function(?string $path): ?string {
        if (empty($path)) {
            return null;
        }
        if (str_starts_with($path, 'data:')) {
            return $path;
        }
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
                if (file_exists($fullPath)) {
                    $mime = mime_content_type($fullPath) ?: 'image/png';
                    return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
                }
            }
        } catch (\Throwable $e) {}

        $pubPath = public_path('storage/' . $path);
        if (file_exists($pubPath)) {
            $mime = mime_content_type($pubPath) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($pubPath));
        }

        $stPath = storage_path('app/public/' . $path);
        if (file_exists($stPath)) {
            $mime = mime_content_type($stPath) ?: 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($stPath));
        }

        return null;
    };

    $logoSrc  = $settings['school_logo_data_uri'] ?? $resolveImage($settings['school_logo'] ?? null);
    $photoSrc = !empty($student['passport_photo']) ? (str_starts_with($student['passport_photo'], 'data:') ? $student['passport_photo'] : $resolveImage($student['passport_photo'])) : null;
@endphp

{{-- Header Section --}}
<table class="school-header-table">
    <tr>
        <td class="header-logo-cell">
            @if (!empty($logoSrc))
                <img src="{{ $logoSrc }}" class="school-logo" alt="Logo">
            @else
                <div style="width:58px;height:58px;border-radius:50%;background:#ffffff;border:2px solid {{ $brandColor }};display:inline-block;line-height:54px;text-align:center;font-size:15px;font-weight:900;color:{{ $brandColor }};letter-spacing:1px;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    {{ strtoupper(substr($settings['school_short_name'] ?? $settings['school_name'] ?? 'SCH', 0, 3)) }}
                </div>
            @endif
        </td>
        <td class="header-center-cell">
            <div class="school-name">{{ $settings['school_name'] ?? 'WONDERS SCHOOL' }}</div>
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
                <span class="report-title-badge">Mid-Term Academic Progress Report</span>
            </div>
        </td>
        <td class="header-photo-cell">
            @if (!empty($photoSrc))
                <img src="{{ $photoSrc }}" class="student-photo" alt="Photo">
            @else
                <div class="student-photo-placeholder">PHOTO</div>
            @endif
        </td>
    </tr>
</table>

{{-- Academic Term Ribbon --}}
<div class="term-ribbon">
    {{ $data['term_name'] ?? 'TERM' }} MID-TERM EVALUATION &mdash; {{ $data['session_name'] ?? '' }} ACADEMIC SESSION
</div>

{{-- Student Bio-Data --}}
<table class="info-table">
    <tr>
        <td class="lbl">Student Name</td>
        <td class="val" style="font-size: 10px;">{{ strtoupper($student['name'] ?? '-') }}</td>
        <td class="lbl">Admission No</td>
        <td class="val">{{ $student['admission_no'] ?? '-' }}</td>
    </tr>
    @if(!empty($student['arm']))
        <tr>
            <td class="lbl">Class</td>
            <td class="val">{{ $data['classroom'] ?? '-' }}</td>
            <td class="lbl">Class Arm</td>
            <td class="val">{{ strtoupper($student['arm']) }}</td>
        </tr>
        <tr>
            <td class="lbl">Number in Class</td>
            <td class="val">{{ $data['class_size'] ?? $termRes['class_population'] ?? '-' }} Students</td>
            <td class="lbl">Number in Arm</td>
            <td class="val">{{ $data['arm_population'] ?? $termRes['arm_population'] ?? '-' }} Students</td>
        </tr>
    @elseif(!empty($student['department']))
        <tr>
            <td class="lbl">Class</td>
            <td class="val">{{ $data['classroom'] ?? '-' }}</td>
            <td class="lbl">Department</td>
            <td class="val">{{ strtoupper($student['department']) }}</td>
        </tr>
        <tr>
            <td class="lbl">Number in Class</td>
            <td class="val">{{ $data['class_size'] ?? $termRes['class_population'] ?? '-' }} Students</td>
            <td class="lbl">Number in Dept</td>
            <td class="val">{{ $data['department_population'] ?? $termRes['department_population'] ?? '-' }} Students</td>
        </tr>
    @else
        <tr>
            <td class="lbl">Class</td>
            <td class="val">{{ $data['classroom'] ?? '-' }}</td>
            <td class="lbl">Number in Class</td>
            <td class="val">{{ $data['class_size'] ?? $termRes['class_population'] ?? '-' }} Students</td>
        </tr>
    @endif
    <tr>
        <td class="lbl">Gender / DOB</td>
        <td class="val">{{ ucfirst($student['gender'] ?? '-') }} &bull; {{ $student['dob'] ?? '-' }}</td>
        <td class="lbl">Mid-Term Weight</td>
        <td class="val">{{ $data['midterm_weight'] ?? '-' }}% of Term Total</td>
    </tr>
</table>

{{-- Subject Performance Table --}}
<div class="section-header">Mid-Term Subject Continuous Assessments</div>
<table class="results-table">
    <thead>
        <tr>
            <th class="th-left" style="width: 25%;">Subject</th>
            @foreach(($data['midterm_heads'] ?? []) as $head)
                <th>
                    {{ $head['name'] }}<br>
                    <span style="font-size: 6.5px; color: #64748b;">({{ $head['effective_max'] ?? $head['max_score'] ?? '—' }})</span>
                </th>
            @endforeach
            <th style="width: 10%;">
                Raw Total<br>
                <span style="font-size: 6.5px; color: #64748b;">({{ $data['raw_max'] ?? '—' }})</span>
            </th>
            <th style="width: 12%; background: #e0e7ff; color: #3730a3;">
                Mid-Term<br>
                <span style="font-size: 6.5px; color: #4338ca;">({{ $data['midterm_weight'] ?? '—' }}%)</span>
            </th>
            <th style="width: 10%;">% Score</th>
            <th style="width: 8%;">Grade</th>
            <th class="th-left" style="width: 15%;">Remark</th>
        </tr>
    </thead>
    <tbody>
        @forelse(($data['subjects'] ?? []) as $row)
            <tr>
                <td class="td-left">{{ $row['subject'] }}</td>
                @foreach(($data['midterm_heads'] ?? []) as $head)
                    <td>
                        @php $val = $row['head_scores'][$head['id']] ?? '—'; @endphp
                        @if($val === 'ABS')
                            <span style="font-weight: 800; color: #e11d48;">ABS</span>
                        @else
                            {{ is_numeric($val) ? (int) ceil((float) $val) : $val }}
                        @endif
                    </td>
                @endforeach
                <td style="font-weight: 700;">
                    @if(is_numeric($row['raw_score']))
                        {{ (int) ceil((float) $row['raw_score']) }}
                    @else
                        {{ $row['raw_score'] }}
                    @endif
                </td>
                <td style="font-weight: 900; background: #eef2ff; color: #4338ca;">
                    @if($row['scaled_score'] === 'ABS')
                        <span style="color: #e11d48;">ABS</span>
                    @elseif(is_numeric($row['scaled_score']))
                        {{ (int) ceil((float) $row['scaled_score']) }}
                    @else
                        {{ $row['scaled_score'] }}
                    @endif
                </td>
                <td style="font-weight: 800;">
                    @if($row['percentage'] === 'ABS')
                        <span style="color: #e11d48;">ABS</span>
                    @elseif(is_numeric($row['percentage']))
                        {{ (int) ceil((float) $row['percentage']) }}%
                    @else
                        {{ $row['percentage'] }}
                    @endif
                </td>
                <td style="font-weight: 900; color: {{ $brandColor }};">
                    {{ $row['grade'] ?? '—' }}
                </td>
                <td class="td-left" style="font-size: 7.5px;">
                    {{ $row['remark'] ?? '—' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($data['midterm_heads'] ?? []) + 5 }}" style="padding: 12px; color: #94a3b8;">
                    No subject assessments recorded for this student.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- Mid-Term Performance Summary --}}
@if(!empty($termRes))
    <div class="section-header">Mid-Term Performance Summary</div>
    <table class="summary-grid-table">
        <tr>
            <td class="s-lbl">Subjects Assessed</td>
            <td class="s-lbl">Total Obtainable</td>
            <td class="s-lbl">Total Marks Obtained</td>
            <td class="s-lbl">Mid-Term Average</td>
            <td class="s-lbl">Overall Grade</td>
        </tr>
        <tr>
            <td class="s-val">{{ $termRes['subjects_count'] ?? count($data['subjects'] ?? []) }}</td>
            <td class="s-val">{{ is_numeric($termRes['total_obtainable'] ?? null) ? (int) ceil((float) $termRes['total_obtainable']) : ($termRes['total_obtainable'] ?? 0) }}</td>
            <td class="s-val">{{ is_numeric($termRes['total_obtained'] ?? null) ? (int) ceil((float) $termRes['total_obtained']) : ($termRes['total_obtained'] ?? 0) }}</td>
            <td class="s-val">{{ is_numeric($termRes['percentage'] ?? null) ? (int) ceil((float) $termRes['percentage']) . '%' : ($termRes['percentage'] ?? 0) }}</td>
            <td class="s-val">{{ $termRes['grade'] ?? '—' }} ({{ $termRes['remark'] ?? '—' }})</td>
        </tr>
    </table>
@endif

{{-- Teacher & Head Teacher Comments --}}
<div class="remark-card">
    <div class="remark-title">Class Teacher's Observation</div>
    <div class="remark-text">
        {{ !empty($termRes['teacher_comment']) ? $termRes['teacher_comment'] : 'Satisfactory mid-term progress. Continue striving for excellence.' }}
    </div>
</div>

<div class="remark-card">
    <div class="remark-title">Principal / Head of School's Remark</div>
    <div class="remark-text">
        {{ !empty($termRes['head_teacher_comment']) ? $termRes['head_teacher_comment'] : 'A good mid-term effort. Keep up the focus and determination towards final exams.' }}
    </div>
</div>

{{-- Signatures --}}
<table class="sig-table">
    <tr>
        <td class="sig-col">
            <div class="sig-line">Class Teacher</div>
        </td>
        <td class="sig-col">
            <div class="sig-line">Date Issued: {{ date('d M Y') }}</div>
        </td>
        <td class="sig-col">
            <div class="sig-line">Principal / Head of School</div>
        </td>
    </tr>
</table>
