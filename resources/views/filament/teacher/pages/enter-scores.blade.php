<x-filament-panels::page>
    <style>
        .ss-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .dark .ss-card { background: #131b2e; border-color: rgba(148,163,184,0.1); }
        .ss-label { display: block; font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 6px; }
        .ss-select { width: 100%; border-radius: 0.75rem; border: 1px solid #e2e8f0; background: white; color: #1e293b; padding: 0.625rem 0.875rem; font-size: 0.875rem; transition: border-color 0.15s; }
        .ss-select:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .ss-select:disabled { opacity: 0.5; cursor: not-allowed; background: #f8fafc; }
        .dark .ss-select { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .dark .ss-select:disabled { background: rgba(30,41,59,0.5); }
        
        .ss-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        @media (min-width: 1024px) { .ss-grid { grid-template-columns: repeat(4, 1fr); } }
        
        .ss-stat-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.25rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .dark .ss-stat-card { background: #131b2e; border-color: rgba(148,163,184,0.1); }
        
        .ss-table-card { position: relative; border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden; }
        .dark .ss-table-card { background: #0b0f19; border-color: rgba(148,163,184,0.1); }
        
        .ss-scroll-container { overflow-x: auto; max-height: 65vh; }
        
        .ss-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; text-align: left; }
        .ss-table th { padding: 1rem 1.25rem; font-size: 0.75rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; border-bottom: 1px solid #e2e8f0; }
        .dark .ss-table th { border-bottom-color: #1e293b; }
        
        .ss-table td { padding: 0.875rem 1.25rem; border-bottom: 1px solid rgba(148,163,184,0.1); }
        .dark .ss-table td { border-bottom-color: rgba(148,163,184,0.05); }
        
        /* Sticky student column */
        .ss-sticky-col { position: sticky; left: 0; background: white; border-right: 1px solid #e2e8f0; z-index: 10; box-shadow: 2px 0 5px -2px rgba(0,0,0,0.08); }
        .dark .ss-sticky-col { background: #0b0f19; border-right-color: #1e293b; }
        tr:hover .ss-sticky-col { background: #f8fafc; }
        .dark tr:hover .ss-sticky-col { background: #131b2e; }
        
        .ss-sticky-header { position: sticky; top: 0; background: #f8fafc; z-index: 20; border-bottom: 2px solid #e2e8f0; }
        .dark .ss-sticky-header { background: #131b2e; border-bottom-color: #1e293b; }
        
        .ss-sticky-header-first { position: sticky; left: 0; top: 0; background: #f8fafc; z-index: 30; border-right: 1px solid #e2e8f0; border-bottom: 2px solid #e2e8f0; }
        .dark .ss-sticky-header-first { background: #131b2e; border-right-color: #1e293b; border-bottom-color: #1e293b; }
        
        .ss-cell-wrap { display: inline-flex; align-items: center; justify-content: center; gap: 0.25rem; padding: 0.375rem 0.625rem; border-radius: 0.75rem; border: 1px solid transparent; transition: all 0.15s; background: rgba(30,41,59,0.04); }
        .dark .ss-cell-wrap { background: rgba(30,41,59,0.4); }
        .ss-cell-wrap:focus-within { border-color: #6366f1; box-shadow: 0 0 0 2px rgba(99,102,241,0.15); }
        
        .ss-cell-wrap-error { background: rgba(239,68,68,0.1) !important; border-color: rgba(239,68,68,0.3) !important; color: #ef4444 !important; }
        .ss-cell-wrap-saving { background: rgba(245,158,11,0.05) !important; border-color: rgba(245,158,11,0.2) !important; }
        
        .ss-cell-input { width: 2.5rem; text-align: center; background: transparent; border: none; outline: none; padding: 0; font-size: 0.875rem; font-weight: 900; color: #1e293b; }
        .dark .ss-cell-input { color: #f1f5f9; }
        .ss-cell-input::placeholder { color: #64748b; }
        
        .ss-bracket { color: #64748b; font-weight: 900; font-size: 0.875rem; user-select: none; }
        
        .ss-empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4rem 1.5rem; text-align: center; border-radius: 1rem; border: 2px dashed rgba(148,163,184,0.2); position: relative; overflow: hidden; }
        .dark .ss-empty-state { border-color: rgba(148,163,184,0.1); background: #131b2e; }
        
        .avatar-wrap { display: flex; height: 2.25rem; width: 2.25rem; shrink-0: 0; align-items: center; justify-content: center; border-radius: 9999px; font-weight: 900; font-size: 0.75rem; text-transform: uppercase; }
        
        .ss-alert { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1.5rem; text-align: center; border-radius: 1rem; border: 2px dashed rgba(245,158,11,0.2); }
        .dark .ss-alert { background: rgba(245,158,11,0.02); }
    </style>

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">

        {{-- ── Step 1: Active Parameters Filters ──────────────────────── --}}
        <div class="ss-card">
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(148,163,184,0.1);">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="display: flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: 0.75rem; background: rgba(238,242,255,1); color: #4f46e5;">
                            <svg style="width: 20px; height: 20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </div>
                        <div>
                            <h2 style="font-size: 1rem; font-weight: 900; color: #0f172a;" class="dark:text-white">Active Parameters</h2>
                            <p style="font-size: 0.75rem; color: #94a3b8;">Configure parameters to load the student scorecard matrix.</p>
                        </div>
                    </div>

                    <div wire:loading wire:target="session_id,term_id,classroom_id,subject_id" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: #6366f1; font-weight: 700;">
                        <svg style="width: 16px; height: 16px;" class="animate-spin" fill="none" viewBox="0 0 24 24"><circle opacity="0.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path opacity="0.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Loading scorecard...
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 1.25rem;" class="sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="ss-label">Academic Session</label>
                        <select wire:model.live="session_id" class="ss-select">
                            <option value="">— Select Session —</option>
                            @foreach($this->sessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ss-label">Term</label>
                        <select wire:model.live="term_id" @disabled(! $session_id) class="ss-select">
                            <option value="">— Select Term —</option>
                            @foreach($this->terms as $term)
                                <option value="{{ $term->id }}">{{ $term->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ss-label">Classroom</label>
                        <select wire:model.live="classroom_id" @disabled(! $term_id) class="ss-select">
                            <option value="">— Select Class —</option>
                            @foreach($this->authorizedClassrooms as $classroom)
                                <option value="{{ $classroom->id }}">{{ $classroom->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ss-label">Subject</label>
                        <select wire:model.live="subject_id" @disabled(! $classroom_id) class="ss-select">
                            <option value="">— Select Subject —</option>
                            @foreach($this->authorizedSubjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Main Workspace ────────────────────────────────────────────────── --}}
        @if($loaded)

            @if(! $structureExists)
                <div class="ss-alert">
                    <div style="width: 3.5rem; height: 3.5rem; display: flex; align-items: center; justify-content: center; border-radius: 1rem; background: rgba(245,158,11,0.1); color: #d97706; margin-bottom: 1rem;">
                        <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <h3 style="font-size: 0.875rem; font-weight: 900; color: #b45309;" class="dark:text-amber-400">Score Structure Not Set</h3>
                    <p style="margin-top: 0.25rem; font-size: 0.75rem; color: #92400e; max-width: 24rem; line-height: 1.6;" class="dark:text-amber-500">
                        The class score weights (e.g. Test &amp; Exams weight distributions) are not yet configured for this term.
                    </p>
                </div>

            @elseif(empty($students))
                <div class="ss-empty-state">
                    <div style="width: 3.5rem; height: 3.5rem; display: flex; align-items: center; justify-content: center; border-radius: 1rem; background: rgba(148,163,184,0.1); color: #64748b; margin-bottom: 1rem;">
                        <svg style="width: 24px; height: 24px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <h3 style="font-size: 0.875rem; font-weight: 900;" class="dark:text-white">No Enrolled Students</h3>
                    <p style="margin-top: 0.25rem; font-size: 0.75rem; color: #94a3b8; max-width: 24rem; line-height: 1.6;">
                        There are currently no active students enrolled in this class for the current session.
                    </p>
                </div>

            @else
                @php $stats = $this->stats; @endphp

                <div 
                    x-data="{
                        savingStates: {},
                        errorMessages: {},
                        moveFocus(current, rowOffset, colOffset) {
                            let row = parseInt(current.getAttribute('data-row')) + rowOffset;
                            let col = parseInt(current.getAttribute('data-col')) + colOffset;
                            let next = document.querySelector(`input[data-row='${row}'][data-col='${col}']`);
                            if (next) {
                                next.focus();
                                next.select();
                            }
                        },
                        triggerSave(studentId, scoreHeadId, value, maxVal, headName) {
                            let key = `${studentId}-${scoreHeadId}`;
                            let rowKey = `row-${studentId}`;
                            
                            if (value !== '' && (parseFloat(value) < 0 || parseFloat(value) > maxVal)) {
                                this.errorMessages[key] = `Max ${maxVal}`;
                                this.savingStates[rowKey] = 'error';
                                document.getElementById(`sync-text-${studentId}`).innerText = `Max Error (${headName})`;
                                document.getElementById(`sync-text-${studentId}`).className = 'text-red-500 font-extrabold text-xs tracking-wide animate-pulse';
                                
                                document.getElementById(`total-${studentId}`).innerText = '--';
                                document.getElementById(`grade-${studentId}`).innerText = '--';
                                document.getElementById(`grade-${studentId}`).className = 'text-slate-500 font-bold text-xs select-none';
                                return;
                            }
                            
                            this.errorMessages[key] = null;
                            this.savingStates[rowKey] = 'saving';
                            document.getElementById(`sync-text-${studentId}`).innerText = 'Syncing...';
                            document.getElementById(`sync-text-${studentId}`).className = 'text-amber-500 font-black text-xs tracking-wide animate-pulse';
                            
                            $wire.saveScore(studentId, scoreHeadId, value).then(result => {
                                if (result.status === 'success') {
                                    this.savingStates[rowKey] = 'saved';
                                    document.getElementById(`sync-text-${studentId}`).innerText = 'Saved';
                                    document.getElementById(`sync-text-${studentId}`).className = 'text-emerald-500 font-bold text-xs tracking-wide';
                                    
                                    document.getElementById(`total-${studentId}`).innerText = result.student_total;
                                    
                                    let gradeSpan = document.getElementById(`grade-${studentId}`);
                                    gradeSpan.innerText = result.student_grade;
                                    
                                    gradeSpan.className = '';
                                    if (result.student_grade === 'A' || result.student_grade === 'A+') {
                                        gradeSpan.className = 'text-white font-extrabold text-sm';
                                    } else if (result.student_grade === '—') {
                                        gradeSpan.className = 'text-slate-500 font-bold text-xs';
                                    } else {
                                        gradeSpan.className = 'text-slate-300 font-bold text-sm';
                                    }
 
                                    if (result.stats) {
                                        document.getElementById('stat-average').innerText = result.stats.average + '%';
                                        document.getElementById('stat-highest').innerText = result.stats.highest + ' / ' + result.stats.max_total;
                                        document.getElementById('stat-lowest').innerText = result.stats.lowest + ' / ' + result.stats.max_total;
                                        document.getElementById('stat-pass-percent').innerText = result.stats.pass_percent + '%';
                                    }
                                } else {
                                    this.savingStates[rowKey] = 'error';
                                    document.getElementById(`sync-text-${studentId}`).innerText = result.message;
                                    document.getElementById(`sync-text-${studentId}`).className = 'text-red-500 font-black text-xs';
                                    document.getElementById(`total-${studentId}`).innerText = '--';
                                    document.getElementById(`grade-${studentId}`).innerText = '--';
                                }
                            }).catch(() => {
                                this.savingStates[rowKey] = 'error';
                                document.getElementById(`sync-text-${studentId}`).innerText = 'Error';
                                document.getElementById(`sync-text-${studentId}`).className = 'text-red-500 font-black text-xs';
                            });
                        }
                    }"
                    style="display: flex; flex-direction: column; gap: 1.5rem;"
                >

                    {{-- ── Real-Time Analytics Dashboard Row ────────── --}}
                    <div class="ss-grid">
                        <div class="ss-stat-card">
                            <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.05em; text-transform: uppercase;">Class Average</p>
                            <p id="stat-average" style="font-size: 1.5rem; font-weight: 900; margin-top: 0.5rem; font-variant-numeric: tabular-nums;" class="text-slate-900 dark:text-white">
                                {{ $stats['average'] }}%
                            </p>
                        </div>

                        <div class="ss-stat-card">
                            <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.05em; text-transform: uppercase;">Highest Mark</p>
                            <p id="stat-highest" style="font-size: 1.5rem; font-weight: 900; margin-top: 0.5rem; font-variant-numeric: tabular-nums;" class="text-slate-900 dark:text-white">
                                {{ $stats['highest'] }} / {{ $stats['max_total'] }}
                            </p>
                        </div>

                        <div class="ss-stat-card">
                            <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.05em; text-transform: uppercase;">Lowest Mark</p>
                            <p id="stat-lowest" style="font-size: 1.5rem; font-weight: 900; margin-top: 0.5rem; font-variant-numeric: tabular-nums;" class="text-slate-900 dark:text-white">
                                {{ $stats['lowest'] }} / {{ $stats['max_total'] }}
                            </p>
                        </div>

                        <div class="ss-stat-card">
                            <p style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; letter-spacing: 0.05em; text-transform: uppercase;">Pass Rate</p>
                            <p id="stat-pass-percent" style="font-size: 1.5rem; font-weight: 900; margin-top: 0.5rem; font-variant-numeric: tabular-nums;" class="text-slate-900 dark:text-white">
                                {{ $stats['pass_percent'] }}%
                            </p>
                        </div>
                    </div>

                    {{-- ── Spreadsheet Gradebook Matrix Grid View ─────────────────── --}}
                    <div class="ss-table-card">
                        <div class="ss-scroll-container">
                            <table class="ss-table">
                                <thead>
                                    <tr class="ss-sticky-header">
                                        {{-- Sticky Details Header --}}
                                        <th class="ss-sticky-header-first" style="min-width: 260px;">
                                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                                <span>Student Details (Sticky)</span>
                                                <svg style="width: 14px; height: 14px; color: #64748b;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                            </div>
                                        </th>
                                        @foreach($scoreHeads as $sh)
                                            <th style="text-align: center; min-width: 135px; border-right: 1px solid rgba(148,163,184,0.1);">
                                                {{ $sh['name'] }} (Max {{ $sh['effective_max'] }})
                                            </th>
                                        @endforeach
                                        <th style="text-align: center; min-width: 110px; border-right: 1px solid rgba(148,163,184,0.1); background: rgba(248,250,252,0.5);" class="dark:bg-slate-900/30">
                                            Total ({{ $stats['max_total'] }})
                                        </th>
                                        <th style="text-align: center; min-width: 95px; border-right: 1px solid rgba(148,163,184,0.1); background: rgba(248,250,252,0.5);" class="dark:bg-slate-900/30">
                                            Grade
                                        </th>
                                        <th style="text-align: center; min-width: 130px; background: rgba(248,250,252,0.5);" class="dark:bg-slate-900/30">
                                            Sync Status
                                        </th>
                                    </tr>
                                </thead>

                                <tbody style="background: white;" class="dark:bg-[#0b0f19]">
                                    @foreach($students as $student)
                                        @php
                                            $studentTotal = collect($scoreHeads)->sum(
                                                fn($sh) => (float) ($scores[$student['id']][$sh['id']] ?? 0)
                                            );
                                            $hasAny = collect($scoreHeads)->contains(fn($sh) => ($scores[$student['id']][$sh['id']] ?? '') !== '');
                                            $gradeInfo = app(\App\Services\ResultCalculationService::class)->resolveGrade($studentTotal);
                                            $currentGrade = $hasAny ? $gradeInfo['grade'] : '—';
                                            
                                            $colors = [
                                                'background: rgba(59,130,246,0.08); color: #2563eb;',
                                                'background: rgba(147,51,234,0.08); color: #9333ea;',
                                                'background: rgba(236,72,153,0.08); color: #ec4899;',
                                                'background: rgba(99,102,241,0.08); color: #6366f1;',
                                                'background: rgba(244,63,94,0.08); color: #f43f5e;',
                                                'background: rgba(20,184,166,0.08); color: #14b8a6;'
                                            ];
                                            $colorIndex = crc32($student['full_name']) % count($colors);
                                            $avatarStyle = $colors[$colorIndex];
                                            $initials = collect(explode(' ', $student['full_name']))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->join('');
                                        @endphp
                                        <tr class="group">
                                            
                                            {{-- Sticky Profile Column --}}
                                            <td class="ss-sticky-col">
                                                <div style="display: flex; align-items: center; gap: 0.875rem;">
                                                    <div class="avatar-wrap" style="{{ $avatarStyle }}">
                                                        {{ $initials }}
                                                    </div>
                                                    <div style="min-width: 0; flex: 1;">
                                                        <p style="font-weight: 900; font-size: 0.875rem; margin: 0;" class="text-slate-900 dark:text-white truncate">{{ $student['full_name'] }}</p>
                                                        <p style="font-size: 10px; font-weight: 700; color: #94a3b8; margin: 2px 0 0 0;">{{ $student['admission_number'] }}</p>
                                                    </div>
                                                </div>
                                            </td>

                                            {{-- Dynamic cells --}}
                                            @foreach($scoreHeads as $sh)
                                                @php
                                                    $val = $scores[$student['id']][$sh['id']] ?? '';
                                                    $key = $student['id'] . '-' . $sh['id'];
                                                @endphp
                                                <td style="text-align: center; border-right: 1px solid rgba(148,163,184,0.1);">
                                                    <div style="display: flex; align-items: center; justify-content: center;">
                                                        <div class="ss-cell-wrap"
                                                            :class="{
                                                                'ss-cell-wrap-error': errorMessages['{{ $key }}'],
                                                                'ss-cell-wrap-saving': savingStates['row-{{ $student['id'] }}'] === 'saving' && $el.querySelector('input') === document.activeElement
                                                            }"
                                                        >
                                                            <span class="ss-bracket" :class="errorMessages['{{ $key }}'] ? 'text-red-500' : ''">[</span>
                                                            <input
                                                                type="number"
                                                                data-row="{{ $loop->parent->index }}"
                                                                data-col="{{ $loop->index }}"
                                                                min="0"
                                                                max="{{ $sh['effective_max'] }}"
                                                                step="0.5"
                                                                placeholder="—"
                                                                value="{{ $val }}"
                                                                
                                                                @keydown.up.prevent="moveFocus($el, -1, 0)"
                                                                @keydown.down.prevent="moveFocus($el, 1, 0)"
                                                                @keydown.left.prevent="moveFocus($el, 0, -1)"
                                                                @keydown.right.prevent="moveFocus($el, 0, 1)"
                                                                @keydown.enter.prevent="moveFocus($el, 1, 0)"
                                                                
                                                                @blur="triggerSave({{ $student['id'] }}, {{ $sh['id'] }}, $el.value, {{ $sh['effective_max'] }}, '{{ $sh['name'] }}')"
                                                                class="ss-cell-input"
                                                            >
                                                            <span class="ss-bracket" :class="errorMessages['{{ $key }}'] ? 'text-red-500' : ''">]</span>
                                                        </div>
                                                    </div>
                                                </td>
                                            @endforeach

                                            {{-- Total --}}
                                            <td style="text-align: center; border-right: 1px solid rgba(148,163,184,0.1); background: rgba(248,250,252,0.25);" class="dark:bg-slate-900/10">
                                                <span id="total-{{ $student['id'] }}" style="font-weight: 900; font-variant-numeric: tabular-nums;" class="text-slate-900 dark:text-white">
                                                    {{ $hasAny ? number_format($studentTotal, 1) : '—' }}
                                                </span>
                                            </td>

                                            {{-- Grade --}}
                                            <td style="text-align: center; border-right: 1px solid rgba(148,163,184,0.1); background: rgba(248,250,252,0.25);" class="dark:bg-slate-900/10">
                                                <span id="grade-{{ $student['id'] }}"
                                                    class="{{ $currentGrade === 'A' || $currentGrade === 'A+' ? 'text-emerald-500 font-black' : ($currentGrade === '—' ? 'text-slate-400' : 'text-slate-600 dark:text-slate-300 font-bold') }}"
                                                    style="font-size: 0.8125rem;"
                                                >
                                                    {{ $currentGrade }}
                                                </span>
                                            </td>

                                            {{-- Sync Status --}}
                                            <td style="text-align: center; background: rgba(248,250,252,0.25);" class="dark:bg-slate-900/10">
                                                <span id="sync-text-{{ $student['id'] }}" style="color: #10b981; font-weight: 700; font-size: 0.75rem;">
                                                    Saved
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            @endif

        @else
            {{-- Begin Entry Empty State --}}
            <div class="ss-empty-state">
                <div style="width: 4rem; height: 4rem; display: flex; align-items: center; justify-content: center; border-radius: 1rem; background: rgba(238,242,255,1); color: #6366f1; margin-bottom: 1.5rem;">
                    <svg style="width: 32px; height: 32px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </div>
                <h3 style="font-size: 1rem; font-weight: 900;" class="dark:text-white">Begin Scorecard Entry</h3>
                <p style="margin-top: 0.5rem; font-size: 0.75rem; color: #94a3b8; max-width: 24rem; line-height: 1.6;">
                    To load the interactive spreadsheet matrix and dynamic metrics, please configure the session, term, class, and subject parameters from the filters above.
                </p>

                <div style="margin-top: 2rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 1rem; font-size: 10px; font-weight: 700; color: #94a3b8;">
                    <span style="display: flex; align-items: center; gap: 0.375rem; background: rgba(148,163,184,0.05); padding: 0.25rem 0.625rem; border-radius: 0.5rem;">
                        <span style="height: 6px; width: 6px; border-radius: 9999px; background: #10b981;"></span>
                        Keyboard Navigation
                    </span>
                    <span style="display: flex; align-items: center; gap: 0.375rem; background: rgba(148,163,184,0.05); padding: 0.25rem 0.625rem; border-radius: 0.5rem;">
                        <span style="height: 6px; width: 6px; border-radius: 9999px; background: #3b82f6;"></span>
                        Real-time Autosave
                    </span>
                    <span style="display: flex; align-items: center; gap: 0.375rem; background: rgba(148,163,184,0.05); padding: 0.25rem 0.625rem; border-radius: 0.5rem;">
                        <span style="height: 6px; width: 6px; border-radius: 9999px; background: #f59e0b;"></span>
                        Live Metrics
                    </span>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
