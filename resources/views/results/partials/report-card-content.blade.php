@php
    use App\Services\ReportCardSettingsService;

    $brandColor = $data['settings']['primary_color'] ?? config('app.tenant_primary_color', '#1e3a8a');
    $settings   = $data['settings'] ?? [];
    $student    = $data['student'] ?? [];
    $termRes    = $data['term_result'] ?? [];
    $opened     = $data['times_school_opened'] ?? null;
    $present    = $termRes['times_present'] ?? null;
    $absent     = ($opened !== null && $present !== null) ? max(0, (int)$opened - (int)$present) : null;
    $attRate    = ($opened && $present !== null) ? round(($present / $opened) * 100, 1) : null;

    // Modular feature toggle helper
    $is = fn(string $key) => ReportCardSettingsService::isEnabled($settings, $key);

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

    $showLogo  = $is('rc_show_school_logo');
    $showPhoto = $is('rc_show_student_photo');
    $logoSrc   = $showLogo ? ($settings['school_logo_data_uri'] ?? $resolveImage($settings['school_logo'] ?? null)) : null;
    $photoSrc  = $showPhoto ? (!empty($student['passport_photo']) ? (str_starts_with($student['passport_photo'], 'data:') ? $student['passport_photo'] : $resolveImage($student['passport_photo'])) : null) : null;

    // Dynamic column counts for results table
    $showScoreHeads = $is('rc_show_score_heads');
    $showCumulative = ($data['is_cumulative'] ?? false) && $is('rc_show_cumulative_terms');
    $showTotal      = $is('rc_show_subject_total');
    $showExamined   = $is('rc_show_students_examined');
    $showGrade      = $is('rc_show_subject_grade');
    $showPosition   = $is('rc_show_subject_position');
    $showRemark     = $is('rc_show_subject_teacher_remark');

    $scoreHeadsList = $showScoreHeads ? ($data['score_heads'] ?? []) : [];
    $colCount = 1 + count($scoreHeadsList)
        + ($showCumulative ? 3 : 0)
        + ($showTotal ? 1 : 0)
        + ($showExamined ? 1 : 0)
        + ($showGrade ? 1 : 0)
        + ($showPosition ? 1 : 0)
        + ($showRemark ? 1 : 0);
@endphp

{{-- ══════════════════════════════════════════════════════════════════════
     1. SCHOOL BRANDING & HEADER SECTION
══════════════════════════════════════════════════════════════════════ --}}
@php
    $centerWidth = 100;
    if ($showLogo)  $centerWidth -= 15;
    if ($showPhoto) $centerWidth -= 15;
@endphp
<table class="school-header-table">
    <tr>
        {{-- Left: School Logo (Optional) --}}
        @if ($showLogo)
            <td class="header-logo-cell">
                @if (!empty($logoSrc))
                    <img src="{{ $logoSrc }}" class="school-logo" alt="Logo">
                @else
                    <div style="width:64px;height:64px;border-radius:50%;background:#ffffff;border:2.5px solid {{ $brandColor }};display:inline-block;line-height:60px;text-align:center;font-size:16px;font-weight:900;color:{{ $brandColor }};letter-spacing:1px;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                        {{ strtoupper(substr($settings['school_short_name'] ?? $settings['school_name'] ?? 'SCH', 0, 3)) }}
                    </div>
                @endif
            </td>
        @endif

        {{-- Center: School Identity (Adaptive Width) --}}
        <td class="header-center-cell" style="width: {{ $centerWidth }}%;">
            @if ($is('rc_show_school_name'))
                <div class="school-name">{{ $settings['school_name'] ?? 'EXCELLENCE ACADEMY' }}</div>
            @endif
            @if ($is('rc_show_school_address') && !empty($settings['school_address']))
                <div class="school-address">{{ $settings['school_address'] }}</div>
            @endif
            @if ($is('rc_show_school_contacts'))
                <div class="school-contacts">
                    @if(!empty($settings['school_phone'])) Tel: {{ $settings['school_phone'] }} @endif
                    @if(!empty($settings['school_email'])) &bull; Email: {{ $settings['school_email'] }} @endif
                    @if(!empty($settings['school_website'])) &bull; Web: {{ $settings['school_website'] }} @endif
                </div>
            @endif
            @if ($is('rc_show_school_motto') && !empty($settings['school_motto']))
                <div class="school-motto">"{{ $settings['school_motto'] }}"</div>
            @endif
            @if ($is('rc_show_report_title'))
                <div>
                    <span class="report-title-badge">Official Terminal Report Card</span>
                </div>
            @endif
        </td>

        {{-- Right: Student Passport Photo (Optional) --}}
        @if ($showPhoto)
            <td class="header-photo-cell">
                @if (!empty($photoSrc))
                    <img src="{{ $photoSrc }}" class="student-photo" alt="Photo">
                @else
                    <div class="student-photo-placeholder">PHOTO</div>
                @endif
            </td>
        @endif
    </tr>
</table>

{{-- Academic Session & Term Ribbon (Optional) --}}
@if ($is('rc_show_term_ribbon'))
    <div class="term-ribbon">
        {{ $data['term_name'] ?? 'TERMINAL' }} REPORT SHEET &mdash; {{ $data['session_name'] ?? '' }} ACADEMIC SESSION
    </div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     2. STUDENT BIO-DATA & ATTENDANCE INFORMATION
══════════════════════════════════════════════════════════════════════ --}}
@php
    $bioItems = [];

    if ($is('rc_show_student_name')) {
        $bioItems[] = [
            'label' => 'Student Name',
            'value' => strtoupper($student['name'] ?? '-'),
            'val_style' => 'font-size:10px;',
        ];
    }

    if ($is('rc_show_admission_number')) {
        $bioItems[] = [
            'label' => 'Admission No',
            'value' => $student['admission_no'] ?? '-',
            'badge' => 'badge-adm',
        ];
    }

    if ($is('rc_show_class')) {
        $bioItems[] = [
            'label' => 'Class',
            'value' => $data['classroom'] ?? '-',
        ];
    }

    if (!empty($student['arm']) && $is('rc_show_class_arm')) {
        $bioItems[] = [
            'label' => 'Class Arm',
            'value' => strtoupper($student['arm']),
            'badge' => 'badge-dept',
        ];
    } elseif (!empty($student['department']) && $is('rc_show_department')) {
        $bioItems[] = [
            'label' => 'Department',
            'value' => strtoupper($student['department']),
            'badge' => 'badge-dept',
        ];
    }

    if ($is('rc_show_class_size')) {
        $bioItems[] = [
            'label' => 'Number in Class',
            'value' => ($termRes['class_population'] ?? $data['class_size'] ?? '-') . ' Students',
        ];
    }

    if (!empty($student['arm']) && $is('rc_show_arm_dept_population')) {
        $bioItems[] = [
            'label' => 'Number in Arm',
            'value' => ($termRes['arm_population'] ?? '-') . ' Students',
        ];
    } elseif (!empty($student['department']) && $is('rc_show_arm_dept_population')) {
        $bioItems[] = [
            'label' => 'Number in Dept',
            'value' => ($termRes['department_population'] ?? '-') . ' Students',
        ];
    }

    $showGender = $is('rc_show_gender');
    $showDob    = $is('rc_show_dob');
    if ($showGender && $showDob) {
        $bioItems[] = [
            'label' => 'Gender / DOB',
            'value' => ucfirst($student['gender'] ?? '-') . ' • ' . ($student['dob'] ?? '-'),
        ];
    } elseif ($showGender) {
        $bioItems[] = [
            'label' => 'Gender',
            'value' => ucfirst($student['gender'] ?? '-'),
        ];
    } elseif ($showDob) {
        $bioItems[] = [
            'label' => 'Date of Birth',
            'value' => $student['dob'] ?? '-',
        ];
    }

    if ($is('rc_show_attendance')) {
        $attText = '';
        if ($opened !== null && $present !== null) {
            $attText = "<strong>{$present}</strong> of <strong>{$opened}</strong> Days ({$attRate}%)";
        } else {
            $attText = $present !== null ? "{$present} Days Present" : '—';
        }
        if ($absent !== null && $absent > 0) {
            $attText .= " &bull; {$absent} Absent";
        }
        $bioItems[] = [
            'label' => 'Attendance',
            'raw_html' => $attText,
        ];
    }
@endphp

@if (!empty($bioItems))
    <table class="info-table">
        @foreach (array_chunk($bioItems, 2) as $rowChunk)
            <tr>
                @foreach ($rowChunk as $index => $item)
                    @php
                        $isSingle = (count($rowChunk) === 1);
                    @endphp
                    <td class="lbl">{{ $item['label'] }}</td>
                    <td class="val" @if($isSingle) colspan="3" @endif @if(!empty($item['val_style'])) style="{{ $item['val_style'] }}" @endif>
                        @if (!empty($item['raw_html']))
                            {!! $item['raw_html'] !!}
                        @elseif (!empty($item['badge']))
                            <span class="{{ $item['badge'] }}">{{ $item['value'] }}</span>
                        @else
                            {{ $item['value'] }}
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     3. ACADEMIC PERFORMANCE BREAKDOWN TABLE
══════════════════════════════════════════════════════════════════════ --}}
<div class="section-header">Academic Performance &amp; Subject Assessments</div>
<table class="results-table">
    <thead>
        <tr>
            <th class="th-left" style="width: {{ $showRemark ? '26%' : '35%' }};">Subject</th>
            @foreach ($scoreHeadsList as $sh)
                <th>{{ $sh['name'] }}</th>
            @endforeach
            @if ($showCumulative)
                <th>3rd Raw</th>
                <th>1st Term</th>
                <th>2nd Term</th>
                @if ($showTotal)
                    <th>Cum. Avg</th>
                @endif
            @else
                @if ($showTotal)
                    <th>Total</th>
                @endif
            @endif
            @if ($showExamined)
                <th style="width:7%;">Examined</th>
            @endif
            @if ($showGrade)
                <th style="width:7%;">Grade</th>
            @endif
            @if ($showPosition)
                <th style="width:6%;">Pos</th>
            @endif
            @if ($showRemark)
                <th class="th-left" style="width:23%;">Subject Teacher's Remark</th>
            @endif
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
                @foreach ($scoreHeadsList as $sh)
                    <td>{{ isset($row['scores'][$sh['id']]) ? (is_numeric($row['scores'][$sh['id']]) ? (int) ceil((float)$row['scores'][$sh['id']]) : $row['scores'][$sh['id']]) : '-' }}</td>
                @endforeach
                @if ($showCumulative)
                    <td class="score-total">{{ is_numeric($row['term3_raw'] ?? null) ? (int) ceil((float) $row['term3_raw']) : ($row['term3_raw'] ?? '-') }}</td>
                    <td>{{ is_numeric($row['term1'] ?? null) ? (int) ceil((float) $row['term1']) : ($row['term1'] ?? '-') }}</td>
                    <td>{{ is_numeric($row['term2'] ?? null) ? (int) ceil((float) $row['term2']) : ($row['term2'] ?? '-') }}</td>
                    @if ($showTotal)
                        <td class="score-total" style="background:rgba(30,58,138,0.06);">{{ is_numeric($row['average'] ?? null) ? (int) ceil((float) $row['average']) : ($row['average'] ?? '-') }}</td>
                    @endif
                @else
                    @if ($showTotal)
                        <td class="score-total" style="background:rgba(30,58,138,0.06);">{{ is_numeric($row['total'] ?? null) ? (int) ceil((float) $row['total']) : ($row['total'] ?? '-') }}</td>
                    @endif
                @endif
                @if ($showExamined)
                    <td>{{ $row['students_examined'] ?? '-' }}</td>
                @endif
                @if ($showGrade)
                    <td class="{{ $gClass }}">{{ $g }}</td>
                @endif
                @if ($showPosition)
                    <td>{{ $row['position'] ?? '-' }}</td>
                @endif
                @if ($showRemark)
                    <td class="remark-cell">{{ $row['remark'] ?? '-' }}</td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="{{ $colCount }}" style="padding:10px;text-align:center;color:#94a3b8;">No subject records found for this term.</td>
            </tr>
        @endforelse
    </tbody>
</table>

{{-- ══════════════════════════════════════════════════════════════════════
     4. TERMINAL SUMMARY STATISTICS
══════════════════════════════════════════════════════════════════════ --}}
@if ($is('rc_show_performance_summary'))
    @php
        $summaryItems = [];

        if ($is('rc_show_total_obtained')) {
            $obtainedVal = isset($termRes['grand_total']) && is_numeric($termRes['grand_total']) ? (int) ceil((float)$termRes['grand_total']) : ($termRes['grand_total'] ?? '-');
            $maxVal = isset($data['total_obtainable']) && is_numeric($data['total_obtainable']) ? (int) ceil((float)$data['total_obtainable']) : ($data['total_obtainable'] ?? '-');
            $summaryItems[] = [
                'label' => 'Total Marks Obtained',
                'value' => "{$obtainedVal} / {$maxVal}",
            ];
        }

        if ($is('rc_show_term_percentage')) {
            $avgVal = isset($termRes['average']) && is_numeric($termRes['average']) ? (int) ceil((float)$termRes['average']) . '%' : ($termRes['average'] ?? '-');
            $summaryItems[] = [
                'label' => 'Term Percentage Average',
                'value' => $avgVal,
            ];
        }

        if (!empty($termRes['department_position']) && $is('rc_show_arm_dept_position')) {
            $deptPop = $termRes['department_population'] ?? '-';
            $deptName = $student['department'] ?? 'Dept';
            $summaryItems[] = [
                'label' => 'Department Position',
                'value' => "{$termRes['department_position']} (of {$deptPop} in {$deptName})",
            ];
        } elseif (!empty($termRes['arm_position']) && $is('rc_show_arm_dept_position')) {
            $armPop = $termRes['arm_population'] ?? '-';
            $armName = $student['arm'] ?? 'Arm';
            $summaryItems[] = [
                'label' => 'Arm Position',
                'value' => "{$termRes['arm_position']} (of {$armPop} in {$armName})",
            ];
        }

        if ($is('rc_show_overall_position')) {
            $classPop = $termRes['class_population'] ?? $data['class_size'] ?? '-';
            $posVal = $termRes['overall_position'] ?? '-';
            $summaryItems[] = [
                'label' => 'Overall Position in Class',
                'value' => "{$posVal} of {$classPop}",
            ];
        }

        if ($is('rc_show_final_grade')) {
            $grd = $termRes['grade'] ?? '-';
            $rmk = $termRes['remark'] ?? '-';
            $summaryItems[] = [
                'label' => 'Final Academic Grade',
                'value' => "{$grd} — {$rmk}",
            ];
        }
    @endphp

    @if (!empty($summaryItems))
        <div class="section-header">Terminal Performance Summary</div>
        <table class="summary-grid-table">
            @foreach (array_chunk($summaryItems, 2) as $rowChunk)
                <tr>
                    @foreach ($rowChunk as $item)
                        @php $isSingle = (count($rowChunk) === 1); @endphp
                        <td class="s-lbl">{{ $item['label'] }}</td>
                        <td class="s-val" @if($isSingle) colspan="3" @endif>{{ $item['value'] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @endif
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     5. AFFECTIVE DOMAIN & PSYCHOMOTOR SKILLS
══════════════════════════════════════════════════════════════════════ --}}
@php
    $showAffective   = $is('rc_show_affective_domain');
    $showPsychomotor = $is('rc_show_psychomotor_skills');
    $showLegend      = $is('rc_show_domain_scale');
@endphp

@if ($showAffective || $showPsychomotor)
    <table class="traits-container">
        <tr>
            {{-- Affective Domain (Optional) --}}
            @if ($showAffective)
                <td class="traits-col" style="width: {{ $showPsychomotor ? '50%' : '100%' }}; @if($showPsychomotor) padding-right: 3px; @endif">
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
            @endif

            {{-- Psychomotor Skills (Optional) --}}
            @if ($showPsychomotor)
                <td class="traits-col" style="width: {{ $showAffective ? '50%' : '100%' }}; @if($showAffective) padding-left: 3px; @endif">
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
            @endif
        </tr>
    </table>

    @if ($showLegend)
        <div style="font-size:7.5px;color:#64748b;margin-bottom:6px;text-align:right;">
            <strong>Rating Scale:</strong> 5 - Excellent &bull; 4 - Very Good &bull; 3 - Good &bull; 2 - Fair &bull; 1 - Poor
        </div>
    @endif
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     6. REMARKS & COMMENTS
══════════════════════════════════════════════════════════════════════ --}}
@if ($is('rc_show_class_teacher_remark'))
    <div class="remark-card">
        <div class="remark-title">Class Teacher's Remark:</div>
        <div class="remark-text">
            {{ $termRes['class_teacher_comment'] ?? 'A diligent, polite, and hardworking student who exhibits great academic promise.' }}
        </div>
    </div>
@endif

@if ($is('rc_show_principal_remark'))
    <div class="remark-card">
        <div class="remark-title">Principal's Executive Comment:</div>
        <div class="remark-text">
            {{ $termRes['principal_comment'] ?? 'An exceptional and commendable performance. Keep maintaining this exemplary standard!' }}
        </div>
    </div>
@endif

{{-- Next Term Resumption Banner (Optional) --}}
@if ($is('rc_show_next_term_resumption') && !empty($data['next_term_begins']))
    <div class="resumption-banner">
        <strong>NEXT TERM RESUMPTION DATE:</strong> {{ strtoupper($data['next_term_begins']) }}
    </div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     7. SIGNATURES & OFFICIAL VERIFICATION
══════════════════════════════════════════════════════════════════════ --}}
@php
    $showTeacherSig   = $is('rc_show_teacher_signature');
    $showStamp        = $is('rc_show_school_stamp');
    $showPrincipalSig = $is('rc_show_principal_signature');

    $sigColumns = [];
    if ($showTeacherSig)   $sigColumns[] = 'teacher';
    if ($showStamp)        $sigColumns[] = 'stamp';
    if ($showPrincipalSig) $sigColumns[] = 'principal';

    $colWidth = !empty($sigColumns) ? round(100 / count($sigColumns), 2) . '%' : '100%';
@endphp

@if (!empty($sigColumns))
    <table class="sig-table">
        <tr>
            @foreach ($sigColumns as $type)
                <td class="sig-col" style="width: {{ $colWidth }};">
                    @if ($type === 'teacher')
                        <div class="sig-line">Class Teacher's Signature</div>
                    @elseif ($type === 'stamp')
                        <div class="stamp-box">
                            &bull; OFFICIAL SCHOOL SEAL &bull;
                        </div>
                    @elseif ($type === 'principal')
                        <div class="sig-line">Principal's Signature &amp; Date</div>
                    @endif
                </td>
            @endforeach
        </tr>
    </table>
@endif

{{-- Document Footer (Optional) --}}
@if ($is('rc_show_footer_note'))
    <div class="footer-note">
        This is an official computer-generated student terminal report issued by {{ $settings['school_name'] ?? 'the School' }}. &bull; Generated on: {{ now()->format('d M Y, h:i A') }}
    </div>
@endif
